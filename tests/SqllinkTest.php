<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use LinkMonitor\Helper\Logs;
use LinkMonitor\Link\SqlLink;
use LinkMonitor\Monitor\MemoryTable;
use PHPUnit\Framework\TestCase;

define('LINK_MONITOR_PATH', __DIR__ . '/..');

class SqllinkTest extends TestCase
{
    public $link = null;

    public function __construct()
    {
        $config                               = require LINK_MONITOR_PATH . '/config/config.php';
        $logger                               = Logs::getLogger($config['logPath'] ?? '', $config['logSaveFileApp'] ?? '');
        $memoryTable                          = new MemoryTable(4);
        $config['linkList'][2]['noticeTimes'] = 1; //重置测试用例告警次数
        $this->link                           = new SqlLink($config['linkList'][2], $memoryTable, $logger);
    }

    /**
     * 测试链接.
     */
    public function testConnection()
    {
        $this->assertTrue($this->link->checkConnection());
    }

    /**
     * 测试操作.
     */
    public function testOperation()
    {
        $this->assertTrue($this->link->checkOperation());
    }
}
