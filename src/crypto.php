<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP 
 */
namespace ShadPHP;

class crypto
{
    /** 
     * AES_256_CBC encrypt
     * @param string $text text to encrypt
     * @param string $key key of encrypt
     * @return string encrypted text
     */
    public static function aes_256_cbc_encrypt(string $text, string $key)
    {
        return base64_encode(openssl_encrypt($text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, str_repeat(chr(0x0), 16)));
    }

    /** 
     * AES_256_CBC encrypt
     * @param string $text text to decrypt
     * @param string $key key of decrypt
     * @return string decrypted text
     */
    public static function aes_256_cbc_decrypt(string $text, string $key)
    {
        return openssl_decrypt(base64_decode($text), 'aes-256-cbc', $key, OPENSSL_RAW_DATA);
    }

    /** 
     * shift text and create new text
     * @param string $text main text
     * @return string shifted text
     */
    public static function createSecretPassphrase(string $text)
    {
        $t = mb_substr($text, 0, 8);
        $i = mb_substr($text, 8, 8);
        $n = mb_substr($text, 16, 8) . $t . mb_substr($text, 24, 8) . $i;

        for ($s = 0; $s < mb_strlen($n); $s++) {
            $e = $n[$s];
            if ($e >= "0" && $e <= "9") {
                $char = ((((mb_ord($e[0]) - 48) + 5) % 10) + 48);
            } else {
                $char = ((((mb_ord($e[0]) - 97) + 9) % 26) + 97);
            }
            $t = mb_chr($char);
            $n = self::replaceCharAt($n, $s, $t);
        }
        return $n;
    }

    /** 
     * @param string $text main text
     * @param int $position char position
     * @param string $newChar new character for replace
     * @return string replaced text
     */
    public static function replaceCharAt(string $text, int $position, string $newChar)
    {
        $text[$position] = $newChar;
        return $text;
    }

    /** 
     * generate random string
     * @param int $length random string length
     * @return string generated random string
     */
    public static function azRand(int $length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, (strlen($chars) - 1))];
        }
        return $result;
    }
}
