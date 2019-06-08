<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class Run extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        $this->info('Hi!');

        while (true)
        {
            if (!Redis::exists('generator')) $this->generator();
            else $this->worker();
        }
    }

    function generator()
    {

        $this->info("No generator found. Starting");
        $generatorKey=Str::uuid();
        $nextGenerationTime=Carbon::now();
        $interval=config('app.interval');
        $generatorTimeout=config('app.generator_timeout');

        while(true)
        {


            // Become generator if generator not present
            /* Синхронно, на стороне редис сервера посмотреть является ли текущий процесс генерпатором.
            Если да, то обновить ттл. Если  нет - попытаться стать генерератором. Но команда завершиться результатом NULL,
            если генератор уже есть (флаг NX). На редисе, в данный момент времени, может выполняться только один скрипт
                https://redis.io/commands/eval
            Пока скрипт выполняется, скрипты и команды от другого процесса буду в ожидании
            */

            $r=Redis::eval('if redis.call("get",KEYS[1]) == ARGV[1] then
		return redis.call("SET", KEYS[1], ARGV[1],  "PX", ARGV[2])
else
		return redis.call("SET", KEYS[1], ARGV[1],  "NX", "PX", ARGV[2])
end',2, 'generator', null, $generatorKey, $generatorTimeout*1000);

            if(!$r) {
                $this->info("Found other generator. Switching to worker mode.");
                return;
            }


            // Generate number every app.interval seconds

            if(Carbon::now()>$nextGenerationTime) {

                $number=mt_rand(0,10);
                Redis::rpush('numbers', $number );
                $this->info("Generator: pushed ".$number);
                $nextGenerationTime=$nextGenerationTime->addSeconds($interval);
            }

            sleep(1);

        }
    }

    function worker()
    {

        $wait=config('app.interval')+1;
        while(true)
        {
            list($record,$number)=Redis::blpop('numbers',$wait);
            if($record==null) {
                // таймаут получения значения. Вернуться в основной цикл, проверить жив ли генератор
                return;
            }
            if($number>8) {
                $this->info("ERROR! Received: ".$number." Added to error list.");
                Redis::rpush('errors',"Error! $number is more than 8");
            }
            else {
                $this->info("OK! Received: ".$number."; Increment counter.");
                Redis::incr('success');
            }
        }
    }
}
