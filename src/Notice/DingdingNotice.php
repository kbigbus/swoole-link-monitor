<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Notice;

use LinkMonitor\Helper\Errors;
use LinkMonitor\Helper\Logs;
use LinkMonitor\Helper\Utils;

class DingdingNotice extends BaseNotice
{
    public $token       = ''; //配置token
    public $atMobiles   = []; //@特定的手机号列表
    public $isAtAll     = false; //是否@全部用户
    public $contentType = 'text'; //发送内容类型 默认text
    public $content     = ''; //发送内容 默认文本

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
        $this->token     = $setting['access_token'] ?? '';
        $this->atMobiles = $setting['atMobiles'] ?? [];
        $this->isAtAll   = $setting['isAtAll'] ?? false;
    }

    /**
     * 设置推送消息类型.
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
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function send()
    {
        if (!$this->token || !$this->contentType || !$this->content) {
            return ['errcode'=>Errors::DINGDING_SEND_SETTING_ERROR_CODE, 'errmsg'=>Errors::DINGDING_SEND_SETTING_ERROR_MESSAGE];
        }
        try {
            $message      = [
                'msgtype' => $this->contentType,
                'text'    => ['content' => $this->content], 'at' => ['atMobiles' => $this->atMobiles, 'isAtAll' => $this->isAtAll],
            ];
            $apiUrl       = 'https://oapi.dingtalk.com/robot/send?access_token=' . $this->token;
            $client       = new \GuzzleHttp\Client();
            $res          = $client->request('POST', $apiUrl, ['json' => $message, 'timeout' => 5]);
            $httpCode     =$res->getStatusCode();
            $body         =$res->getBody();
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
            $body = ['errcode'=>Errors::DINGDING_SEND_RETURN_ERROR_CODE, 'errmsg'=>$e->getMessage()];
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
            $body = ['errcode'=>Errors::DINGDING_SEND_RETURN_ERROR_CODE, 'errmsg'=>$e->getMessage()];
        }

        $this->logger->log('[钉钉接口]请求自定义机器人消息接口,请求地址：' . json_encode($apiUrl) . ',请求参数:' . json_encode($message) . ',返回结果:' . $body . '  httpcode: ' . $httpCode, 'info');

        return $body;
    }
}
