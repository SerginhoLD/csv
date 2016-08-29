<?php
require_once __DIR__ . '/../../../autoload.php';

$data = [
    ['Number', 'Fruit'],
    ['1', 'Bananas'],
    ['2', 'Kiwi'],
];

$file = __DIR__ . '/save.csv';

$csv = new \SerginhoLD\CSV\Parser($data);
$csv[] = ['3', 'Oranges'];
$csv->append(['4', 'Apples']);

var_dump($csv->saveToFile($file));