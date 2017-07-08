# CSV Parser (RFC 4180)

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
$csv = new \SerginhoLD\CSV\Parser();
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

## License
[MIT](LICENSE.md)