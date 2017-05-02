<?php
/**
 * Example Translate Texts to French Using OHT
 *
 * php translate_to.php --provider=OHT \
 *      --region=ca-central-1  --translation=translation_carambola \
 *      --translation_queue=t_carambola_queue --translation_project=t_carambola_project \
 *      fr
 */
require_once ("./../vendor/autoload.php");
date_default_timezone_set( 'UTC' );

$options = getopt('', ['region::', 'provider::', 'limit::', 'translation::', 'translation_queue::', 'translation_project::']);

/**
 * Check configuration
 */
if (empty($options['region'])) {
    echo "Please specify AWS region\n";
    exit;
}
$region = $options['region'];

if (empty($options['translation'])) {
    echo "Please specify the name of translation table\n";
    exit;
}
$translation = $options['translation'];

if (empty($options['translation_queue'])) {
    echo "Please specify the name of translation_queue table\n";
    exit;
}
$translationQueue = $options['translation_queue'];

if (empty($options['translation_project'])) {
    echo "Please specify the name of translation_project table\n";
    exit;
}
$translationProject = $options['translation_project'];

if (empty($options['provider']) || ($options['provider'] != 'OHT' && $options['provider'] != 'GCT')) {
    echo "Please specify the translation provider. We current support OHT and GCT. \n";
    exit;
}
$targetProvider = $options['provider'];

$limit = !empty($options['limit']) && $options['limit'] > 1 ? $options['limit'] : 25;

$numOfOptions = count($options);
$targetLanguage = $argv[$numOfOptions+1];

$translationConfig = [
    "name" => $translation,
    "region" => $region,
    "version" => "2012-08-10"
];

$translationQueueConfig = [
    "name" => $translationQueue,
    "region" => $region,
    "version" => "2012-08-10"
];

$translationProjectConfig = [
    "name" => $translationProject,
    "region" => $region,
    "version" => "2012-08-10"
];

/**
 * Code started.
 */
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
} while($lastEvaluatedKey !== null);

echo "A total of [$projectItemCount] items added to the translation queue. [$projectCount] projects has been created. \n";