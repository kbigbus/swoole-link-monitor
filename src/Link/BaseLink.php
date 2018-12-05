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
    public $noticeMsg = ''; //检查链路失败的告警信息
    protected $logger = [];

    public function __construct($link, Logs $logger)
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
