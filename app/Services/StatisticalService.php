<?php
namespace App\Services;

use App\Models\CommissionLog;
use App\Models\Order;
use App\Models\Stat;
use App\Models\StatServer;
use App\Models\StatUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticalService {
    protected $userStats;
    protected $startAt;
    protected $endAt;
    protected $serverStats;

    public function __construct()
    {
        ini_set('memory_limit', -1);
    }

    public function setStartAt($timestamp) {
        $this->startAt = $timestamp;
    }

    public function setUserStats() {
        $this->userStats = Cache::get("stat_user_{$this->startAt}");
        $this->userStats = json_decode($this->userStats, true) ?? [];
        if (!is_array($this->userStats)) {
            $this->userStats = [];
        }
    }

    public function setServerStats() {
        $this->serverStats = Cache::get("stat_server_{$this->startAt}");
        $this->serverStats = json_decode($this->serverStats, true) ?? [];
        if (!is_array($this->serverStats)) {
            $this->serverStats = [];
        }
    }

    public function statServer($serverId, $serverType, $u, $d)
    {
        $this->serverStats[$serverType] = $this->serverStats[$serverType] ?? [];
        if (isset($this->serverStats[$serverType][$serverId])) {
            $this->serverStats[$serverType][$serverId][0] += $u;
            $this->serverStats[$serverType][$serverId][1] += $d;
        } else {
            $this->serverStats[$serverType][$serverId] = [$u, $d];
        }
        Cache::set("stat_server_{$this->startAt}", json_encode($this->serverStats));
    }

    public function statUser($rate, $userId, $u, $d)
    {
        $this->userStats[$rate] = $this->userStats[$rate] ?? [];
        if (isset($this->userStats[$rate][$userId])) {
            $this->userStats[$rate][$userId][0] += $u;
            $this->userStats[$rate][$userId][1] += $d;
        } else {
            $this->userStats[$rate][$userId] = [$u, $d];
        }
        Cache::set("stat_user_{$this->startAt}", json_encode($this->userStats));
    }

}
