<?php


namespace App\Lib;


class Redis
{
    protected static $instance;
    protected static $config = [
        'host' => '49.234.47.239',
        'port' => 6973,
    ];

    /**
     * 获取redis实例
     *
     * @return \Redis|\RedisCluster
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            $instance = new \Redis();
            $instance->connect(
                self::$config['host'],
                self::$config['port']
            );
            self::$instance = $instance;
        }
        return self::$instance;
    }
}