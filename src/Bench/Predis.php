<?php
/**
 * @summary predis bench
 *
 * @author sunnyw
 */
namespace sunnyw\RedisBench\Bench;

use Predis\Client;

class Predis
{
    private static $redis;

    public static function getInstance($redis_conf=[])
    {
        if(!isset(self::$redis)){
            if(isset($redis_conf['socket']) && !empty($redis_conf['socket'])){
                self::$redis=new Client($redis_conf['socket']);
            }
            else{
                self::$redis=new Client([
                    'scheme'=> 'tcp',
                    'host'=> $redis_conf['host'] ?: '127.0.0.1',
                    'port'=> $redis_conf['port'] ?: '6379'
                ]);
            }
        }

        return self::$redis;
    }

}