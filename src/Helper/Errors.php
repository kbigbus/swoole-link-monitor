<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Helper;

class Errors
{
    const SETTING_ERROR_CODE    = 10001; //设置错误码
    const SETTING_ERROR_MESSAGE = 'wrong setting. ticker time or link or workernum'; //设置错误信息

    //钉钉消息相关
    const DINGDING_SEND_SETTING_ERROR_CODE    = 11002; //钉钉发送错误码
    const DINGDING_SEND_SETTING_ERROR_MESSAGE = 'access_token Or contentType Or content is empty';
    const DINGDING_SEND_RETURN_ERROR_CODE     = 11003; //钉钉发送结果错误码
    const DINGDING_SEND_RETURN_ERROR_MESSAGE  = 'send dingding message error msg';
}
