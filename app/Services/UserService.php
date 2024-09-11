<?php

namespace App\Services;

use App\Jobs\StatServerJob;
use App\Jobs\StatUserJob;
use App\Jobs\TrafficFetchJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;

class UserService
{
    public function trafficFetch(array $server, string $protocol, array $data)
    {
        $statService = new StatisticalService();
        $statService->setStartAt(strtotime(date('Y-m-d')));
        $statService->setUserStats();
        $statService->setServerStats();
        foreach (array_keys($data) as $userId) {
            $u = $data[$userId][0];
            $d = $data[$userId][1];
            TrafficFetchJob::dispatch($u, $d, $userId, $server, $protocol);
            $statService->statServer($server['id'], $protocol, $u, $d);
            $statService->statUser($server['rate'], $userId, $u, $d);
        }
    }
}
