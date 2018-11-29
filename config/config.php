<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    'tickerTime' => 3, //每3秒一次轮训
    'workerNum'  => 2, //worker数量
    'logPath'    => LINK_MONITOR_PATH . '/runtime/',
    'linkList'   => require 'link.php',
    'notice'     => require 'notice.php',
];
