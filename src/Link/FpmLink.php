<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) php-team@yaochufa <php-team@yaochufa.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use GuzzleHttp\Client;

class FpmLink extends BaseLink
{
    /**
     * @var bool|Client
     */
    public $connection = false; //链接对象成员

    /**
     * @var array
     */
    private $reqData = []; // 请求拿到的数据

    public function __construct($link, $memoryTable, $logger)
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
        if ($this->connection) {
            return $this->connection;
        }

        return new Client();
    }

    public function checkConnection(): bool
    {
        $connectSetting = $this->linkSetting['connectSetting'];

        try {
            $res = $this->connection->request('GET', 'http://' . $connectSetting['host'] . ':'
                . $connectSetting['port'] . $connectSetting['uri'] . '?json',
                ['timeout' => $connectSetting['timeout']]);
            $httpCode      = $res->getStatusCode();
            $data          = json_decode($res->getBody()->getContents(), true);
            $this->reqData = $data;

            if ((200 != $httpCode || empty($data)) && $this->setNoticeMsg()) { // 连接不成功而且达到出错次数
                return false;
            }
            $this->logger->applicationLog($this->logFix . ' test fpm: http code ' . $httpCode . ' data: ' . json_encode($data));
            $this->errorMsg = ''; //重置错误信息

            return true;
        } catch (\Exception $e) {
            if ($this->setNoticeMsg()) {
                return false;
            }
            $this->logger->applicationLog($this->logFix . ' test fpm: has exception ' . $e->getMessage());
            $this->errorMsg = ''; //重置错误信息
            return true;
        }
    }

    /**
     * check: 1.listen queue 2.max children reached 3.slow requests
     * http://www.ttlsa.com/php/use-php-fpm-status-page-detail.
     *
     * @return bool
     */
    public function checkOperation(): bool
    {
        $ret = false;
        try {
            $this->logger->applicationLog($this->logFix . ' test fpm operation');
            if (!empty($this->reqData)) {
                $listenQueueLimit        = $this->linkSetting['connectSetting']['listenQueueLimit'] ?? -1;
                $maxChildrenReachedLimit = $this->linkSetting['connectSetting']['maxChildrenReachedLimit'] ?? -1;
                $slowRequestsLimit       = $this->linkSetting['connectSetting']['slowRequestsLimit'] ?? -1;
                $listenQueueData         = $this->reqData['listen queue'];
                $maxChildrenReachedData  = $this->reqData['max children reached'];
                $slowRequestsData        = $this->reqData['slow requests'];
                $handleError             = false; // 默认操作无错误
                $this->errorMsg          = '';

                if ($listenQueueLimit > 0 && $listenQueueData >= $listenQueueLimit) {
                    $handleError = true;
                    $this->errorMsg .= " listenQueue=${listenQueueData}(limit=${listenQueueLimit}). ";
                }
                if ($maxChildrenReachedLimit > 0 && $maxChildrenReachedData >= $maxChildrenReachedLimit) {
                    $handleError = true;
                    $this->errorMsg .= " maxChildrenReached=${maxChildrenReachedData}(limit=${maxChildrenReachedLimit}). ";
                }
                if ($slowRequestsLimit > 0 && $slowRequestsData >= $slowRequestsLimit) {
                    $handleError = true;
                    $this->errorMsg .= " slowRequests=${slowRequestsData}(limit=${slowRequestsLimit}). ";
                }

                if ($handleError && $this->setNoticeMsg(self::CHECK_TYPE_OPERATION)) {
                    return false;
                }
                $this->errorMsg = ''; //重置错误信息

                return true;
            }
            // 请求数据为空，不检查操作
            $this->errorMsg = ''; //重置错误信息
            return true;
        } catch (\Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->logger->applicationLog($this->logFix . ' test fpm operation error, errorInfo:' . json_encode($ex));
        }

        return $ret;
    }
}
