<?php

if (!function_exists('mb_ucfirst')) {

    function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = false) {
        $first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
        $str_end = "";
        if ($lower_str_end) {
            $str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
        } else {
            $str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
        }
        $str = $first_letter . $str_end;
        return $str;
    }

}

function sys_encrypt($string, $secret_key, $encrypt_method = "AES-256-CBC") {
    $ivlen = openssl_cipher_iv_length($encrypt_method);
    $iv = substr(md5($secret_key), 0, $ivlen);
    $key = hash('sha256', $secret_key);
    return base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
}

function sys_decrypt($string, $secret_key, $encrypt_method = "AES-256-CBC") {
    $ivlen = openssl_cipher_iv_length($encrypt_method);
    $iv = substr(md5($secret_key), 0, $ivlen);
    $key = hash('sha256', $secret_key);
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
}
