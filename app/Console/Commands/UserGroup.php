<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class UserGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:group {group_id=0}';

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
     * @return int
     */
    public function handle()
    {

//        $users = Redis::SMEMBERS('server:group:1');
//
//        var_dump($users);exit;

        $users = User::select(["id", "uuid", "speed_limit", "group_id"])->limit(100000)->get()->toArray();

        foreach($users as $item){

            $gid = $item['group_id'];

            if(!$gid) continue;

            unset($item['group_id']);

            Redis::sadd('server:group:'.$gid, json_encode($item));
        }

        return 0;
    }
}
