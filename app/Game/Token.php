<?php

namespace App\Game;

class Token
{
    public static function makePublic($len = 6)
    {
        $code = md5(microtime(true) . time() . mt_rand(1, 99999));
        $code = strtoupper(substr($code, 5, $len));

        //hmm, could nest function tree with recursion, however
        //0.024% chance of 3 levels deep
        //0.0015% change of 4 levels
        while (substr($code, 0, 1) === '0') {
            $code = Token::makePublic($len);
        }

        return $code;
    }

    /*
    $isInvalidCode = function() {
    todo this
        starts with 0, has three consecutive alpha characters, or is already in the Redis store
    };
    */


    public static function makeSecret($ip = null)
    {
        $ip = $ip ?? 'dont-have-one';
        return sha1(mt_rand(1, 10 * 1000 * 1000) . microtime(true) .  $ip);
    }
}
