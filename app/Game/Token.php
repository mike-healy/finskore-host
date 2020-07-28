<?php

namespace App\Game;

class Token
{
    public static function makePublicCode($len = 5)
    {
        $code = md5(microtime(true) . time() . mt_rand(1, 99999));
        $code = strtoupper(substr($code, 5, $len));

        while (substr($code, 0, 1) === '0') {
            $code = Token::makePublicCode($len); //move to ! ::isValidCode()
        }

        return $code;
    }

    public static function isValidCode($code)
    {
        //todo 
        //invalid if begins with zero, has three consecutive alpha characters, or is already in the Redis store
    }
    
    public static function makeSecret($ip = null)
    {
        $ip = $ip ?? 'dont-have-one';
        return sha1(mt_rand(1, 10*1000*1000) . microtime(true) .  $ip);
    }
}
