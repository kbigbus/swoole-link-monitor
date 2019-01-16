<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class FactoryLink
{
    const LINK_TYPE_MQ    = 'mq';
    const LINK_TYPE_REDIS = 'redis';
    const LINK_TYPE_SQL   = 'sql';
    const LINK_TYPE_FPM   = 'fpm';

    protected static $linkObject = [];

    protected $logger = [];

    /**
     * 获取配置项.
     *
     * @param array $config
     */
    public function getConfig($config)
    {
        !$this->logger && $this->logger = Logs::getLogger($config['logPath'] ?? '');
    }

    /**
     * 获取链路对象
     *
     * @param array  $linkSetting 链路配置
     * @param object $memoryTable 共享内存对象
     */
    public function getLinkObject($linkSetting, $memoryTable)
    {
        if (!$linkSetting || !isset($linkSetting['linkType']) || !$linkSetting['linkType']) {
            return false;
        }
        $linkKey = md5(json_encode($linkSetting));
        if (isset(self::$linkObject[$linkKey]) && self::$linkObject[$linkKey]) {
            return self::$linkObject[$linkKey];
        }
        try {
            switch ($linkSetting['linkType']) {
                case self::LINK_TYPE_MQ:
                    self::$linkObject[$linkKey] = new MqLink($linkSetting, $memoryTable, $this->logger);
                    break;
                case self::LINK_TYPE_REDIS:
                    self::$linkObject[$linkKey] = new RedisLink($linkSetting, $memoryTable, $this->logger);
                    break;
                case self::LINK_TYPE_SQL:
                    self::$linkObject[$linkKey] = new SqlLink($linkSetting, $memoryTable, $this->logger);
                    break;
                case self::LINK_TYPE_FPM:
                    self::$linkObject[$linkKey] = new FpmLink($linkSetting, $memoryTable, $this->logger);
                    break;
                default:
                    // code...
                    return false;
                    break;
            }

            return self::$linkObject[$linkKey];
        } catch (\Exception $ex) {
            Utils::catchError($this->logger, $ex);

            return false;
        }
    }
}
