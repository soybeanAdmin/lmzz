<?php

namespace App\Http\Controllers\Server;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

use App\Services\UserService;
use App\Services\ServerService;

use App\Utils\Helper;

class UniProxyController extends Controller
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

        $this->nodeType = $request->input('node_type');
        if ($this->nodeType === 'v2ray') $this->nodeType = 'vmess';
        if ($this->nodeType === 'vless') $this->nodeType = 'vmess';
        $this->nodeId = $request->input('node_id');

        $this->serverService = new ServerService();
        $this->nodeInfo = $this->serverService->getServer($this->nodeId, $this->nodeType);

        if (!$this->nodeInfo) abort(500, 'server is not exist');
    }

    // 后端获取用户
    public function user(Request $request)
    {

        ini_set('memory_limit', -1);

        $response['users'] = [];

        foreach($this->nodeInfo->group_id as $gid){
            $users = Redis::ZRANGE('server:group:'. $gid, 0, -1);

            if(empty($users)) continue;

            foreach($users as $item){
                $response['users'][] = json_decode($item);
            }
        }

        $eTag = sha1(json_encode($response));
        if (strpos($request->header('If-None-Match'), $eTag) !== false ) {
            abort(304);
        }

        return response($response)->header('ETag', "\"{$eTag}\"");
    }

    // 后端提交数据
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

    private function pushNum($count, $node_id){

        $url = 'http://board.suyou.org/api/v1/server/deepbwork/submitPersonCount?token=a60a866d-97b4-411c-ae97-a208e1ed4f09&count=1&node_id=124';

        $client = new Client();

        $result = $client->request('post', $url, ['count' => $count, 'node_id' => $node_id]);

    }

    // 后端获取配置
    public function config(Request $request)
    {
        switch ($this->nodeType) {
            case 'shadowsocks':
                $response = [
                    'server_port' => $this->nodeInfo->server_port,
                    'cipher' => $this->nodeInfo->cipher,
                    'obfs' => $this->nodeInfo->obfs,
                    'obfs_settings' => $this->nodeInfo->obfs_settings
                ];

                if ($this->nodeInfo->cipher === '2022-blake3-aes-128-gcm') {
                    $response['server_key'] = Helper::getServerKey($this->nodeInfo->created_at, 16);
                }
                if ($this->nodeInfo->cipher === '2022-blake3-aes-256-gcm') {
                    $response['server_key'] = Helper::getServerKey($this->nodeInfo->created_at, 32);
                }
                break;
            case 'vmess':
                $response = [
                    'server_port' => $this->nodeInfo->server_port,
                    'network' => $this->nodeInfo->network,
                    'networkSettings' => $this->nodeInfo->networkSettings,
                    'tls' => $this->nodeInfo->tls
                ];
                break;
            case 'trojan':
                $response = [
                    'host' => $this->nodeInfo->host,
                    'server_port' => $this->nodeInfo->server_port,
                    'server_name' => $this->nodeInfo->server_name,
                ];
                break;
            case 'hysteria':
                $response = [
                    'host' => $this->nodeInfo->host,
                    'server_port' => $this->nodeInfo->server_port,
                    'server_name' => $this->nodeInfo->server_name,
                    'up_mbps' => $this->nodeInfo->up_mbps,
                    'down_mbps' => $this->nodeInfo->down_mbps,
                    'obfs' => Helper::getServerKey($this->nodeInfo->created_at, 16)
                ];
                break;
        }
        $response['base_config'] = [
            'push_interval' => (int)config('v2board.server_push_interval', 60),
            'pull_interval' => (int)config('v2board.server_pull_interval', 60)
        ];
        if ($this->nodeInfo['route_id']) {
            $response['routes'] = $this->serverService->getRoutes($this->nodeInfo['route_id']);
        }
        $eTag = sha1(json_encode($response));
        if (strpos($request->header('If-None-Match'), $eTag) !== false ) {
            abort(304);
        }

        return response($response)->header('ETag', "\"{$eTag}\"");
    }
}
