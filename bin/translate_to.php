<?php

require_once ("./../vendor/autoload.php");

$translationConfig = [
    "name" => 'translation_carambola',
    "region" => "ca-central-1",
    "version" => "2012-08-10"
];

$translationQueueConfig = [
    "name" => 't_carambola_queue',
    "region" => "ca-central-1",
    "version" => "2012-08-10"
];

$translationProjectConfig = [
    "name" => 't_carambola_project',
    "region" => "ca-central-1",
    "version" => "2012-08-10"
];

$targetLanguage = 'zh';
$limit = 25;
$targetProvider = "OHT";

/**
 * Code started.
 */
$lastEvaluatedKey = null;
$projectCounts = 0;
$projectId = null;
do {
    $result = \Models\Translation::getAllTextsByLanguage($translationConfig, \Models\Translation::DEFAULT_LANGUAGE_CODE, $lastEvaluatedKey);
    if (!empty($result)) {
        $lastEvaluatedKey = $result->get('LastEvaluatedKey');
    }
    foreach ($result->get('Items') as $item) {
        //starting a new project
        if ($projectId === null || $projectCounts % $limit == 0) {
            $projectId = \Models\TranslationProject::idFactory();
            $translationProjectObject = new \Models\TranslationProject($translationProjectConfig);
            $translationProjectObject->setId($projectId);
            $translationProjectObject->setCreated(time());
            $translationProjectObject->setModified(time());
            $translationProjectObject->setStatus(\Models\TranslationProject::STATUS_PENDING);
            $translationProjectObject->setTargetLanguage($targetLanguage);
        }

        $translationItemObj = \Models\Translation::populateItemToObject($translationConfig, $item);

        $translationQueueItemID = \Models\TranslationQueue::idFactory($translationItemObj->getId(), $targetLanguage);
        $translationQueueItem = \Models\TranslationQueue::getById($translationQueueConfig, $translationQueueItemID);
        if (empty($translationQueueItem)) {
            $translationQueueItem = new \Models\TranslationQueue($translationQueueConfig);
            $translationQueueItem->setId($translationQueueItemID);
            $translationQueueItem->setStatus(\Models\TranslationQueue::STATUS_PENDING);
            $translationQueueItem->setCreated(time());
            $translationQueueItem->setModified(time());
            $translationQueueItem->setProvider($targetProvider);
            $translationQueueItem->setProjectId($projectId);
            $translationQueueItem->save();

            $projectCounts++;
        } else if ($translationQueueItem->getProvider() != $targetProvider) {
            $translationQueueItem->setStatus(\Models\TranslationQueue::STATUS_PENDING);
            $translationQueueItem->setProvider($targetProvider);
            $translationQueueItem->setProjectId($projectId);
            $translationQueueItem->setModified(time());
            $translationQueueItem->save();

            $projectCounts++;
        }

        if ($projectCounts > 0 && !empty($translationProjectObject)) {
            $translationProjectObject->save();
            $translationProjectObject = null;
        }
    }
} while($lastEvaluatedKey !== null);

echo "All Done\n";