<?php
/**
 * php cli.php [--options] [command] [arguments]
 */
require_once ("./../vendor/autoload.php");
date_default_timezone_set( 'UTC' );

$options = getopt('', ['region::', 'provider::', 'limit::', 'translation::', 'translation_queue::',
    'translation_project::', 'oth_pubkey::', 'oth_secret::', 'oth_sandbox::']);

/**
 * Check configuration
 */
if (empty($options['region'])) {
    echo "Please specify AWS region\n";
    exit;
}

if (empty($options['translation'])) {
    echo "Please specify the name of translation table\n";
    exit;
}

if (empty($options['translation_queue'])) {
    echo "Please specify the name of translation_queue table\n";
    exit;
}

if (empty($options['translation_project'])) {
    echo "Please specify the name of translation_project table\n";
    exit;
}

$translationConfig = [
    "name" => $options['translation'],
    "region" => $options['region'],
    "version" => "2012-08-10"
];

$translationQueueConfig = [
    "name" => $options['translation_queue'],
    "region" => $options['region'],
    "version" => "2012-08-10"
];

$translationProjectConfig = [
    "name" => $options['translation_project'],
    "region" => $options['region'],
    "version" => "2012-08-10"
];

$numOfOptions = count($options);
$action = $argv[$numOfOptions+1];

/**
 * Code started.
 */
switch ($action) {
    case "translate":
        if (empty($options['provider']) || ($options['provider'] != 'OHT' && $options['provider'] != 'GCT')) {
            echo "Please specify the translation provider. We current support OHT and GCT. \n";
            exit;
        }
        $targetProvider = $options['provider'];
        $limit = !empty($options['limit']) && $options['limit'] > 1 ? $options['limit'] : 25;
        $targetLanguages = array_slice($argv, count($options) + 2);
        if (empty($targetLanguages)) {
            echo "Please specify the target language. \n";
            exit;
        }
        foreach ($targetLanguages as $targetLanguage) {
            translate($targetLanguage, $targetProvider, $limit, $translationConfig, $translationQueueConfig, $translationProjectConfig);
        }
        break;

    case "add":
        $projects = array_slice($argv, count($options) + 2);
        foreach ($projects as $projectId) {
            echo "Starting processing project: $projectId \n";
            /** @var $translationProjectItem \Models\TranslationProject */
            $translationProjectItem = \Models\TranslationProject::getById($translationProjectConfig,$projectId);
            if (empty($translationProjectItem) || $translationProjectItem->getStatus() != \Models\TranslationProject::STATUS_PENDING) {
                echo "The project does not exists or the status is not pending.\n";
                continue;
            }
            switch ($translationProjectItem->getProvider()) {
                case \Models\TranslationProject::PROVIDER_ONE_HOUR_TRANSLATION:
                    if (empty($options['oth_pubkey']) || empty($options['oth_secret'])) {
                        throw new InvalidArgumentException("Unable to continue OTH project without keys.");
                    }
                    $oht = [
                        "pubkey" => $options['oth_pubkey'],
                        "secret" => $options['oth_secret'],
                        "sandbox" => !empty($options['oth_sandbox']) ? filter_var($options['oth_sandbox'], FILTER_VALIDATE_BOOLEAN) : false,
                    ];

                    handleOHTProject($translationProjectItem, $translationQueueConfig, $translationConfig, $oht);
                    break;
                case \Models\TranslationProject::PROVIDER_GOOGLE_CLOUD_TRANSLATION:
                    echo "TBD\n";
                    break;
                default:
                    echo "Unknown service provider\n";
                    continue;
            }
            echo "done\n";
        }
        break;
    default:
        echo "The action is not supported. [$action] \n";
        break;
}

/**
 * Translate
 *
 * @param $targetLanguage
 * @param $targetProvider
 * @param $limit
 * @param $translationConfig
 * @param $translationQueueConfig
 * @param $translationProjectConfig
 */
