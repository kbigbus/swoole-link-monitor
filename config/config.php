<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    //全局配置
    'noticeType'      => 1, //通知类型 1钉钉提醒 2邮件提醒
    'tickerTime'      => 3, //每10秒一次轮训
    'workerNum'       => 4, //worker数量
    'dingdingSetting' => [//钉钉提醒配置
        'access_token' => 'd8331d4ae86a8b870bab2e7c5f2a23cae2312c4337602cdf4260b96a514e08ec',
        'atMobiles'    => ['13570233789'],
        'isAtAll'      => false,
    ],
    'emailSetting'  => [//邮件提醒配置
        'smtp' => [
            'smtpserver' => 'xxx', //邮件服务器地址
            'smtpuser'   => 'xxxx', //邮件服务器账号
            'smtppass'   => 'xxx', //邮件服务器密码
            'logfile'    => '/xxx/xxx/xxx', //邮件发送日志文件路径 可为空
        ],
        'mailto' => 'xxx', //接收告警的邮箱 多个以逗号隔开
    ],

    'logPath'     => LINK_MONITOR_PATH . '/runtime/', //日志路径
    'pidPath'     => LINK_MONITOR_PATH . '/runtime/', //进程ID路径
    'processName' => 'swoole-link-monitor', //进程名称
    //链路配置
    'linkList' => require 'link.php',
];
