<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Monitor\Main;

class Console
{
    public $logger         = null;
    private $config        = []; //配置项
    private $masterPidFile = '';

    public function __construct($config)
    {
        $this->config = $config;
        $this->logger = Logs::getLogger($this->config['logPath'] ?? '');
        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $this->masterPidFile = $this->config['pidPath'] . '/master.pid';
        } else {
            echo 'config pidPath must be set!' . PHP_EOL;
            exit;
        }
    }

    /**
     * 执行入口.
     *
     * @param array $argv 参数
     */
    public function run($argv)
    {
        if (count($argv) <= 1) {
            $this->echoHelpMsg();

            return;
        }
        switch (strtolower($argv[1])) {
            case 'start':
                $this->sendStartSignal();
            break;
            case 'stop':
                $this->sendExitSignal();
            break;
            case 'exit':
                $this->sendExitSignal();
            break;
            case 'restart':
                $this->sendRestartSignal();
            break;
            case 'status':
                $this->sendStatusSignal();
            break;
            case 'help':
            default:
                $this->echoHelpMsg();
            break;
        }
    }

    /**
     * 启动服务
     */
    private function sendStartSignal()
    {
        $main = new Main($this->config);
        $main->start();
    }

    /**
     * 停止服务 强制处理.
     */
    private function sendExitSignal()
    {
        $this->sendSignal(SIGTERM);
    }

    /**
     * 重启服务
     */
    private function sendRestartSignal()
    {
        $this->sendExitSignal();
        sleep(2);
        $this->sendStartSignal();
    }

    /**
     * 查看服务
     */
    private function sendStatusSignal()
    {
        $this->sendSignal(SIGUSR1);
    }

    /**
     * 发送信号操作服务
     *
     * @param mixed $signal
     */
    private function sendSignal($signal = SIGTERM)
    {
        //获取主进程PID
        if (file_exists($this->masterPidFile)) {
            $pid = file_get_contents($this->masterPidFile);
            //发送信号
            @\swoole_process::kill($pid, $signal);

            return;
        }
        echo "\rlink-monitor service is not running                       " . PHP_EOL;
    }

    /**
     * 输出提示信息.
     */
    private function echoHelpMsg()
    {
        $helpStr = <<<'ET'
NAME
       	php link-monitor - manage link-monitor

SYNOPSIS
       	php link-monitor [command] 

DESCRIPTION
        Link monitoring alarm based on swoole
        *   Based on swoole
            |__ timer           Second level monitoring
            |__ memory table    Records the number and time of errors
            |__ process/worker  Multiprocess Queue Mode Consumption Monitoring Link
        *   Unlimited extended link
        *   Unlimited extended alarm  

COMMAND OPTIONS 
        help 
        Show this help info

        start
        Start link monitor service

        stop
        Stop link monitor service

        restart
        You know what I mean



ET;
        echo $helpStr;
    }
}
