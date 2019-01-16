# 全局配置

一维数组。包括轮询时间、子进程数、告警配置等全局设置

* tickerTime 主进程定时轮询检查时间 每多少秒检查一次
* workerNum  检查的子进程数量
* noticeType 告警类型 1钉钉提醒 2邮件提醒
* dingdingSetting   钉钉机器人告警配置
    + access_token 钉钉机器人对应access_token
    + atMobiles @具体人的手机号
    + isAtAll 是否@所有人 

* emailSetting 邮件告警配置
    * smtp 邮件服务器配置
        + smtpserver 邮件服务器地址
        + smtpuser 邮件服务器账号
        + smtppass 邮件服务器密码
    * mailto 接收告警的邮箱 多个以逗号隔开
* logPath 日志路径
* pidPath 保存进程ID的文件路径
* processName 进程名称 默认 swoole-link-monitor
* linkList [链路配置](https://github.com/kbigbus/swoole-link-monitor/blob/master/docs/config.md#%E9%93%BE%E8%B7%AF%E9%85%8D%E7%BD%AE)



# 链路配置

二维数组。每个数组代表一条链路配置，以下为单条链路的配置说明

* linkType 链路资源类型 mq/redis/sql/api..
* checkList 检查列表  1检查链接  2检查操作
* noticeType 告警类型 1钉钉提醒 2邮件提醒  `存在则覆盖全局配置`
* noticeTimes 每多少次出错预警一次
* connectSetting 链路链接操作配置
    * rabbitmq 配置
        + alias 别名 存在该设置则告警不显示主机和端口
        + host 主机
        + user 账户
        + pass 密码
        + port 端口
        + vhost vhost
        + topic 测试队列名称
        + timeout 链接超时时间 无该配置则默认1s
    * redis 配置
        + alias 别名 存在该设置则告警不显示主机和端口
        + host 主机
        + port 端口
        + auth 认证密码 可为空
        + key 测试的key值
        + timeout 链接超时时间 无该配置则默认1s
    * sql 配置
        + alias 别名 存在该设置则告警不显示主机和端口
        + adapter 数据库类型  mysql/pgsql/sybase/oracle/mssql/sqlite
        + host 主机
        + port 端口
        + user 账号
        + pass 密码
        + db 数据库
        + test 测试表与字段配置
            + table 测试表
            + field 测试字段
        + timeout 链接超时时间 无该配置则默认1s
    * PHP-FPM 配置
        + host 主机
        + port 端口
        + uri PHP-FPM状态页的uri，默认是/status
        + timeout 链路超时时间 默认1s 防止由于链接过长导致链路阻塞
        + listenQueueLimit 0-忽略，正数-限制，listenQueue表示请求等待队列，如果这个值不为0，那么要增加FPM的进程数量
        + maxChildrenReachedLimit 0-忽略，正数-限制，maxChildrenReached表示达到进程最大数量限制的次数，如果这个数量不为0，那说明你的最大进程数量太小了
        + slowRequestsLimit 0-忽略，正数-限制，slowRequests表示启用了php-fpm slow-log后，缓慢请求的数量

* dingdingSetting   钉钉机器人告警配置  `存在则覆盖全局配置`
    * access_token 钉钉机器人对应access_token
    * atMobiles @具体人的手机号
    * isAtAll 是否@所有人 

* emailSetting 邮件告警配置 `存在则覆盖全局配置`
    * smtp 邮件服务器配置
        + smtpserver 邮件服务器地址
        + smtpuser 邮件服务器账号
        + smtppass 邮件服务器密码
	* mailto 接收告警的邮箱 多个以逗号隔开 


