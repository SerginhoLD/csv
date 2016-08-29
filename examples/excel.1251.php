<?php
require_once __DIR__ . '/../../../autoload.php';

$csv = new \SerginhoLD\CSV\Parser;
$csv->setInputEncoding('Windows-1251')
    ->setDelimiter(';')
    ->parseFile(__DIR__ . '/excel.1251.csv');

echo '<pre>' . print_r((array)$csv, true) . '</pre>';