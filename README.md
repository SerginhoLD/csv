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
$csv = new \SerginhoLD\CSV\Parser;
$csv->parseFile($path);

print_r((array)$csv);
```

Result:
```php
return [
    ["Pot", "Club", "Country"],
    ["1", "PFC CSKA Moskva", "RUS"],
    ["2", "Bayer 04 Leverkusen", "GER"],
    ["3", "Tottenham\r\nHotspur FC", "ENG"],
    ["4", "Monaco", "FRA"],
];
```

## License
[MIT](LICENSE.md)