<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;
use LinkMonitor\Monitor\MemoryTable;

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
                self::$staticConnect[$linkSetting['host']] = new \AMQPConnection();
                self::$staticConnect[$linkSetting['host']]->setHost($linkSetting['host']);
                self::$staticConnect[$linkSetting['host']]->setLogin($linkSetting['user']);
                self::$staticConnect[$linkSetting['host']]->setPassword($linkSetting['pass']);
                self::$staticConnect[$linkSetting['host']]->setPort($linkSetting['port']);
                self::$staticConnect[$linkSetting['host']]->setVhost($linkSetting['vhost']);
                self::$staticConnect[$linkSetting['host']]->connect();

                return self::$staticConnect[$linkSetting['host']];
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

        return true;
    }

    /**
     * 检查链路操作是否成功   写入队列.
     *
     * @return bool 返回检查结果  true成功  false失败
     */
    public function checkOperation(): bool
    {
        $ret = false;
        try {
            $this->logger->applicationLog('test mq publish0');
            if ($this->connection) {
                $this->logger->applicationLog('test mq publish1');
                $routingKey = $this->linkSetting['connectSetting']['topic'] ?? 'test';
                $channel    = new \AMQPChannel($this->connection);
                $exchange   = new \AMQPExchange($channel);
                $queue      = new \AMQPQueue($channel);
                $queue->setName($routingKey);
                $queue->setFlags(AMQP_DURABLE);
                $queue->declareQueue();
                $publishRet = $exchange->publish('linkMonitor test message', $routingKey);
                $outRet     = $queue->get(AMQP_AUTOACK);
                $this->logger->applicationLog('test mq publish, return:' . json_encode($publishRet));
                if ((!$publishRet || !$outRet) && $this->setNoticeMsg(self::CHECK_TYPE_OPERATION)) {
                    return false;
                }

                return true;
            }
        } catch (Exception $ex) {
            $this->logger->applicationLog('test mq publish error, errorInfo:' . json_encode($ex));
        }

        return $ret;
    }

    /**
     * 检查共享内存的错误数是否超标.
     */
    public function checkMemoryTableErrorNum()
    {
        $linkKey     = md5(json_encode($this->linkSetting['connectSetting']));
        $errorNowNum = $this->memoryTable->incrErrorNum($linkKey);
        if (1 == $errorNowNum) {//设置出错开始时间
            $this->memoryTable->setStartTime($linkKey);
        }
        $noticeTimes = $this->linkSetting['noticeTimes'] ?? 5; //不存在则默认5次
        if ($errorNowNum >= $noticeTimes) {//设置出错结束时间
            $this->memoryTable->setEndTime($linkKey);
            $errorInfo   = $this->memoryTable->getKeyValue($linkKey);
            $this->memoryTable->delKey($linkKey);
            $errorColumn = MemoryTable::COLUMN_NAME;
            $errorStr    = [];
            foreach ($errorInfo as $key=>$error) {
                $errorStr[] = ($errorColumn[$key] ?? $key) . '：' . $error;
            }

            return implode(PHP_EOL, $errorStr);
        }

        return false;
    }

    /**
     * 设置告警信息.
     *
     * @param int $checkType 检查类型 1检查链接  2检查操作
     */
    public function setNoticeMsg($checkType = self::CHECK_TYPE_CONNECTION)
    {
        $errorStr = $this->checkMemoryTableErrorNum();
        if (!$errorStr) {
            return false;
        }
        $linkSetting     = $this->linkSetting['connectSetting'];
        $this->noticeMsg = '链路' . (1 == $checkType ? '连接' : '操作') . '异常' . PHP_EOL
        . '类型：' . $this->linkSetting['linkType'] . PHP_EOL
        . '主机：' . $linkSetting['host'] . PHP_EOL
        . '端口：' . $linkSetting['port'] . PHP_EOL;
        $this->noticeMsg .= $errorStr;

        return true;
    }
}
