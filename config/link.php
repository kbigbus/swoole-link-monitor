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
        'checkList'      => [1, 2], //检查列表 1检查链接  2检查操作
        'noticeType'     => 1, //通知类型 1钉钉提醒 2邮件提醒
        'noticeTimes'    => 5, //每多少次出错预警一次
        'connectSetting' => [
            'alias'   => '开发环境消息队列', //别名
            'host'    => '192.168.9.24',
            'user'    => 'mqadmin',
            'pass'    => 'mqadmin',
            'port'    => 5672,
            'vhost'   => 'php',
            'topic'   => 'test', //测试的队列名称
            'timeout' => 1, //链路超时时间 默认1s 防止由于链接过长导致链路阻塞
        ],
        // 'dingdingSetting' => [//钉钉提醒配置
        //     'access_token' => 'xxx',
        //     'atMobiles'    => [],
        //     'isAtAll'      => false,
        // ],
        // 'emailSetting'  => [//邮件提醒配置
        //     'smtp' => [
        //         'smtpserver' => 'xxx', //邮件服务器地址
        //         'smtpport'   => 25, //邮件服务器端口
        //         'smtpuser'   => 'ixxx', //邮件服务器账号
        //         'smtppass'   => 'xxx', //邮件服务器密码
        //         'logfile'    => '/xxx/xxx/xxx', //邮件发送日志文件路径 可为空
        //     ],
        //     'mailto' => 'xxx', //接收告警的邮箱 多个以逗号隔开
        // ],
    ],
    [
        'linkType'       => 'redis', //链路资源类型
        'checkList'      => [1, 2], //检查列表 1检查链接  2检查操作
        'noticeType'     => 1, //通知类型 1钉钉提醒 2邮件提醒
        'noticeTimes'    => 5, //每多少次出错预警一次
        'connectSetting' => [
            'host' => '192.168.10.6',
            'port' => 6379,
            //'auth' => '',//认证密码
            'key'     => 'test', //测试的可以名称
            'timeout' => 1, //链路超时时间 默认1s 防止由于链接过长导致链路阻塞
        ],
    ],
    [
        'linkType'       => 'sql', //链路资源类型
        'checkList'      => [1, 2], //检查列表 1检查链接  2检查操作
        'noticeType'     => 1, //通知类型 1钉钉提醒 2邮件提醒
        'noticeTimes'    => 5, //每多少次出错预警一次
        'connectSetting' => [
            'adapter'  => 'mysql', //数据库类型  mysql/pgsql/sybase/oracle/mssql/sqlite
            'host'     => '192.168.10.6', //数据库主机
            'port'     => 3306, //数据库端口
            'user'     => 'admin', //数据库账号
            'pass'     => '123456', //数据库密码
            'db'       => 'ycf_test', //数据库名称
            'test'     => ['table'=>'test', 'field'=>'id'], //测试表名称与字段
            'timeout'  => 1, //链路超时时间 默认1s 防止由于链接过长导致链路阻塞
        ],
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
