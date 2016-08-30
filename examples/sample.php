<?php
require_once __DIR__ . '/__autoload.php';

$csv = new \SerginhoLD\CSV\Parser;
$csv->parseFile(__DIR__ . '/sample.utf8.csv');

echo '<pre>' . print_r((array)$csv, true) . '</pre>';