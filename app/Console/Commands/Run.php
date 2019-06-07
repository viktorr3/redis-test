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
        //Redis::connection('redis');



        while (1)
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

            // Check if my process generator
            $g=Redis::get('generator');



            if($g && $g!=$generatorKey) {
                $this->warn("Another active generator found! Switching to worker mode.");
                return;
            }

            // Set generator and update TTL
            Redis::setex('generator', $generatorTimeout, $generatorKey);


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
        while(1)
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
