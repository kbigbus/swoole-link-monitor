<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Helper;

class Errors
{
    //全局成功标识
    const SUCCESS_CODE    = 0;
    const SUCCESS_MESSAGE = 'all is well';

    const SETTING_ERROR_CODE    = 10001; //设置错误码
    const SETTING_ERROR_MESSAGE = 'wrong setting. ticker time or link or workernum'; //设置错误信息

    //钉钉消息相关
    const DINGDING_SEND_SETTING_ERROR_CODE    = 21002; //钉钉发送错误码
    const DINGDING_SEND_SETTING_ERROR_MESSAGE = 'access_token Or contentType Or content is empty';
    const DINGDING_SEND_RETURN_ERROR_CODE     = 21003; //钉钉发送结果错误码
    const DINGDING_SEND_RETURN_ERROR_MESSAGE  = 'send dingding message error msg';

    //邮件消息相关
    const EMAIL_SEND_SETTING_ERROR_CODE    = 22002; //邮件发送错误码
    const EMAIL_SEND_SETTING_ERROR_MESSAGE = 'email object Or contentType Or content is empty';
    const EMAIL_SEND_RETURN_ERROR_CODE     = 22003; //邮件发送结果错误码
    const EMAIL_SEND_RETURN_ERROR_MESSAGE  = 'send email message error msg';
}
