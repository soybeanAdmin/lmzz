<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'prefix' => '/v1',
//    'middleware' => '',
    'namespace' => 'App\Http\Controllers\\'
], function () {

    Route::group([
        'prefix' => '/server',
//        'middleware' => ''
    ], function ($router) {

        $router->get('/UniProxy/user', 'Server\\UniProxyController@user');
        $router->post('/UniProxy/push', 'Server\\UniProxyController@push');
        $router->get('/UniProxy/config', 'Server\\UniProxyController@config');

        $router->post('/UniProxy/pushUser', 'Server\\UserProxyController@pushUser');

    });

});
