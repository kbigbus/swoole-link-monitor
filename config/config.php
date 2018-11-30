<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    //全局配置
    'noticeType' => 0, //1钉钉提醒 2邮件提醒 0全部提醒
    'timeLimit'  => 3, //链路超时时间 以秒为单位
    'tickerTime' => 10, //每10秒一次轮训
    'workerNum'  => 4, //worker数量
    'logPath'    => LINK_MONITOR_PATH . '/runtime/',
    //链路配置
    'linkList'   => require 'link.php',
    //告警配置
    'notice'     => require 'notice.php',
];
