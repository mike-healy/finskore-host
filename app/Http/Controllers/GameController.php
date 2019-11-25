<?php

namespace App\Http\Controllers;

use App\Game\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

use function GuzzleHttp\json_encode;

class GameController extends Controller
{

    /*
    @jaaprood has suggested that there's no need to store the game data on the server at all.
    When updated it can just be pushed immediately to Ably as a persistent message.
    New clients subscribing to the channel will get the game state from ably.

    Saves having to Gargabe Collect here.

    Except the size might get too big at some point.
    I think I'll maintain an index of games and purge untouched ones after N hours.
    */
    
    /**
     * Start a new game session
     * todo: Prefix Redis store name, extract this code generation logic to its own class.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Generate new code and secret code allowing Leader to publish game
        //Todo: namespace or prefix the Redis store
        $code = Token::makePublic();
        while (Redis::exists($code)) {
            $code = Token::makePublic();
        }

        //Store empty game in Redis
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $gameData = [
            'secret'     => Token::makeSecret($ip),
            'created'    => time(),
            'clientData' => null, //the Finska client game state
        ];

        Redis::set($code, json_encode($gameData));
        Redis::lpush('active_games', $code);

        return [
            'code'   => $code,
            'secret' => $gameData['secret']
        ];

        /*
        Don't manipulate Redis directly from controller.
        Create a game object.

        Can you have a model that is not Eloquent.

        */
    }

    /**
     * Get JSON game state
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        if (!Redis::exists($code)) {
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

        $secret = $request->get('secret');

        if (!$state = json_decode($request->get('state'))) {
            return response()->json(['message' => 'Invalid JSON game state'], 403);
        }

        if (!Redis::exists($code)) {
            return response()->json(['message' => 'Unknown game'], 401);
        }

        $gameData = json_decode(Redis::get($code));
        if (!isset($gameData->secret) || $secret !== $gameData->secret) {
            return response()->json(['message' => 'Unknown game'], 401);
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
        //end game
    }
}
