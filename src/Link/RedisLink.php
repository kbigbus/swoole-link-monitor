<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class RedisLink extends BaseLink
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
        $class = class_exists('\Redis', false);
        if ($class) {
            try {
                self::$staticConnect[$linkSetting['host']] = new \Redis();
                $connectRet                                = self::$staticConnect[$linkSetting['host']]->connect($linkSetting['host'], $linkSetting['port'], $linkSetting['timeout'] ?? 1);
                //是否验证
                $authRet = true;
                if (isset($linkSetting['auth']) && $linkSetting['auth']) {
                    $authRet = self::$staticConnect[$linkSetting['host']]->auth($linkSetting['auth']);
                }

                return ($connectRet && $authRet) ? self::$staticConnect[$linkSetting['host']] : null;
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
            $this->logger->errorLog('you need install pecl redis extension');

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
            $this->logger->applicationLog('test redis set0');
            if ($this->connection) {
                $testKey = $this->linkSetting['connectSetting']['key'] ?? 'test';
                $setRet  = $this->connection->set($testKey, 'test redis set');
                $delRet  = $this->connection->del($testKey);
                $this->logger->applicationLog('test redis set1, set:' . json_encode($setRet) . ', del:' . json_encode($delRet));
                if ((!$setRet || !$delRet) && $this->setNoticeMsg(self::CHECK_TYPE_OPERATION)) {
                    return false;
                }

                return true;
            }
        } catch (\Exception $ex) {
            $this->logger->applicationLog('test redis set error, errorInfo:' . json_encode($ex));
        }

        return $ret;
    }
}
