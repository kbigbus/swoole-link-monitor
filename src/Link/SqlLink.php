<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;
use Medoo\Medoo;

class SqlLink extends BaseLink
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
        $class = class_exists('\PDO', false);
        if ($class) {
            try {
                self::$staticConnect[$linkSetting['host']] = new Medoo([
                    'database_type' => $linkSetting['adapter'],
                    'database_name' => $linkSetting['db'],
                    'server'        => $linkSetting['host'],
                    'port'          => $linkSetting['port'],
                    'username'      => $linkSetting['user'],
                    'password'      => $linkSetting['pass'],
                    'charset'       => 'utf8',
                ]);

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
            $this->logger->errorLog('you need install PDO extension');

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
        $ret    = false;
        $logFix = $this->linkSetting['connectSetting']['host'] . ':' . $this->linkSetting['connectSetting']['port'];
        try {
            $this->logger->applicationLog($logFix . ' test mysql insert0');
            if ($this->connection) {
                $testTable   = $this->linkSetting['connectSetting']['test']['table'] ?? 'test';
                $testField   = $this->linkSetting['connectSetting']['test']['field'] ?? 'id';
                $insertRet   = $this->connection->insert($testTable, [$testField=>1]);
                $delRet      = $this->connection->delete($testTable, [$testField=>1]);
                $errorInfo   = $this->connection->error();
                $handleError = false; //默认操作无误
                if ($errorInfo && isset($errorInfo[0]) && $errorInfo[0] && isset($errorInfo[1]) && $errorInfo[1]) {
                    $handleError = true;
                }
                $this->logger->applicationLog($logFix . ' test mysql insert1, insert:' . json_encode($insertRet) . ', del:' . json_encode($delRet) . ', errorInfo:' . json_encode($errorInfo));
                if ($handleError && $this->setNoticeMsg(self::CHECK_TYPE_OPERATION)) {
                    return false;
                }

                return true;
            }
        } catch (\Exception $ex) {
            $this->logger->applicationLog($logFix . ' test mysql insert error, errorInfo:' . json_encode($ex));
        }

        return $ret;
    }
}
