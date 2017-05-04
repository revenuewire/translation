<?php
/**
 * To Create tables that need for this translation services, run
 * php init_dynamo_table.php --translation=[TABLE_NAME] --translation_queue=[TABLE_NAME] \
 *      --translation_project=[TABLE_NAME] --region=[aws_region_code]
 */
require_once ("./../vendor/autoload.php");
date_default_timezone_set( 'UTC' );

$options = getopt('', array('translation::', 'translation_queue::', 'translation_project::', 'region::'));

/**
 * Check configuration
 */
if (empty($options['region'])) {
    echo "Please specify AWS region\n";
    exit;
}
$region = $options['region'];

$db = new \Aws\DynamoDb\DynamoDbClient([
    "region" => $region,
    "version" => "2012-08-10"
]);

if (!empty($options['translation'])) {
    echo "Install translation table name: [{$options['translation']}] in region: [{$region}] ...";
    $schema = \Models\Translation::$schema;
    $schema['TableName'] = $options['translation'];
    $db->createTable($schema);
    echo "done\n";
}

if (!empty($options['translation_queue'])) {
    echo "Install translation queue table name: [{$options['translation_queue']}] in region: [{$region}] ...";
    $schema = \Models\TranslationQueue::$schema;
    $schema['TableName'] = $options['translation_queue'];
    $db->createTable($schema);
    echo "done\n";
}

if (!empty($options['translation_project'])) {
    echo "Install translation project table name: [{$options['translation_project']}] in region: [{$region}] ...";
    $schema = \Models\TranslationProject::$schema;
    $schema['TableName'] = $options['translation_project'];
    $db->createTable($schema);
    echo "done\n";
}

echo "All Done\n";