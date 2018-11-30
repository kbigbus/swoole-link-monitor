<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Link;

use LinkMonitor\Helper\Logs;

class FactoryLink
{
    const LINK_TYPE_MQ    = 'mq';
    const LINK_TYPE_REDIS = 'redis';

    protected $linkSetting = [];

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
     * @param array $linkSetting
     */
    public function getLinkObject($linkSetting)
    {
        if (!$linkSetting || !isset($linkSetting['linkType']) || !$linkSetting['linkType']) {
            return false;
        }
        $linkKey = md5(json_encode($linkSetting));
        if (isset(self::$linkObject[$linkKey]) && self::$linkObject[$linkKey]) {
            return self::$linkObject[$linkKey];
        }
        switch ($linkSetting['linkType']) {
            case self::LINK_TYPE_MQ:
                self::$linkObject[$linkKey] = new MqLink($linkSetting, $this->logger);
                break;

            default:
                // code...
                break;
        }

        return self::$linkObject[$linkKey];
    }
}
