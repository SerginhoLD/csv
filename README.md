# Simple csv parser (RFC 4180)

## Installation
```bash
composer require serginhold/csv
```

## Example

CSV:
```csv
Pot,Club,Country
1,"PFC CSKA Moskva",RUS
2,Bayer 04 Leverkusen,GER
3,"Tottenham
Hotspur FC",ENG
4,Monaco,FRA
```

Code:
```php
$csv = new \SerginhoLD\Csv\Parser();
$arCsv = [];

foreach ($csv->parseFile(__DIR__ . '/sample.utf8.csv') as $row)
{
    $arCsv[] = $row;
}

echo '<pre>' . print_r($arCsv, true) . '</pre>';
```

Result:
```
Array
(
    [0] => Array
        (
            [0] => Pot
            [1] => Club
            [2] => Country
        )

    [1] => Array
        (
            [0] => 1
            [1] => PFC CSKA Moskva
            [2] => RUS
        )

    [2] => Array
        (
            [0] => 2
            [1] => Bayer 04 Leverkusen
            [2] => GER
        )

    [3] => Array
        (
            [0] => 3
            [1] => Tottenham
Hotspur FC
            [2] => ENG
        )

    [4] => Array
        (
            [0] => 4
            [1] => Monaco
            [2] => FRA
        )

)
```

## Methods
```php
/**
 * @param string $file Path to csv file
 * @param \Closure|null $convert Function for converting each line of a file
 * @return \Generator
 */
public function parseFile($file, \Closure $convert = null)

/**
 * @param string $csv
 * @return array
 */
public function parse($csv)

/**
 * @param string $row
 * @param string $delimiter
 * @param string $enclosure
 * @return string[]
 */
public function parseRow($row, $delimiter = null, $enclosure = null)

/**
 * @return string
 */
public function getDelimiter()

/**
 * @param string $delimiter
 * @return $this
 */
public function setDelimiter($delimiter)
   
/**
 * @return string
 */
public function getEnclosure()

/**
 * @param string $enclosure
 * @return $this
 */
public function setEnclosure($enclosure)
```

## Requirements
* PHP >= 5.5.0
* mbstring

## License
[MIT](LICENSE.md)