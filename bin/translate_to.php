<?php

require_once ("./../vendor/autoload.php");
$client = new \Aws\DynamoDb\DynamoDbClient([
    "region" => 'ca-central-1',
    "version" => '2012-08-10',
]);

$i = 0;
$lastEvaluatedKey = null;
do {
    $scanAttributes = array(
        'TableName' => "translation_carambola",
        'IndexName' => 'l-index',
        'ExpressionAttributeNames' => array(
            '#l' => 'l'
        ),
        'ExpressionAttributeValues' => array(
            ':l' => array('S' => "en"),
        ),
        'FilterExpression' => '#l = :l',
        'ScanIndexForward' => true,
        'Limit' => 3
    );
    if ($lastEvaluatedKey != null) {
        $scanAttributes['ExclusiveStartKey'] = $lastEvaluatedKey;
    }

    $result = $client->scan($scanAttributes);
    if (!empty($result)) {
        $lastEvaluatedKey = $result->get('LastEvaluatedKey');
    }
    echo "Page $i\n";

} while($lastEvaluatedKey !== null);
