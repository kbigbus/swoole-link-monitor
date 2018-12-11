<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Monitor;

use LinkMonitor\Helper\Errors;
use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;
use LinkMonitor\Link\FactoryLink;
use LinkMonitor\Notice\FactoryNotice;

class Main
{
    public $logger        = null;
    public $pid           = null; //进程ID
    public $masterPidFile = '';
    private $config       = [];
    private $workers      = []; //子进程列表

    /**
     * 初始化.
     *
     * @param array $config 配置项
     *                      [
                           		'tickerTime' => 5;//每5秒循环一次
     *                      ]
     */
    public function __construct($config)
    {
        $this->config = $config ?: ['tickerTime'=>5, 'workerNum'=>4];
        $this->logger = Logs::getLogger($this->config['logPath'] ?? '');
        \swoole_process::daemon();
        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $this->masterPidFile = $this->config['pidPath'] . '/master.pid';
        } else {
            echo 'config pidPath must be set!' . PHP_EOL;
            exit;
        }
    }

    //启动
    public function start()
    {
        try {
            //监听信号
            $this->registerSignalListener();
            //添加时间监听
            $this->addTimeListener();
            $this->pid = getmypid();
            $this->saveMasterPid();
            $this->setProcessName();
        } catch (\Exception $ex) {
            Utils::catchError($this->logger, $ex);
        }
    }

    //定时监听
    public function addTimeListener()
    {
        if ((int) $this->config['tickerTime'] <= 0 || !$this->config['linkList'] || !$this->config['workerNum']) {
            throw new \Exception(Errors::SETTING_ERROR_MESSAGE, Errors::SETTING_ERROR_CODE);
        }
        try {
            //内存方式记录出错次数
            $swooleTable = new MemoryTable(count($this->config['linkList']));
            \swoole_timer_tick($this->config['tickerTime'] * 1000, function () use ($swooleTable) {
                $customMsgKey = 1;
                $mod          = 2 | \swoole_process::IPC_NOWAIT; //这里设置消息队列为非阻塞模式

                $workers = [];
                $factoryLink = new FactoryLink();
                $factoryLink->getConfig($this->config);
                $factoryNotice = new FactoryNotice();
                $factoryNotice->getConfig($this->config);
                for ($i=0; $i < $this->config['workerNum']; $i++) {
                    //开启子进程检查链路
                    $process = new \swoole_process(function ($worker) use ($factoryLink, $factoryNotice, $swooleTable) {
                        $this->setProcessName(false);
                        $pid = $worker->pid;
                        $this->logger->log('Worker Start, PID=' . $pid);
                        $workerIndex = false;
                        while ($recv = $worker->pop()) {
                            //获取队列内容 获取 链路对象
                            list($linkSetting, $workerIndex) = @json_decode($recv, true);
                            $linkObject = $factoryLink->getLinkObject($linkSetting, $swooleTable);
                            if ($linkObject) {
                                $sendNotice = false; //默认不告警
                                if (!isset($linkSetting['checkList'])) {
                                    //不存在则只检查连接
                                    $connectRet = $linkObject->checkConnection();
                                    !$connectRet && $sendNotice = true;
                                } else {
                                    //检查链接
                                    $connectRet = true;
                                    if (in_array(1, $linkSetting['checkList'])) {
                                        $connectRet = $linkObject->checkConnection();
                                        !$connectRet && $sendNotice = true;
                                    }
                                    if ($connectRet) {
                                        //检查操作
                                        if (in_array(2, $linkSetting['checkList'])) {
                                            $operateRet = $linkObject->checkOperation();
                                            !$operateRet && $sendNotice = true;
                                        }
                                    }
                                }
                                if ($sendNotice) {
                                    $noticeObject = $factoryNotice->getNoticeObject($linkSetting);
                                    $noticeObject->setContent($linkObject->noticeMsg);
                                    $noticeObject->send();
                                }
                            } else {
                                $this->logger->errorLog('get link object failed,linkSetting:' . json_encode($linkSetting));
                            }
                        }
                        if (false !== $workerIndex) {
                            unset($this->workers[$workerIndex]);
                        }
                        $worker->exit(0);
                    }, false, false);
                    $process->useQueue($customMsgKey, $mod);
                    $pid           = $process->start();
                    $this->workers[] = [$pid, $process];
                }
                //循环取模入队
                $this->config['linkList'] = array_values($this->config['linkList']);
                $countWorkers = count($this->workers);
                $i = 0;
                foreach ($this->config['linkList'] as $link) {
                    $workerIndex = $i % $countWorkers;
                    $pid = $this->workers[$workerIndex][0];
                    $process = $this->workers[$workerIndex][1];
                    $process->push(json_encode([$link, $workerIndex]));
                    $i++;
                }
                //执行完成  删除队列
                foreach ($this->workers as $worker) {
                    \swoole_process::wait();
                }
            });
        } catch (\Exception $ex) {
            Utils::catchError($this->logger, $ex);
        }
    }

    /**
     * 设置进程名称.
     *
     * @param bool $master 是否是主进程
     */
    public function setProcessName($master = true)
    {
        $processName = ((isset($this->config['processName']) && $this->config['processName']) ? $this->config['processName'] : 'swoole-link-monitor') . ':' . ($master ? 'Master' : 'Worker');
        //mac os不支持进程重命名
        if (function_exists('swoole_set_process_name') && PHP_OS != 'Darwin') {
            \swoole_set_process_name($processName);
        }
    }

    /**
     * 设置信号监听.
     */
    public function registerSignalListener()
    {
        //停止
        \swoole_process::signal(SIGTERM, function ($signo) {
            $this->forceExitService();
        });
        //停止
        \swoole_process::signal(SIGKILL, function ($signo) {
            $this->forceExitService();
        });
    }

    /**
     * 强制停止服务
     */
    public function forceExitService()
    {
        $this->forceExitWorkers();
        $this->forceExistMaster();
    }

    /**
     * 强制退出子进程.
     */
    public function forceExitWorkers()
    {
        foreach ($this->workers as $key=>$worker) {
            $pid = $worker[0];
            @\swoole_process::kill($pid);
            unset($this->workers[$key]);
        }
    }

    /**
     * 强制退出主进程.
     */
    public function forceExistMaster()
    {
        @\swoole_process::kill($this->pid);
        @unlink($this->masterPidFile);
        exit;
    }

    /**
     * 写入主进程ID.
     */
    public function saveMasterPid()
    {
        file_put_contents($this->masterPidFile, $this->pid);
    }
}
