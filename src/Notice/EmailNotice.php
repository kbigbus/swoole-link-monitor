<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Notice;

use LinkMonitor\Helper\Email;
use LinkMonitor\Helper\Errors;
use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class EmailNotice extends BaseNotice
{
    public $contentType = 'HTML'; //发送内容类型 默认HTML //邮件格式（HTML/TXT）,TXT为文本邮件. 139邮箱的短信提醒要设置为HTML才正常
    public $content     = ''; //发送内容 默认文本
    public $smtpSetting = []; //smtp邮件服务器配置
    public $mailTo      = ''; //接收告警的用户
    public $mailSub     = 'LinkMonitor邮件告警'; //邮件主题
    public $emailObj    = null; //邮件对象

    public static $emailInstance = null;

    public function __construct(Logs $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 初始化设置.
     *
     * @param array $setting
     */
    public function setToken($setting)
    {
        $this->smtpSetting = $setting['smtp'] ?? [];
        $this->mailTo      = $setting['mailto'] ?? '';
        $this->emailObj    = $this->getEmailInstance();
    }

    /**
     * 获取邮件单例对象
     */
    public function getEmailInstance()
    {
        if (!$this->smtpSetting || !isset($this->smtpSetting['smtpserver']) || !$this->smtpSetting['smtpserver']) {
            return false;
        }
        $mailKey = md5(json_encode($this->smtpSetting));
        if (isset(self::$emailInstance[$mailKey]) && self::$emailInstance[$mailKey]) {
            return self::$emailInstance[$mailKey];
        }
        self::$emailInstance[$mailKey] = new Email($this->smtpSetting['smtpserver'], $this->smtpSetting['smtpport'] ?? 25, true, $this->smtpSetting['smtpuser'] ?? '', $this->smtpSetting['smtppass'] ?? '', $this->smtpSetting['logfile'] ?? '');

        return self::$emailInstance[$mailKey];
    }

    /**
     * 设置邮件主题.
     *
     * @param mixed $mailSub
     */
    public function setMailSub($mailSub)
    {
        $this->mailSub = $mailSub;
    }

    /**
     * 设置邮件内容类型.
     *
     * @param string $type
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
    }

    /**
     * 设置推送内容.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = str_ireplace(["\n", "\n\r", "\r\n"], '<br>', $content);
    }

    public function send()
    {
        if (!$this->emailObj || !$this->contentType || !$this->content || !$this->mailTo) {
            return ['errcode'=>Errors::EMAIL_SEND_SETTING_ERROR_CODE, 'errmsg'=>Errors::EMAIL_SEND_SETTING_ERROR_MESSAGE];
        }
        try {
            $mailsubject = '=?UTF-8?B?' . base64_encode($this->mailSub) . '?='; //防止乱码  邮件主题
            $mailRet     = $this->emailObj->sendmail($this->mailTo, $this->smtpSetting['smtpuser'], $mailsubject, $this->content, $this->contentType);
            if ($mailRet) {
                $body = ['errcode'=>Errors::SUCCESS_CODE, 'errmsg'=>Errors::SUCCESS_MESSAGE];
            } else {
                $body = ['errcode'=>Errors::EMAIL_SEND_RETURN_ERROR_CODE, 'errmsg'=>Errors::EMAIL_SEND_RETURN_ERROR_MESSAGE];
            }
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
            $body = ['errcode'=>Errors::EMAIL_SEND_RETURN_ERROR_CODE, 'errmsg'=>$e->getMessage()];
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
            $body = ['errcode'=>Errors::EMAIL_SEND_RETURN_ERROR_CODE, 'errmsg'=>$e->getMessage()];
        }

        $this->logger->log('[告警服务]请求邮件发送告警,告警人：' . json_encode($this->mailTo) . ',告警内容:' . json_encode($this->content) . ',返回结果:' . json_encode($body), 'info');

        return $body;
    }
}
