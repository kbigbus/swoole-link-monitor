<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

//链路配置
return [
    [
        'linkType'       => 'mq', //链路资源类型
        'noticeType'     => 1, //通知类型
        'timeLimit'      => 3, //链接告警时间
        'connectSetting' => [
            'host'    => '192.168.9.24',
            'user'    => 'mqadmin',
            'pass'    => 'mqadmin',
            'port'    => '5672',
            'vhost'   => 'php',
            'topic'   => 'test', //测试的队列名称
        ],
    ],
    [
        'type'      => 'api2', //链路资源类型
        'noticeType'=> '',
    ],
    [
        'type'      => 'api3', //链路资源类型
        'noticeType'=> '',
    ],
    [
        'type'      => 'api4', //链路资源类型
        'noticeType'=> '',
    ],
    [
        'type'      => 'api5', //链路资源类型
        'noticeType'=> '',
    ],
    [
        'type'      => 'api6', //链路资源类型
        'noticeType'=> '',
    ],
];
