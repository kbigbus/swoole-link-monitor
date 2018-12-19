<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Monitor;

//内存缓存链路出错次数 达到阈值则上报
class MemoryTable
{
    const ERROR_NUM        = 'errorNum'; //出错次数
    const ERROR_START_TIME = 'startTime'; //开始时间
    const ERROR_END_TIME   = 'endTime'; //告警时间
    const COLUMN_NAME      = [
        self::ERROR_NUM        => '出错次数',
        self::ERROR_START_TIME => '开始时间',
        self::ERROR_END_TIME   => '告警时间',
    ];

    public static $staticTable = null;
    public $table              = null;

    public function __construct($linkCount = 5)
    {
        $this->table = $this->getTableInstance($linkCount);
    }

    /**
     * 获取单例对象
     *
     * @param mixed $linkCount
     */
    public function getTableInstance($linkCount)
    {
        if (self::$staticTable) {
            return self::$staticTable;
        }
        self::$staticTable = new \swoole_table($linkCount);
        self::$staticTable->column(self::ERROR_NUM, \swoole_table::TYPE_INT, 4); //出错次数
        self::$staticTable->column(self::ERROR_START_TIME, \swoole_table::TYPE_STRING, 20); //开始时间
        self::$staticTable->column(self::ERROR_END_TIME, \swoole_table::TYPE_STRING, 20); //告警时间
        self::$staticTable->create();

        return self::$staticTable;
    }

    /**
     * 设置出错开始时间.
     *
     * @param string $linkKey  标记链路唯一key
     * @param string $dateTime 开始时间 长度不能超过20
     */
    public function setStartTime($linkKey, $dateTime = '')
    {
        $dateTime = $dateTime ?: date('Y-m-d H:i:s');

        return $this->table->set($linkKey, [self::ERROR_START_TIME=>(string) $dateTime]);
    }

    /**
     * 设置出错结束时间.
     *
     * @param string $linkKey  标记链路唯一key
     * @param string $dateTime 开始时间 长度不能超过20
     */
    public function setEndTime($linkKey, $dateTime = '')
    {
        $dateTime = $dateTime ?: date('Y-m-d H:i:s');

        return $this->table->set($linkKey, [self::ERROR_END_TIME=>(string) $dateTime]);
    }

    /**
     * 原子递增出错次数.
     *
     * @param string $linkKey 标记各链路唯一key
     * @param int    $incrNum 递增值
     */
    public function incrErrorNum($linkKey, $incrNum = 1)
    {
        return $this->table->incr($linkKey, self::ERROR_NUM, $incrNum);
    }

    /**
     * 删除key  重置错误记录.
     *
     * @param string $linkKey 标记链路唯一key
     */
    public function delKey($linkKey)
    {
        return $this->table->del($linkKey);
    }

    /**
     * 获取key对应的数据存储  ['errorNum'=>11,'startTime'=>'xx', 'endTime'=>'xxx'].
     *
     * @param string $linkKey 标记链路唯一key
     */
    public function getKeyValue($linkKey)
    {
        return $this->table->get($linkKey);
    }

    /**
     * 删除全部key.
     */
    public function flushAll()
    {
        foreach ($this->table as $key=>$row) {
            $this->table->del($key);
        }
    }
}
