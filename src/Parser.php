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

namespace SerginhoLD\CSV;

use SerginhoLD\CSV\Exception\FileNotFoundException;
use SerginhoLD\CSV\Exception\WrongFileMimeTypeException;

/**
 * Class Parser
 * @package SerginhoLD\CSV
 */
class Parser extends \ArrayObject
{
    /**
     * @var string Разрыв строки
     */
    const CRLF = "\r\n";
    
    /**
     * @var string Разделитель (Только один символ)
     */
    protected $delimiter = ',';
    
    /**
     * @var string Ограничитель полей (Только один символ)
     */
    protected $enclosure = '"';
    
    /**
     * @var array Mime-типы для проверки файлов
     */
    protected $mimeTypes = [
        'text/plain',
        'text/csv',
        'text/tsv',
        'application/vnd.ms-excel'
    ];
    
    /**
     * @var string
     */
    protected $inputEncoding = 'UTF-8';
    
    /**
     * @var string
     */
    protected $outputEncoding = 'UTF-8';
    
    /**
     * @var bool Использование заголовков полей
     */
    protected $withHeaders = false;
    
    /**
     * @var array Заголовки полей
     */
    protected $headers = [];
    
    /**
     * @var \finfo
     * @see http://php.net/manual/ru/class.finfo.php
     */
    protected static $finfo;
    
    /**
     * {@inheritdoc}
     */
    public function __construct($input = [], $flags = 0, $iterator_class = "ArrayIterator")
    {
        if (!(self::$finfo instanceof \finfo))
        {
            self::$finfo = new \finfo;
        }
        
        parent::__construct($input, $flags, $iterator_class);
    }
    
    /**
     * Разбор CSV
     * 
     * @param string $data
     * 
     * @return $this
     */
    public function parse($data)
    {
        if ($this->inputEncoding !== $this->outputEncoding)
        {
            $data = mb_convert_encoding($data, $this->outputEncoding, $this->inputEncoding);
        }
        
        $result = str_replace(self::CRLF, "\n", $data); // Linux or Windows? 😕
        $result = explode("\n", $result);
        
        $result = array_filter($result, function($row) {
            return $row !== '';
        });
        
        $issetNextRow = true;
        $index = key($result);
        $maxRowSize = 0;
        
        while ($issetNextRow)
        {
            next($result);
            $nextIndex = key($result);
            $issetNextRow = isset($result[$nextIndex]);
            
            $row = &$result[$index];
            $countEnclosure = mb_substr_count($row, $this->enclosure, $this->outputEncoding);
            
            // Проверяем кол-во символов ограничителя полей в текущей строке.
            // Если их нечетное кол-во, значит в одном из полей строки находится символ разрыва строки,
            // объединяем текущую строку со следующей и проверям заново.
            if ($countEnclosure % 2 !== 0 && $issetNextRow)
            {
                $row .= self::CRLF . $result[$nextIndex];
                unset($result[$nextIndex]);
    
                prev($result);
            }
            else
            {
                $row = $this->parseRow($row, $this->delimiter, $this->enclosure);
                $index = $nextIndex;
                
                $rowSize = count($row);
                $maxRowSize = ($rowSize > $maxRowSize) ? $rowSize : $maxRowSize;
            }
        }
        
        // Каждая строка должна содержать одинаковое количество полей по всему файлу
        $result = array_map(function($row) use ($maxRowSize) {
            return array_pad($row, $maxRowSize, null);
        }, array_values($result));
        
        if ($this->withHeaders)
        {
            $this->headers = $headers = $result[0];
            unset($result[0]);
            
            $result = array_map(function($row) use ($headers) {
                return array_combine($headers, $row);
            }, array_values($result));
        }
        
        $this->exchangeArray($result);
        
        return $this;
    }
    
    /**
     * Разбор одной строки
     * 
     * Magic! ✨
     * Последний параметр `$escape` в `str_getcsv` равен параметру `$enclosure`.
     * Как-то работает, ничего не экранируя, ибо RFC 4180 (Поля со спец. символами заключаются в кавычки).
     * 
     * @param string $str
     * @param string $delimiter
     * @param string $enclosure
     * 
     * @return array
     */
    public function parseRow($str, $delimiter, $enclosure)
    {
        return str_getcsv($str, $delimiter, $enclosure, $enclosure);
    }
    
    /**
     * Разбор CSV-файла
     *
     * @param string $path
     *
     * @throws FileNotFoundException
     * @throws WrongFileMimeTypeException
     *
     * @return $this
     */
    public function parseFile($path)
    {
        if (!in_array($this->getFileMimeType($path), $this->mimeTypes, true))
        {
            throw new WrongFileMimeTypeException($path);
        }
        
        $data = null;
        $file = fopen($path, 'r');
        
        while (($buffer = fgets($file)) !== false)
        {
            $data .= $buffer;
        }
        
        fclose($file);
        
        return $this->parse($data);
    }
    
