<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use LinkMonitor\Helper\Errors;
use LinkMonitor\Helper\Logs;
use LinkMonitor\Notice\DingdingNotice;
use PHPUnit\Framework\TestCase;

define('LINK_MONITOR_PATH', __DIR__ . '/..');

class DingdingNoticeTest extends TestCase
{
    public $notice = null;

    public function __construct()
    {
        $config       = require LINK_MONITOR_PATH . '/config/config.php';
        $logger       = Logs::getLogger($config['logPath'] ?? '', $config['logSaveFileApp'] ?? '');
        $this->notice = new DingdingNotice($logger);
        $this->notice->setToken($config['dingdingSetting']);
        $this->notice->setContent('phpunit test');
    }

    /**
     * 测试发送.
     */
    public function testSend()
    {
        $ret = $this->notice->send();
        $this->assertSame($ret['errcode'], Errors::SUCCESS_CODE);
    }
}
