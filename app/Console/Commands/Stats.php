<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class Stats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats';

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
        $errors=Redis::lrange('errors',0,-1);
        $success=Redis::get('success');

        $this->info("SUCCESS:". intval($success));
        $this->info("ERROR MESSAGES: ");
        foreach ($errors as $error) {
            echo("$error\n");
        }

        //print_r($r); die;
        //
    }
}
