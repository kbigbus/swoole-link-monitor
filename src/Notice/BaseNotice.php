<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Notice;

use LinkMonitor\Helper\Logs;

class BaseNotice
{
    protected $logger = null;

    public function __construct(Logs $logger)
    {
    }

    public function setToken($setting)
    {
    }

    public function send($msg)
    {
    }
}
