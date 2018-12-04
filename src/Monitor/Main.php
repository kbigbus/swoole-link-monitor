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
    public $logger  = null;
    private $config = [];

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
        //\swoole_process::daemon();
    }

    //启动
    public function start()
    {
        try {
            //TODO 监听信号

            //添加时间监听
            $this->addTimeListener();
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
        \swoole_timer_tick($this->config['tickerTime'] * 1000, function () {
            $customMsgKey = 1;
            $mod          = 2 | \swoole_process::IPC_NOWAIT; //这里设置消息队列为非阻塞模式

            $workers = [];
            $factoryLink = new FactoryLink();
            $factoryLink->getConfig($this->config);
            $factoryNotice = new FactoryNotice();
            $factoryNotice->getConfig($this->config);
            for ($i=0; $i < $this->config['workerNum']; $i++) {
                //开启子进程检查链路
                $process = new \swoole_process(function ($worker) use ($factoryLink, $factoryNotice) {
                    $pid = $worker->pid;
                    $this->logger->log('Worker Start, PID=' . $pid);
                    //echo 'Worker Start, PID=' . $pid . PHP_EOL;
                    while ($recv = $worker->pop()) {
                        //获取队列内容 获取 链路对象
                        $linkSetting = json_decode($recv, true);
                        $linkObject = $factoryLink->getLinkObject($linkSetting);
                        if ($linkObject) {
                            if (!isset($linkSetting['checkList'])) {
                                //不存在则只检查连接
                                $connectRet = $linkObject->checkConnection();
                            } else {
                                //检查链接
                                if (in_array(1, $linkSetting['checkList'])) {
                                    $connectRet = $linkObject->checkConnection();
                                } else {
                                    $connectRet = true;
                                }
                                //检查操作
                                if ($connectRet) {
                                    if (in_array(2, $linkSetting['checkList'])) {
                                        $operateRet = $linkObject->checkConnection();
                                    }
                                } else {
                                    $noticeObject = $factoryNotice->getNoticeObject($linkSetting);
                                    $noticeObject->setContent('这是个测试告警，配置信息：' . json_encode($linkSetting));
                                    $noticeObject->send();
                                }
                            }
                        } else {
                            $this->logger->log('get link object failed,linkSetting:' . json_encode($linkSetting), 'info', Logs::LEVEL_ERROR);
                        }
                        //echo 'recv =' . $recv . PHP_EOL;
                        //$this->logger->log('recv =' . $recv . PHP_EOL);
                    }
                    //$this->logger->log('Worker Exit, PID=' . $pid);
                    //echo 'Worker Exit, PID=' . $pid . PHP_EOL;
                    $worker->exit(0);
                }, false, false);
                $process->useQueue($customMsgKey, $mod);
                $pid           = $process->start();
                $workers[] = [$pid, $process];
            }
            //循环取模入队
            $this->config['linkList'] = array_values($this->config['linkList']);
            $countWorkers = count($workers);
            foreach ($this->config['linkList'] as $key=>$link) {
                $workerIndex = $key % $countWorkers;
                $pid = $workers[$workerIndex][0];
                $process = $workers[$workerIndex][1];
                //echo json_encode($link) . ' pid：' . $pid . PHP_EOL;
                $process->push(json_encode($link));
            }
            //执行完成  删除队列
            foreach ($workers as $worker) {
                \swoole_process::wait();
            }
        });
    }
}
