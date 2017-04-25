<?php
/**
 * Summary
 * Description
 * @package
 * @author    Wang Xi <iwisunny@gmail.com>
 * @copyright (C) 2017 Wang Xi. All rights reserved.
 * @version 0.1
 * Date 17-4-25
 */

namespace sunnyw\RedisBench;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use InvalidArgumentException;
use sunnyw\RedisBench\ConsoleException;
use sunnyw\RedisBench\Bench\Predis;
use sunnyw\RedisBench\Bench\PhpRedis;

class Console extends Command
{
    /**
     * @var array
     */
    protected $redisConf=[];

    protected $redis;

    protected function configure()
    {
        $this->setName("redis-bench")
            ->setDescription("redis benchmark runner written in php")
            ->setHelp(<<<EOT
Benchmark different php redis library, show their I/O performance

Usage:

<info>./redis-bench -t set,lpush -N 100000</info>

You can also specify redis-server's host, port, or even use unix socket
<info>./redis-bench -H 10.0.0.1 -P 6380 -N 10000</info>
<info>./redis-bench -S /tmp/redis-node-1.sock -N 10000</info>

You can read connection from external config file
<info>./redis-bench -C /path/to/redis-conf.php</info>

EOT
            );

        $this->setDefinition(new InputDefinition([
            new InputArgument('bench_name', InputArgument::OPTIONAL, 'run bench name: [predis|phpredis]', 'predis'),
//            new InputOption('fast_iteration', 'f', InputOption::VALUE_OPTIONAL, '', 10000),
//            new InputOption('slow_iteration', 's', InputOption::VALUE_OPTIONAL, '', 5000),
            new InputOption('host', 'H', InputOption::VALUE_OPTIONAL, 'server host', '127.0.0.1'),
            new InputOption('port', 'P', InputOption::VALUE_OPTIONAL, 'server port', '6379'),
            new InputOption('socket', 'S', InputOption::VALUE_OPTIONAL, 'unix socket, this will override host, port'),
            new InputOption('conf_file', 'C', InputOption::VALUE_OPTIONAL, 'read from config file'),
            new InputOption('db_idx', 'D', InputOption::VALUE_OPTIONAL, 'select the specified db number', 1),
            new InputOption('req', 'N', InputOption::VALUE_OPTIONAL, 'total number of requests', 10000),
            new InputOption('tests', 't', InputOption::VALUE_OPTIONAL, 'only run the comma separated list of tests, like: set,rpush', 'set'),

        ]));

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bench_name=$input->getArgument('bench_name');
        $host=$input->getOption('host');
        $port=$input->getOption('port');
        $socket=$input->getOption('socket');
        $conf_file=$input->getOption('conf_file');
        $db_idx=$input->getOption('db_idx');
        $req=$input->getOption('req');
        $tests=$input->getOption('tests');
        $verbose=$input->getOption('verbose');

        if(file_exists($external_conf_file=__DIR__.'/../redis-conf.php')){
            $external_conf=require $external_conf_file;
            $this->redisConf=array_merge($this->redisConf, $external_conf);
        }

        if(!empty($conf_file) && file_exists($conf_file)){
            $conf_file_cont=require $conf_file;
            $this->redisConf=array_merge($this->redisConf, $conf_file_cont);
        }

        if(!empty($host)){
            $this->redisConf['host']=$host;
        }
        if(!empty($port)){
            $this->redisConf['port']=$port;
        }
        if(!empty($socket)){
            $this->redisConf['socket']=$socket;
        }
        if(!empty($db_idx)){
            $this->redisConf['db']=$db_idx;
        }

        if($verbose){
            $output->writeln('<info>redis config</info>');
            print_r($this->redisConf);

            $output->writeln('<info>using '. $bench_name.' to bench</info>');
        }

        if(!in_array($bench_name, ['predis','phpredis'])){
            throw new ConsoleException('invalid bench_name, accept: predis|phpredis, '.$bench_name. ' given');
        }

        switch($bench_name)
        {
            case 'predis':
                $this->redis=Predis::getInstance($this->redisConf);
                break;
            case 'phpredis':
                $this->redis=PhpRedis::getInstance($this->redisConf);
                break;
            default:
                //todo
                throw new ConsoleException('unknown redis library');
                break;
        }

        //normalize tests
        $test_parts=array_map('trim', explode(',', $tests));

        //begin benchmark
        $this->redis->select($db_idx);
        $this->redis->flushdb();    //every time run bench, flush current db

        $start_time = microtime(true);

        foreach($test_parts as $test){
            $start_t=microtime(true);

            $bench_suit="[$bench_name:$test]";
            if($verbose){
                $output->writeln("<info>benchmarking for {$bench_suit}</info>");
            }

            for($i=0; $i<$req; $i++){
                if($test=='set'){
                    $this->redis->set($i, 'foo'.$i);
                }

                if(in_array($test, ['lpush','rpush'])){
                    $this->redis->$test('test_list', 'bar'.$i);
                }

                if (!($i % 1000)){
                    echo ".";
                }
            }

            $end_t = microtime(true);

            $output->writeln('<info>'. sprintf("\n$bench_suit completed in %.3f seconds with %ld requests, RPS=%ld\n",
                $end_t-$start_t, $req, floor($req/($end_t-$start_t))). '</info>' );
        }

        $end_time=microtime(true);

        $output->writeln('<comment>'. sprintf("$bench_name: Tests completed in %.3f seconds, %.2f MB peak memory usage\n",
                $end_time-$start_time, memory_get_peak_usage(true)/1000000). '</comment>' );


    }
}