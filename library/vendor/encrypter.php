<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (08/21/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor;

class Encrypter
{

    public static function encrypt($input, $key)
    {
        $output = base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                md5($key),
                $input,
                MCRYPT_MODE_CBC,
                md5(md5($key))
            )
        );

        return $output;
    }

    public static function decrypt($input, $key)
    {
        $output = rtrim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                md5($key),
                base64_decode($input),
                MCRYPT_MODE_CBC,
                md5(md5($key))
            ),
            "\0"
        );

        return $output;
    }

}
