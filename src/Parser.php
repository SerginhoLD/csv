<?php
/**
 * CSV Parser
 * 
 * @author Sergey Zubrilin https://github.com/SerginhoLD
 * 
 * @link https://en.wikipedia.org/wiki/Comma-separated_values
 * @link https://www.rfc-editor.org/rfc/rfc4180.txt RFC 4180
 * @link http://tradeincome.ru/useful-content/RFC%204180%20rus.pdf RFC 4180 на русском языке
 * 
 * @license MIT
 */

namespace SerginhoLD\Csv;

use SerginhoLD\Csv\Exception\FileException;
use SerginhoLD\Csv\Exception\FileNotReadException;
use SerginhoLD\Csv\Exception\FileNotFoundException;

/**
 * Class Parser
 * @package SerginhoLD\Csv
 */
class Parser
{
    /** @var string */
    protected $delimiter = ',';
    
    /** @var string */
    protected $enclosure = '"';
    
    /**
     * Parser constructor.
     * @param string $delimiter
     * @param string $enclosure
     */
    public function __construct($delimiter = null, $enclosure = null)
    {
        if ($delimiter !== null)
            $this->setDelimiter($delimiter);
        
        if ($enclosure !== null)
            $this->setEnclosure($enclosure);
    }
    
    /**
     * Example:
     * foreach ($csv->parseFile($file) as $row)
     *
     * @param string $file csv
     * @param \Closure|null $convert Функция для обработки строк файла, например для изменения кодировки
     * @return \Generator
     * @throws FileException
     */
    public function parseFile($file, \Closure $convert = null)
    {
        if (!is_file($file))
            throw new FileNotFoundException($file);
        
        $handle = @fopen($file, 'r');
        
        if ($handle === false)
            throw new FileNotReadException($file);
        
        try
        {
            $row = null;
            
            while (!feof($handle))
            {
                $buffer = fgets($handle);
                
                if ($convert instanceof \Closure)
                    $buffer = $convert($buffer, $this);
                
                $row .= $buffer;
                
                // Не берем пустые строки
                if (trim($row) === '')
                {
                    $row = null;
                    continue;
                }
                // Проверяем кол-во символов ограничителя полей в текущей строке
                // Если их нечетное кол-во, значит в одном из полей строки находится символ разрыва строки
                // объединяем текущую строку со следующей и проверям заново
                else if (mb_substr_count($row, $this->getEnclosure()) % 2 !== 0)
                {
                    continue;
                }
                
                yield $this->parseRow($row);
                $row = null;
            }
        }
        finally
        {
            fclose($handle);
        }
    }
    
    /**
     * @param string $csv
     * @return array
     */
    public function parse($csv)
    {
        $data = explode("\n", str_replace(["\r\n", "\n\r", "\r"], "\n", $csv));
        $rows = [];
        $row = null;
        
        foreach ($data as $item)
        {
            $row .= $item;
            
            // Не берем пустые строки
            if (trim($row) === '')
            {
                $row = null;
                continue;
            }
            // Проверяем кол-во символов ограничителя полей в текущей строке
            // Если их нечетное кол-во, значит в одном из полей строки находится символ разрыва строки
            // объединяем текущую строку со следующей и проверям заново
            else if (mb_substr_count($row, $this->getEnclosure()) % 2 !== 0)
            {
                $row .= PHP_EOL;
                continue;
            }
            
            $rows[] = $this->parseRow($row);
            $row = null;
        }
        
        return $rows;
    }
    
    /**
     * Разбор одной строки
     *
     * Magic! ✨
     * Последний параметр `$escape` в `str_getcsv` равен параметру `$enclosure`.
     * Как-то работает, ничего не экранируя, ибо RFC 4180 (Поля со спец. символами заключаются в кавычки).
     *
     * @param string $row
     * @return string[]
     */
    public function parseRow($row)
    {
        return str_getcsv($row, $this->getDelimiter(), $this->getEnclosure(), $this->getEnclosure());
    }
    
    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }
    
    /**
     * @param string $delimiter
     * @return $this
     * @throws \LengthException
     */
    public function setDelimiter($delimiter)
    {
        if (mb_strlen($delimiter) !== 1)
            throw new \LengthException('A delimiter can consist of only one character');
        
        $this->delimiter = $delimiter;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }
    
    /**
     * @param string $enclosure
     * @return $this
     * @throws \LengthException
     */
    public function setEnclosure($enclosure)
    {
        if (mb_strlen($enclosure) !== 1)
            throw new \LengthException('A enclosure can consist of only one character');
        
        $this->enclosure = $enclosure;
        return $this;
    }
}