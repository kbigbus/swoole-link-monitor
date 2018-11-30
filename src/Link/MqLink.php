<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class MqLink extends BaseLink
{
    public $connection              = false; //链接对象成员
    protected static $staticConnect = []; //静态资源

    public function __construct($link, Logs $logger)
    {
        $this->logger      = $logger;
        $this->linkSetting = $link;
        if (!isset($this->linkSetting['connectSetting']) || !$this->linkSetting['connectSetting']) {
            $this->logger->log('link setting error', 'info', Logs::LEVEL_ERROR);

            return false;
        }

        $this->connection = $this->getConnection();
    }

    //单例模式获取链接对象
    public function getConnection()
    {
        $linkSetting = $this->linkSetting['connectSetting'];
        if (isset(self::$staticConnect[$linkSetting['host']]) && null !== self::$staticConnect[$linkSetting['host']]) {
            return self::$staticConnect[$linkSetting['host']];
        }
        $class = class_exists('\AMQPConnection', false);
        if ($class) {
            try {
                self::$staticConnect[$linkSetting['host']] = new \AMQPConnection();
                self::$staticConnect[$linkSetting['host']]->setHost($linkSetting['host']);
                self::$staticConnect[$linkSetting['host']]->setLogin($linkSetting['user']);
                self::$staticConnect[$linkSetting['host']]->setPassword($linkSetting['pass']);
                self::$staticConnect[$linkSetting['host']]->setPort($linkSetting['port']);
                self::$staticConnect[$linkSetting['host']]->setVhost($linkSetting['vhost']);
                self::$staticConnect[$linkSetting['host']]->connect();

                return self::$staticConnect[$linkSetting['host']];
            } catch (\AMQPConnectionException $ex) {
                Utils::catchError($this->logger, $ex);

                return false;
            } catch (\Throwable $ex) {
                Utils::catchError($this->logger, $ex);

                return false;
            } catch (\Exception $ex) {
                Utils::catchError($this->logger, $ex);

                return false;
            }
        } else {
            $this->logger->log('you need install pecl amqp extension', 'info', Logs::LEVEL_ERROR);

            return false;
        }
    }

    /**
     * 检查链路链接是否成功
     *
     * @return bool 返回检查结果  true成功  false失败
     */
    public function checkConnection(): bool
    {
        return $this->connection ? true : false;
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
