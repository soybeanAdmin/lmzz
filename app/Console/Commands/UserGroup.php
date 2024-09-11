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

        $users = User::select(['id', 'uuid', 'speed_limit', 'group_id'])->limit(100)->get()->toArray();

        foreach($users as $item){

            $gid = $item['group_id'];

            if(!$gid) continue;

            unset($item['group_id']);

            Redis::zadd('server:group:'.$gid, $item['id'],json_encode($item));
        }

        exit;

        $group_id = $this->argument('group_id');

        $userModel = User::whereRaw("(u + d) < transfer_enable");

        if ($group_id != 0){
            $userModel->where('group_id', $group_id);
        }

        $count = $userModel->count();

        $index = 0;
        $limit = 100000;

        $len = (int)ceil($count / $limit);

        for($i = 0; $i < $len; $i ++){

            $users = $userModel->limit($index, $limit)->get()->toArray();

            foreach($users as $item){

                $gid = $item['group_id'];

                if(!$gid) continue;

                unset($item['group_id']);

                Redis::zadd('server:group:'.$gid, json_encode($item));
            }

            $index = $limit * ($i + 1);
        }

        return 0;
    }
}
