<?php

use Illuminate\Http\Request;
use App\Events\TestEvent;
use App\Events\MoveDone;
use App\Events\Losed;
use App\GameData;
use App\User;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('startup', function (Request $request) {
    if(User::where('room', $request->token)->count() < 2){
        if(!User::where('name', $request->userName)->where('room', $request->token)->exists()){
            $user = new User;
            $user->name = $request->userName;
            $user->room = $request->token;
            $user->save();
        }
    }
    $usersInRoom = User::where('room', $request->token)->get();
    $gameData = GameData::where('room', $request->token)->get();
    if($usersInRoom->count() > 1){
        if($gameData->count() < 1){
            broadcast(new TestEvent($request->token, $usersInRoom->first()->name));
        } else {
            return $gameData;
        }
    }
    return 0;
});

Route::post('getGameData', function (Request $request) {
    return 1;
});

Route::post('moveDone', function (Request $request) {
    $gameData = new GameData;
    $gameData->room = $request->token;
    $gameData->user = $request->user;
    $gameData->x = $request->x;
    $gameData->y = $request->y;
    $gameData->color = $request->color;
    $gameData->save();

    broadcast(new MoveDone($request->token, $request->x, $request->y, $request->color))->toOthers();
    return 0;
});

Route::post('win', function (Request $request) {
    GameData::where('room', $request->token)->delete();
    User::where('room', $request->token)->delete();
    broadcast(new Losed($request->token))->toOthers();
    return 0;
});
