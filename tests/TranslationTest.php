<?php
/**
 * Created by IntelliJ IDEA.
 * User: swang
 * Date: 2017-08-18
 * Time: 1:39 PM
 */

class TranslationTest extends \PHPUnit\Framework\TestCase
{
    public static $gct;
    public static $cache;
    public static $dynamoDB;

    /**
     * Set up
     */
    public static function setUpBeforeClass()
    {
        self::$gct = [
            'project' => getenv('GCT_PROJECT'),
            'key' => getenv('GCT_KEY'),
        ];


        self::$cache = [
            'host'     => 'redis',
            'timeout'  => '0.5',
            'port'     => '6379',
        ];

        self::$dynamoDB = [
            'endpoint' => 'http://dynamo:8000',
            "region" => "us-west-1",
            "table" => "translation",
            "version" => "2012-08-10",
        ];
    }

    /**
     * Test translation without namespace
     */
    public function testLiveTranslateWithoutNamespace()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $exclude = [];

        $translator = new \RW\Translation(null, $supportLangugaes, self::$cache, $defaultLang, self::$gct, $exclude);
        $this->assertSame("你好", $translator->translate('hello', "zh"));
    }

    /**
     * Test Batch
     */
    public function testLiveBetachTranslateWithoutNamespace()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $exclude = [];

        $texts = [
            'hello' => "Hello",
            "world" => "World",
        ];

        $translator = new \RW\Translation(null, $supportLangugaes, self::$cache, $defaultLang, self::$gct, $exclude);
        $translatedTexts = $translator->batchTranslate($texts, "zh");

        $this->assertSame(['hello' => "你好", "world" => "世界"], $translatedTexts);
    }
}