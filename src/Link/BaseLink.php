<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;

class BaseLink
{
    const CHECK_TYPE_CONNECTION = 1; //检查链接
    const CHECK_TYPE_OPERATION  = 2; //检查操作

    public $memoryTable         = null;
    public $noticeMsg           = ''; //检查链路失败的告警信息
    protected $logger           = [];

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
}
