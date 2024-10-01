<?php

namespace App\Console\Commands;

use App\Models\StatServer;
use App\Models\StatUser;
use App\Services\StatisticalService;
use Illuminate\Console\Command;
use App\Models\Stat;
use Illuminate\Support\Facades\DB;

class V2boardStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计任务';

    protected $date;

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
        $this->date = date('Y-m-d');

        $startAt = microtime(true);
        ini_set('memory_limit', -1);
        $this->statUser();
        $this->statServer();
        $this->info('耗时' . (microtime(true) - $startAt));
    }

    private function statServer()
    {
        $createdAt = time();
        $recordAt = strtotime('-1 day', strtotime($this->date));
        $statService = new StatisticalService();
        $statService->setStartAt($recordAt);
        $statService->setServerStats();
        $stats = $statService->getStatServer();
        DB::beginTransaction();
        foreach ($stats as $stat) {

            $statServer = StatServer::where('server_id', $stat['server_id'])->where('record_at', $recordAt)->first();

            if(!$statServer){
                $result = StatServer::insert([
                    'server_id' => $stat['server_id'],
                    'server_type' => $stat['server_type'],
                    'u' => $stat['u'],
                    'd' => $stat['d'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'record_type' => 'd',
                    'record_at' => $recordAt
                ]);
            }else{
                $statServer->u += $stat['u'];
                $statServer->d += $stat['d'];
                $statServer->updated_at += $createdAt;
                $result = $statServer->save();
            }

            if (!$result) {
                DB::rollback();
                throw new \Exception('stat server fail');
            }
        }
        DB::commit();
        $statService->clearStatServer();
    }

    private function statUser()
    {
        $createdAt = time();
        $recordAt = strtotime('-1 day', strtotime($this->date));
        $statService = new StatisticalService();
        $statService->setStartAt($recordAt);
        $statService->setUserStats();
        $stats = $statService->getStatUser();
        DB::beginTransaction();
        foreach ($stats as $stat) {

            $statUser = StatUser::where('user_id', $stat['user_id'])->where('record_at', $recordAt)->first();

            if(!$statUser){
                $result = StatUser::insert([
                    'user_id' => $stat['user_id'],
                    'u' => $stat['u'],
                    'd' => $stat['d'],
                    'server_rate' => $stat['server_rate'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'record_type' => 'd',
                    'record_at' => $recordAt
                ]);
            }else{

                $statUser->u += $stat['u'];
                $statUser->d += $stat['d'];
                $statUser->updated_at += $createdAt;
                $result = $statUser->save();
            }

            if (!$result) {
                DB::rollback();
                throw new \Exception('stat user fail');
            }
        }
        DB::commit();
        $statService->clearStatUser();
    }

}
