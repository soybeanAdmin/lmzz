<?php

namespace App\Services;

use App\Models\ServerHysteria;
use App\Models\ServerShadowsocks;
use App\Models\User;
use App\Models\ServerVmess;
use App\Models\ServerTrojan;
use App\Models\ServerV2ray;
use Illuminate\Support\Facades\Cache;

class ServerService
{

    public function getAvailableUsers($groupId)
    {
        return User::whereIn('group_id', $groupId)
            ->whereRaw('u + d < transfer_enable')
            ->where('banned', 0)
            ->select([
                'id',
                'uuid',
                'speed_limit'
            ])
            ->limit(10)
            ->get();
    }

    public function getServer($serverId, $serverType)
    {
        switch ($serverType) {
            case 'vmess':
                return ServerVmess::find($serverId);
            case 'shadowsocks':
                return ServerShadowsocks::find($serverId);
            case 'trojan':
                return ServerTrojan::find($serverId);
            case 'hysteria':
                return ServerHysteria::find($serverId);
            case 'v2ray':
                return ServerV2ray::find($serverId);
            default:
                return false;
        }
    }
}
