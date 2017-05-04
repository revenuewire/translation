<?php
namespace RW;

/**
 * Created by IntelliJ IDEA.
 * User: swang
 * Date: 2017-03-10
 * Time: 10:55 AM
 */
class Utils
{
    /**
     * slugify a given string, It was taken from symfony's jobeet tutorial.
     *
     * @param $text
     * @return mixed|string
     */
    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return '';
        }

        return $text;
    }
}