<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Notice;

use LinkMonitor\Helper\Logs;

class DingdingNotice extends BaseNotice
{
    public function __construct(Logs $logger)
    {
        $this->logger = $logger;
    }

    public function setToken($setting)
    {
    }

    public function send()
    {
    }

    public function sendDingding()
    {
        if (!$token || !$content) {
            return false;
        }
        try {
            $message      = ['msgtype' => 'text', 'text' => ['content' => $content], 'at' => ['atMobiles' => [], 'isAtAll' => false]];
            $apiUrl       = $this->apiUrl . '?access_token=' . $token;
            $client       = new \GuzzleHttp\Client();
            $res          = $client->request('POST', $apiUrl, ['json' => $message, 'timeout' => 5]);
            $httpCode     =$res->getStatusCode();
            $body         =$res->getBody();
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }

        $this->logger->log('[钉钉接口]请求自定义机器人消息接口,请求地址：' . json_encode($apiUrl) . ',请求参数:' . json_encode($message) . ',返回结果:' . $body . '  httpcode: ' . $httpCode, 'info');

        return $body;
    }
}
