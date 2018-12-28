<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use function GuzzleHttp\json_encode;

class GameController extends Controller
{

    /**
     * Start and store a new game/session
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Generate new code and secret code allowing Leader to publish game 

        $makeCode = function() {
            $code = md5( microtime(true) . time() . mt_rand(1,99999) );
            return strtoupper( substr($code, 5,6) );
        };
        
        
        $code = $makeCode();
        while( substr($code, 0, 1) === '0' || Redis::exists($code) ) {
            $code = $makeCode();
        }

        //Store empty game in Redis
        $gameData = [
            'secret' => sha1( mt_rand(1, 10*1000*1000) . microtime(true) . $_SERVER['REMOTE_ADDR'] ), //used by host to write
            'created' => time(),
            'clientData' => null, //the Finska client game state
        ];

        Redis::set($code, json_encode($gameData));
        Redis::lpush('active_games', $code);

        return [
            'code'   => $code,
            'secret' => $gameData['secret']
        ];
    }

    /**
     * Get JSON game state
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        if(!Redis::exists($code)) {
            return response()->json(['message' => 'Unknown game'], 404);
        }

        $data = json_decode(Redis::get($code));
        return $data->clientData;
    }



    /**
     * Update game state
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code)
    {
        //todo check these rules
        //max size etc.
        /*
        $request->validate([
            //'code' => 'size:6',
            'secret' => 'size:40',
            'state'  => 'min:20'
        ]); */

        //$code   = $request->get('code');
        $secret = $request->get('secret');

        if( !$state = json_decode($request->get('state')) ) {
            return response()->json(['message' => 'Invalid JSON game state'], 403);
        }

        if(!Redis::exists($code)) {
            return response()->json(['message' => 'Unknown game'], 404);
        }

        $gameData = json_decode( Redis::get($code) );
        if( !isset($gameData->secret) || $secret !== $gameData->secret ) {
            return response()->json(['message' => "You don't have access to update this game"], 401);
        }

        $gameData->clientData = $state;
        Redis::set($code, json_encode($gameData));

        return response()->json(['message' => 'Game updated'], 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //fuck shit up ya'll 
    }
}
