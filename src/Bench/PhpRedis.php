<?php

/**
 * @summary phpredis bench
 *
 * @author sunnyw
 */
namespace sunnyw\RedisBench\Bench;

use Redis;

class PhpRedis
{
    private static $redis;

    public static function getInstance($redis_conf=[])
    {
        if(!isset(self::$redis)){
            self::$redis=new Redis();

            if(isset($redis_conf['socket']) && !empty($redis_conf['socket'])){
                self::$redis->connect($redis_conf['socket']);
            }
            else{
                self::$redis->connect($redis_conf['host'] ?: '127.0.0.1',
                    $redis_conf['port'] ?: '6379'
                );
            }
        }

        return self::$redis;
    }
}