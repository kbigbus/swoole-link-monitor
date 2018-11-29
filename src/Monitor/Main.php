<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Monitor;

use LinkMonitor\Helper\Logs;

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
        // try {
        //TODO 监听信号

        //添加时间监听
        $this->addTimeListener();
        // } catch (\Exception $ex) {
        // }
    }

    //定时监听
    public function addTimeListener()
    {
        if ((int) $this->config['tickerTime'] <= 0 || !$this->config['linkList'] || !$this->config['workerNum']) {
            throw new \Exception('wrong ticker time or link setting');
        }
        \swoole_timer_tick($this->config['tickerTime'] * 1000, function () {
            $workers = [];
            for ($i=0; $i < $this->config['workerNum']; $i++) {
                //开启子进程检查链路
                $process = new \swoole_process(function ($worker) {
                    while ($recv = $worker->pop()) {
                        //获取队列内容
                        $this->logger->log('recv =' . $recv . PHP_EOL);
                    }
                    $worker->exit(0);
                }, false, false);
                $process->useQueue();
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
                $process->push('worker:' . $pid);
                usleep(300); //异步并发  必须sleep 等callback执行完成后在kill
            }
            //执行完成  删除队列
            foreach ($workers as $key=>$worker) {
                $pid = $worker[0];
                \swoole_process::kill($pid);
                unset($workers[$key]);
                $this->logger->log('Worker Exit, PID=' . $pid . PHP_EOL);
            }
        });
    }
}