    /**
     * Присваивает значения для заданной строки
     *
     * @param mixed $index
     * @param array $value One-dimensional array
     *
     * @throws \InvalidArgumentException
     */
    public function offsetSet($index, $value)
    {
        if (!is_array($value) || count(array_filter($value, 'is_array')))
        {
            throw new \InvalidArgumentException('Argument `$value` is not a one-dimensional array');
        }
        
        $newRowSize = count($value);
        $csvFirstRowKey = key($this->getArrayCopy());
        $csvRowSize = ($csvFirstRowKey !== null) ? count($this[$csvFirstRowKey]) : 0;
        
        if ($csvRowSize)
        {
            if ($csvRowSize > $newRowSize)
            {
                $value = array_pad($value, $csvRowSize, null);
            }
            else if ($csvRowSize < $newRowSize)
            {
                // or Exception?
                $this->exchangeArray(array_map(function($row) use ($newRowSize) {
                    return array_pad($row, $newRowSize, null);
                }, $this->getArrayCopy()));
            }
        }
        
        if ($this->inputEncoding !== $this->outputEncoding)
        {
            $value = array_map(function($cell) {
                return mb_convert_encoding($cell, $this->outputEncoding, $this->inputEncoding);
            }, $value);
        }
        
        parent::offsetSet($index, $value);
    }
    
    /**
     * Добавляет новую строку
     *
     * @param array $value One-dimensional array
     */
    public function append($value)
    {
        $this->offsetSet(null, $value);
    }
    
    /**
     * Преобразует массив данных в CSV-формат
     * 
     * @return string
     */
    public function __toString()
    {
        return implode(self::CRLF, array_map(function($row) {
            
            return implode($this->delimiter, array_map(function($cell) {
                
                $countEnclosure = mb_substr_count($cell, $this->enclosure, $this->outputEncoding);
                $countDelimiter = mb_substr_count($cell, $this->delimiter, $this->outputEncoding);
                $countCR = mb_substr_count($cell, "\r", $this->outputEncoding);
                $countLF = mb_substr_count($cell, "\n", $this->outputEncoding);
    
                if ($countEnclosure)
                {
                    $cell = str_replace($this->enclosure, ($this->enclosure . $this->enclosure), $cell);
                }
    
                if ($countEnclosure || $countDelimiter || $countCR || $countLF)
                {
                    $cell = $this->enclosure . $cell . $this->enclosure;
                }
                
                return $cell;
                
            }, $row));
            
        }, $this->getArrayCopy()));
    }
    
    /**
     * Сохраняет данные в CSV-файл
     * @see http://php.net/manual/ru/function.file-put-contents.php
     * 
     * @param string $path
     * @param $flags
     * 
     * @return int|false
     */
    public function saveToFile($path, $flags = LOCK_EX)
    {
        return file_put_contents($path, (string)$this, $flags);
    }
    
    /**
     * Использовать заголовки полей
     * 
     * @param bool $flag
     * 
     * @return $this
     */
    public function withHeaders($flag = true)
    {
        $this->withHeaders = $flag;
        return $this;
    }
    
    /**
     * Устанавливает заголовки полей
     * 
     * @param array $headers
     * 
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }
    
    /**
     * Возвращает заголовки полей
     * 
     * @return array|false
     */
    public function getHeaders()
    {
        return $this->withHeaders ? $this->headers : false;
    }
    
    /**
     * Устанавливает разделитель полей
     * 
     * @param string $delimiter
     * 
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }
    
    /**
     * Возвращает разделитель полей
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }
    
    /**
     * Устанавливает ограничительный символ полей
     *
     * @param string $enclosure
     * 
     * @return $this
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }
    
    /**
     * Возвращает ограничительный символ полей
     *
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }
    
    /**
     * @param $encoding
     * 
     * @return $this
     */
    public function setInputEncoding($encoding)
    {
        $this->inputEncoding = $encoding;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getInputEncoding()
    {
        return $this->inputEncoding;
    }
    
    /**
     * @param $encoding
     * 
     * @return $this
     */
    public function setOutputEncoding($encoding)
    {
        if ($encoding !== $this->outputEncoding)
        {
            // encode all rows
            
            if ($this->count())
            {
                //
            }
            
            if (count($this->headers))
            {
                //
            }
        }
        
        $this->outputEncoding = $encoding;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getOutputEncoding()
    {
        return $this->outputEncoding;
    }
    
    /**
     * Добавляет mime-тип для проверки CSV-файлов
     * 
     * @param string $mimeType
     * 
     * @return $this
     */
    public function addMimeType($mimeType)
    {
        $this->mimeTypes[] = $mimeType;
        return $this;
    }
    
    /**
     * Возвращает mime-тип файла
     * 
     * @param string $path
     * @throws FileNotFoundException
     * 
     * @return string
     */
    public function getFileMimeType($path)
    {
        if (!is_file($path))
        {
            throw new FileNotFoundException($path);
        }
        
        return self::$finfo->file($path, FILEINFO_MIME_TYPE);
    }
    
    /**
     * Если используется как функция, возвращает CSV в виде массива
     *
     * @return array
     */
    public function __invoke()
    {
        return $this->getArrayCopy();
    }
}