<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class MqLink extends BaseLink
{
    public $connection              = false; //链接对象成员
    protected static $staticConnect = []; //静态资源

    /**
     * 初始化.
     *
     * @param array  $link        链路配置
     * @param object $memoryTable 共享内存对象
     * @param object $logger      日志对象
     */
    public function __construct($link, $memoryTable, Logs $logger)
    {
        $this->memoryTable = $memoryTable;
        $this->logger      = $logger;
        $this->linkSetting = $link;
        if (!isset($this->linkSetting['connectSetting']) || !$this->linkSetting['connectSetting']) {
            $this->logger->errorLog('link setting error');

            return false;
        }
        $this->logFix     = $this->linkSetting['connectSetting']['host'] . ':' . $this->linkSetting['connectSetting']['port'];
        $this->connection = $this->getConnection();
    }

    //单例模式获取链接对象
    public function getConnection()
    {
        $linkSetting = $this->linkSetting['connectSetting'];
        if (isset(self::$staticConnect[$linkSetting['host']]) && null !== self::$staticConnect[$linkSetting['host']]) {
            return self::$staticConnect[$linkSetting['host']];
        }
        $class = class_exists('\AMQPConnection', false);
        if ($class) {
            try {
                self::$staticConnect[$linkSetting['host']] = new \AMQPConnection([
                    'host'           => $linkSetting['host'],
                    'login'          => $linkSetting['user'],
                    'password'       => $linkSetting['pass'],
                    'port'           => $linkSetting['port'],
                    'vhost'          => $linkSetting['vhost'],
                    'connect_timeout'=> $linkSetting['timeout'] ?? 1, //设置链接超时 防止由于链接过长导致链路阻塞
                ]);
                $connectRet = self::$staticConnect[$linkSetting['host']]->connect();

                return $connectRet ? self::$staticConnect[$linkSetting['host']] : null;
            } catch (\AMQPConnectionException $ex) {
                Utils::catchError($this->logger, $ex);

                return false;
            } catch (\Throwable $ex) {
                Utils::catchError($this->logger, $ex);

                return false;
            } catch (\Exception $ex) {
                Utils::catchError($this->logger, $ex);

                return false;
            }
        } else {
            $this->logger->errorLog('you need install pecl amqp extension');

            return false;
        }
    }

    /**
     * 检查链路链接是否成功
     *
     * @return bool 返回检查结果  true成功  false失败
     */
    public function checkConnection(): bool
    {
        if (!$this->connection && $this->setNoticeMsg()) {
            return false;
        }
        $this->logger->applicationLog($this->logFix . ' test mq connection success');

        return true;
    }

    /**
     * 检查链路操作是否成功   写入队列.
     *
     * @return bool 返回检查结果  true成功  false失败
     */
    public function checkOperation(): bool
    {
        $ret    = false;
        try {
            $this->logger->applicationLog($this->logFix . ' test mq publish0');
            if ($this->connection) {
                $routingKey = $this->linkSetting['connectSetting']['topic'] ?? 'test';
                $channel    = new \AMQPChannel($this->connection);
                $exchange   = new \AMQPExchange($channel);
                $queue      = new \AMQPQueue($channel);
                $queue->setName($routingKey);
                $queue->setFlags(AMQP_DURABLE);
                $queue->declareQueue();
                $publishRet = $exchange->publish('linkMonitor test message', $routingKey);
                $outRet     = $queue->get(AMQP_AUTOACK);
                $this->logger->applicationLog($this->logFix . ' test mq publish1, publish:' . json_encode($publishRet) . ', out:' . json_encode($outRet));
                if ((!$publishRet || !$outRet) && $this->setNoticeMsg(self::CHECK_TYPE_OPERATION)) {
                    return false;
                }

                return true;
            }
        } catch (\Exception $ex) {
            $this->logger->applicationLog($this->logFix . ' test mq publish error, errorInfo:' . json_encode($ex));
        }

        return $ret;
    }
}
