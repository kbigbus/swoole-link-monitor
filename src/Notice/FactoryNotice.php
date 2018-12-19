<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Notice;

use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class FactoryNotice
{
    const NOTICE_TYPE_DINGDING = 1; //钉钉提醒
    const NOTICE_TYPE_EMAIL    = 2; //邮件提醒

    public static $noticeObject = [];

    protected $logger = [];

    protected $dingdingSetting = [];
    protected $emailSetting    = [];
    protected $noticeType      = 1;

    /**
     * 获取配置项.
     *
     * @param array $config 全局配置
     */
    public function getConfig($config)
    {
        !$this->logger && $this->logger = Logs::getLogger($config['logPath'] ?? '');
        $this->noticeType               = $config['noticeType'] ?? 1; //获取全局告警配置
        $this->dingdingSetting          = $config['dingdingSetting'] ?? []; //获取全局钉钉配置
        $this->emailSetting             = $config['emailSetting'] ?? []; //获取全局邮件配置
    }

    /**
     * 获取告警对象
     *
     * @param array $noticeConfig 链路告警配置
     */
    public function getNoticeObject($noticeConfig)
    {
        if (!$noticeConfig) {
            return false;
        }
        //获取链路告警配置
        if (isset($noticeConfig['noticeType'])) {
            $this->noticeType = $noticeConfig['noticeType'];
        }
        if (isset($noticeConfig['dingdingSetting'])) {
            $this->dingdingSetting = $noticeConfig['dingdingSetting'];
        }
        if (isset($noticeConfig['emailSetting'])) {
            $this->emailSetting = $noticeConfig['emailSetting'];
        }
        $retObj = false;
        try {
            switch ($this->noticeType) {
                case self::NOTICE_TYPE_DINGDING://钉钉提醒
                    if ($this->dingdingSetting) {
                        //调用钉钉接口推送
                        $retObj = $this->getInstanceObj();
                        if ($retObj) {
                            $retObj->setToken($this->dingdingSetting);
                        }
                    } else {
                        //不存在配置
                        $this->logger->errorLog('has no dingding setting, noticeConfig:' . json_encode($noticeConfig));
                    }
                break;
                case self::NOTICE_TYPE_EMAIL://邮件提醒
                    if ($this->emailSetting) {
                        //调用邮件推送
                        $retObj = $this->getInstanceObj();
                        if ($retObj) {
                            $retObj->setToken($this->emailSetting);
                        }
                    } else {
                        //不存在配置
                        $this->logger->errorLog('has no email setting, noticeConfig:' . json_encode($noticeConfig));
                    }

                break;
            }

            return $retObj;
        } catch (\Exception $ex) {
            Utils::catchError($this->logger, $ex);

            return false;
        }
    }

    //获取单例对象
    public function getInstanceObj()
    {
        $noticeKey = md5($this->noticeType);
        if (isset(self::$noticeObject[$noticeKey]) && self::$noticeObject[$noticeKey]) {
            return self::$noticeObject[$noticeKey];
        }
        switch ($this->noticeType) {
            case self::NOTICE_TYPE_DINGDING:
                self::$noticeObject[$noticeKey] = new DingdingNotice($this->logger);
                break;
           case self::NOTICE_TYPE_EMAIL:
                self::$noticeObject[$noticeKey] = new EmailNotice($this->logger);
                break;
        }

        return self::$noticeObject[$noticeKey];
    }
}
