# CSV Parser

## Пример

CSV:
```csv
Pot,Club,Country
1,"PFC CSKA Moskva",RUS
2,Bayer 04 Leverkusen,GER
3,"Tottenham
Hotspur FC",ENG
4,Monaco,FRA
```

Результат:
```php
return [
    ["Pot", "Club", "Country"],
    ["1", "PFC CSKA Moskva", "RUS"],
    ["2", "Bayer 04 Leverkusen", "GER"],
    ["3", "Tottenham\r\nHotspur FC", "ENG"],
    ["4", "Monaco", "FRA"],
];
```

## Лицензия
[MIT](LICENSE.md)