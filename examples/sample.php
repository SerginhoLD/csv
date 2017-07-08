<?php
require_once __DIR__ . '/__autoload.php';

$csv = new \SerginhoLD\Csv\Parser();
$arCsv = [];

foreach ($csv->parseFile(__DIR__ . '/sample.utf8.csv') as $row)
{
    $arCsv[] = $row;
}

echo '<pre>' . print_r($arCsv, true) . '</pre>';


$arCsvStrData = $csv->parse(file_get_contents(__DIR__ . '/sample.utf8.csv'));
echo '<pre>' . print_r($arCsvStrData, true) . '</pre>';