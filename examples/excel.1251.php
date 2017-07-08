<?php
require_once __DIR__ . '/__autoload.php';

$csv = new \SerginhoLD\Csv\Parser(';');
$arCsv = [];

$convertFunc = function ($row) {
    return mb_convert_encoding($row, 'UTF-8', 'Windows-1251');
};

foreach ($csv->parseFile(__DIR__ . '/excel.1251.csv', $convertFunc) as $row)
{
    $arCsv[] = $row;
}

echo '<pre>' . print_r($arCsv, true) . '</pre>';