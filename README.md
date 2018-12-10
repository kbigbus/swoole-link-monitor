# swoole-link-monitor

基于swoole timer/table/process(worker) 实现链路监控与告警

## 链路支持

> * rabbitmq
> * mysql  
> * redis  
> * api    开发ING
> * ... （待扩展）

## 告警支持

> * 钉钉告警
> * 邮件告警
> * ...  （待扩展）

## 架构图

![架构图](docs/images/architecture.png)

## 告警示例图

![钉钉告警](docs/images/dingding_notice.jpg)
![邮件告警](docs/images/email_notice.png)

## 安装要求

> * php >= 7.0
> * swoole >= 1.9.18
> * amqp >= 1.6.0
> * redis

## 安装方式
```
git clone https://github.com/kbigbus/swoole-link-monitor
cd swoole-link-monitor
composer install
```

## 配置调整

参见 config.md(待完善)

## 执行方式

> php link-monitor