function translate($targetLanguage, $targetProvider, $limit, $translationConfig, $translationQueueConfig, $translationProjectConfig)
{
    $lastEvaluatedKey = null;
    $projectItemCount = 0;
    $projectCount = 0;
    $projectId = null;

    echo "Source Language: [en]. Target Language: [$targetLanguage]. Target Provider: [$targetProvider]\n";
    do {
        $result = \Models\Translation::getAllTextsByLanguage($translationConfig, \Models\Translation::DEFAULT_LANGUAGE_CODE, $lastEvaluatedKey);
        if (!empty($result)) {
            $lastEvaluatedKey = $result->get('LastEvaluatedKey');
        }
        foreach ($result->get('Items') as $item) {
            //starting a new project
            if ($projectId === null || $projectItemCount % $limit == 0) {
                $projectId = \Models\TranslationProject::idFactory();
                $translationProjectObject = new \Models\TranslationProject($translationProjectConfig);
                $translationProjectObject->setId($projectId);
                $translationProjectObject->setCreated(time());
                $translationProjectObject->setModified(time());
                $translationProjectObject->setStatus(\Models\TranslationProject::STATUS_PENDING);
                $translationProjectObject->setProvider($targetProvider);
                $translationProjectObject->setTargetLanguage($targetLanguage);
            }

            $translationItemObj = \Models\Translation::populateItemToObject($translationConfig, $item);

            $translationQueueItemID = \Models\TranslationQueue::idFactory($translationItemObj->getId(), $targetLanguage, $targetProvider);
            $translationQueueItem = \Models\TranslationQueue::getById($translationQueueConfig, $translationQueueItemID);
            if (empty($translationQueueItem)) {
                $translationQueueItem = new \Models\TranslationQueue($translationQueueConfig);
                $translationQueueItem->setId($translationQueueItemID);
                $translationQueueItem->setStatus(\Models\TranslationQueue::STATUS_PENDING);
                $translationQueueItem->setCreated(time());
                $translationQueueItem->setModified(time());
                $translationQueueItem->setProjectId($projectId);
                $translationQueueItem->save();

                $projectItemCount++;
            }

            if ($projectItemCount > 0 && !empty($translationProjectObject)) {
                echo "  ====> Project [$projectId] created by using [$targetProvider]. Source Language: [en]. Target Language: [$targetLanguage].\n";
                $translationProjectObject->save();
                $translationProjectObject = null;
                $projectCount++;
            }
        }
    } while ($lastEvaluatedKey !== null);

    echo "A total of [$projectItemCount] items added to the translation queue. [$projectCount] projects has been created. \n";
}

/**
 * Handle OHT
 * @param $translationProjectItem \Models\TranslationProject
 */
function handleOHTProject($translationProjectItem, $translationQueueConfig, $translationConfig, $oht)
{


    $items = \Models\TranslationQueue::getQueueItemsByProjectId($translationQueueConfig, $translationProjectItem->getId());

    $projectId = $translationProjectItem->getId();
    $targetLang = \Services\OneHourTranslation::transformTargetLang($translationProjectItem->getTargetLanguage());
    $sourceLang = \Services\OneHourTranslation::transformTargetLang(\Models\Translation::DEFAULT_LANGUAGE_CODE);
    $resources = [];
    $oneHourTranslation = new \Services\OneHourTranslation($oht['pubkey'], $oht['secret'], $oht['sandbox']);
    $projectData = [
        "resources" => [],
        "response" => [],
    ];

    echo "Starting OHT translation project id: [$projectId]. Source Lang: [$sourceLang]. Target Lang: [$targetLang] \n";
    foreach ($items->get('Items') as $item) {
        /** @var $translationQueueItem \Models\TranslationQueue */
        $translationQueueItem = \Models\TranslationQueue::populateItemToObject($translationQueueConfig, $item);
        list($sourceId, $__t, $__p) = \Models\TranslationQueue::idExplode($translationQueueItem->getId());
        /** @var $translationItem \Models\Translation */
        $translationItem = \Models\Translation::getById($translationConfig, $sourceId);
        $text = $translationItem->getText();

        $resourceId = $oneHourTranslation->uploadResourceText($text);
        $resources[] = $resourceId;
        $projectData['resources'][$resourceId] = $sourceId;
        $displayText = substr($text, 0, 10) . "...";
        echo "  ===> Translate: sourceID: [$sourceId]. resourceID: [$resourceId]. Text: [$displayText]\n";
    }

    $ohtExpertise = null;
    if (!empty($oht['expertise'])) {
        $ohtExpertise = $oht['expertise'];
    }
    $ohtCallback = "";
    if (!empty($oht['callback'])) {
        $ohtCallback = $oht['callback'];
    }
    $ohtNote = "";
    if (!empty($oht['note'])) {
        $ohtNote = $oht['note'];
    }
    $wordCount = 0;
    if (!empty($oht['wordCount'])) {
        $wordCount = $oht['wordCount'];
    }

    $projectData['response'] = $oneHourTranslation->createProject($projectId, $sourceLang, $targetLang, $resources, $ohtExpertise, $ohtCallback, $ohtNote, $wordCount);
    $translationProjectItem->setProjectData($projectData);
    $translationProjectItem->setModified(time());
    $translationProjectItem->setStatus(\Models\TranslationProject::STATUS_IN_PROGRESS);
    $translationProjectItem->save();

    echo "  ===> Project created. OHT Project ID: [{$projectData['response']->project_id}]. Cost of the project: [{$projectData['response']->credits}]\n";
}