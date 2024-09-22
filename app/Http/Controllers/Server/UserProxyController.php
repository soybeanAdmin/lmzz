<?php

namespace App\Http\Controllers\Server;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

use App\Services\UserService;
use App\Services\ServerService;

use App\Utils\Helper;

class UserProxyController extends Controller
{
    private $nodeType;
    private $nodeInfo;
    private $nodeId;
    private $serverService;

    public function __construct(Request $request)
    {
        $token = $request->input('token');
        if (empty($token)) {
            abort(500, 'token is null');
        }

        if(!in_array($token, config('v2cfg.server_token'))){
            abort(500, 'token is error');
        }
    }

    public function push(Request $request)
    {

        $data = file_get_contents('php://input');
        $data = json_decode($data, true);

//        Cache::put(CacheKey::get('SERVER_' . strtoupper($this->nodeType) . '_ONLINE_USER', $this->nodeInfo->id), count($data), 3600);
//        Cache::put(CacheKey::get('SERVER_' . strtoupper($this->nodeType) . '_LAST_PUSH_AT', $this->nodeInfo->id), time(), 3600);
        $userService = new UserService();
        $userService->trafficFetch($this->nodeInfo->toArray(), $this->nodeType, $data);

        $this->pushNum(count($data), $this->nodeInfo->id);

        return response([
            'data' => true
        ]);
    }

    public function pushUser(Request $request){

        $uid = $request->input('user_id');

        $user = User::select(['id', 'uuid', 'speed_limit', 'group_id'])->where('id', $uid)->first()->toArray();

        $gid = $user['group_id'];
        unset($user['group_id']);

        Redis::zadd('server:group:'.$gid,$user['id'], json_encode($user));
        return response([
            'data' => true
        ]);
    }

}
