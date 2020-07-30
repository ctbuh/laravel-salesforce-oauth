<?php

// https://www.converticacommerce.com/support-maintenance/security/php-one-liner-decode-jwt-json-web-tokens/
if (!function_exists('decode_jwt_token')) {

    function decode_jwt_token($token)
    {
        return json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))));
    }
}
