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

    /**
     * Set up
     */
    public static function setUpBeforeClass()
    {
        self::$gct = [
            'project' => getenv('GCT_PROJECT'),
            'key' => getenv('GCT_KEY'),
        ];
    }

    /**
     * Test translation without namespace
     */
    public function testLiveTranslateWithoutNamespace()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $translator = new \RW\Translation($supportLangugaes, $defaultLang, self::$gct);
        $this->assertSame("你好", $translator->translate('hello', "zh"));
    }

    /**
     * translation default language
     */
    public function testTranslateDefault()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $translator = new \RW\Translation($supportLangugaes, $defaultLang, self::$gct);
        $this->assertSame("hello world", $translator->translate("hello world"));
    }

    /**
     * translation unsupported language
     */
    public function testTranslateUnsupportedLaguage()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $translator = new \RW\Translation($supportLangugaes, $defaultLang, self::$gct);
        $this->assertSame("", $translator->translate("", "zh"));
    }

    /**
     * translation unsupported language
     */
    public function testTranslateEmptyText()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $translator = new \RW\Translation($supportLangugaes, $defaultLang, self::$gct);
        $this->assertSame("hello world", $translator->translate("hello world", "vi"));
    }

    /**
     * Test Batch
     */
    public function testLiveBatchTranslateWithoutNamespace()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";
        $texts = [
            'hello' => "Hello",
            "world" => "World",
        ];

        $translator = new \RW\Translation($supportLangugaes, $defaultLang, self::$gct);
        $translatedTexts = $translator->batchTranslate($texts, "zh");
        $this->assertSame(['hello' => "你好", "world" => "世界"], $translatedTexts);
    }

    /**
     * Test Batch
     */
    public function testLiveBatchTranslateWithNamespace()
    {
        $supportLangugaes = ["en", "zh"];
        $defaultLang = "en";

        $texts = [
            'hello' => "Hello",
            "world" => "World",
        ];

        $translator = new \RW\Translation($supportLangugaes, $defaultLang, self::$gct, "unittest1");
        $translatedTexts = $translator->batchTranslate($texts, "zh");

        $this->assertSame(['hello' => "你好", "world" => "世界"], $translatedTexts);
    }
}