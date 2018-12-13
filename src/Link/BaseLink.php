<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Monitor\MemoryTable;

class BaseLink
{
    const CHECK_TYPE_CONNECTION = 1; //检查链接
    const CHECK_TYPE_OPERATION  = 2; //检查操作

    public $memoryTable    = null;
    public $noticeMsg      = ''; //检查链路失败的告警信息
    public $logFix         = ''; //应用日志前缀

    protected $logger      = [];
    protected $linkSetting = [];

    /**
     * 初始化.
     *
     * @param array  $link        链路配置
     * @param object $memoryTable 共享内存对象
     * @param object $logger      日志对象
     */
    public function __construct($link, $memoryTable, Logs $logger)
    {
    }

    /**
     * 检查链路链接是否成功
     *
     * @return bool 返回检查结果  true成功  false失败
     */
    public function checkConnection(): bool
    {
        return true;
    }

    /**
     * 检查链路操作是否成功
     *
     * @return bool 返回检查结果  true成功  false失败
     */
    public function checkOperation(): bool
    {
        return true;
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
