<?php
namespace RW\Services;
use com\OHT\API\OHTAPI;

/**
 * Class OneHourTranslation
 */
class OneHourTranslation
{
    /**
     * OHT supports ISO 639.2
     * @var array
     */
    public static $supportedLanguages = [
        "af",
        "sq-al",
        "am-et",
        "ar-sa",
        "ar-eg",
        "ar-ae",
        "ar-jo",
        "ar-ma",
        "hy-am",
        "az-az",
        "baq",
        "be-by",
        "bn-bd",
        "bs-ba",
        "bg-bg",
        "my-mm",
        "ca-es",
        "zh-cn-yue",
        "zh-cn-cmn-s",
        "zh-cn-cmn-t",
        "zh-tw",
        "hr-hr",
        "cs-cz",
        "da-dk",
        "fa-af",
        "nl-nl",
        "en-us",
        "et-ee",
        "fi-fi",
        "fl-be",
        "fr-fr",
        "fr-ca",
        "ka-ge",
        "de-de",
        "el-gr",
        "gu-in",
        "ht",
        "he-il",
        "hi-in",
        "hu-hu",
        "is-is",
        "id-id",
        "ga-ie",
        "it-it",
        "jp-jp",
        "kn",
        "kk-kz",
        "km-kh",
        "ko-kp",
        "ku-tr",
        "lo-la",
        "lv-lv",
        "lt-lt",
        "mk-mk",
        "ms-my",
        "ml-in",
        "mt-mt",
        "mr-in",
        "mn-mn",
        "sr-me",
        "ne-np",
        "no-no",
        "ps",
        "fa-ir",
        "pl-pl",
        "pt-br",
        "pt-pt",
        "pa-in",
        "ro-ro",
        "ru-ru",
        "sr-rs",
        "sr",
        "zn-shn",
        "si",
        "sk-sk",
        "sl-si",
        "so-so",
        "es-es",
        "es-ar",
        "sw",
        "sv-se",
        "fr-ch",
        "gsw-ch",
        "it-ch",
        "tl-ph",
        "tgk",
        "ta-in",
        "te-in",
        "th-th",
        "tir",
        "tr-tr",
        "uk-ua",
        "ur",
        "uz-uz",
        "vi-vn",
        "cy-bg",
        "xho",
        "yo-ng",
        "zul",
    ];

    /** @var $oht \com\OHT\API\OHTAPI */
    public $oht;

    /**
     * OneHourTranslation constructor.
     *
     * @param $ohtPublicKey
     * @param $ohtSecretKey
     * @param bool $sandbox
     */
    function __construct($ohtPublicKey, $ohtSecretKey, $sandbox = true)
    {
        $this->oht = new OHTAPI($ohtPublicKey, $ohtSecretKey, $sandbox);
    }

    /**
     * Upload Resource Text
     *
     * @param $text
     *
     * @return \com\OHT\API\stdClass
     */
    function uploadResourceText($text)
    {
        $result = $this->oht->uploadTextResource($text);
        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return $result->results[0];
        }
        throw new \InvalidArgumentException("Unable to upload resource to OHT. Reason: " . var_export($result, true));
    }

    /**
     * Upload File Resources
     *
     * @param $filePath
     *
     * @return mixed
     */
    function uploadResourceFile($filePath)
    {
        $cmd = "curl -s -F secret_key={$this->oht->getSecretKey()} -F public_key={$this->oht->getPublicKey()} -F upload=@{$filePath} {$this->oht->getBaseURL()}/resources/file";
        $response = shell_exec($cmd);
        $result = json_decode($response);

        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return $result->results[0];
        }
        throw new \InvalidArgumentException("Unable to upload resource to OHT. Reason: " . var_export($result, true));
    }

    /**
     * Tag a project
     *
     * @param $projectId
     */
    function tagProject($projectId, $tag)
    {
        $curl = curl_init();

        $data = [
            "public_key" => $this->oht->getPublicKey(),
            "secret_key" => $this->oht->getSecretKey(),
            "tag_name" => $tag
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->oht->getBaseURL() . "/project/$projectId/tag",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => 1,
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($curl);

        $result = json_decode($response);
        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return true;
        }
        throw new \InvalidArgumentException("Unable to upload resource to OHT. Reason: " . var_export($result, true));
    }

    /**
     * Unable to get resource
     *
     * @param $resourceId
     *
     * @return bool|string
     */
    function getResourceText($resourceId)
    {
        $result = $this->oht->getResource($resourceId, true);
        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return base64_decode($result->results->content);
        }
        throw new \InvalidArgumentException("Unable to get resource from OHT. Reason: " . var_export($result, true));
    }

    /**
     * Create Project
     *
     * @param $targetLang
     * @param $sources
     */
    function createProject($name, $sourceLang, $targetLang, $sources, $expertise = null, $callbackURL = "", $note = "", $wordCount = 0)
    {
        if (is_scalar($sources)) {
            $sources = array($sources);
        }

        $params = array();
        if (!empty($expertise)) {
            $params['expertise'] = $expertise;
        }

        if (!empty($name)) {
            $params['name'] = $name;
        }

        $result = $this->oht->newTranslationProject($sourceLang, $targetLang, implode(',',$sources), $wordCount, $note, $callbackURL, $params);
        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return $result->results;
        }
        throw new \InvalidArgumentException("Unable to create project in OHT. Reason: " . var_export($result, true));
    }

    /**
     * Get Project Status
     *
     * @param $projectId
     *
     * @return \com\OHT\API\stdClass
     */
    function getProjectStatus($projectId)
    {
        return $this->oht->getProjectDetails($projectId);
    }

}