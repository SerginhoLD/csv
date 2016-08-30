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
     * @var bool Строгий режим, разрыв строк строго равен CRLF
     */
    protected $strictMode = false;
    
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
     * @param string $data CSV
     * 
     * @return $this
     */
    public function parse($data)
    {
        // http://php.net/manual/ru/regexp.reference.escape.php#108096 🤔
        $newlinePattern = $this->strictMode ? self::CRLF : "\R";
        
        $rows = preg_split("/$newlinePattern/", self::encode($data, $this->inputEncoding, $this->outputEncoding));
        
        $issetNextRow = true;
        $index = key($rows);
        $maxRowSize = 0;
        
        while ($issetNextRow)
        {
            next($rows);
            $nextIndex = key($rows);
            $issetNextRow = isset($rows[$nextIndex]);
            
            $row = &$rows[$index];
            $countEnclosure = mb_substr_count($row, $this->enclosure, $this->outputEncoding);
            
            // Проверяем кол-во символов ограничителя полей в текущей строке.
            // Если их нечетное кол-во, значит в одном из полей строки находится символ разрыва строки,
            // объединяем текущую строку со следующей и проверям заново.
            if ($countEnclosure % 2 !== 0 && $issetNextRow)
            {
                $row .= self::CRLF . $rows[$nextIndex];
                unset($rows[$nextIndex]);
                
                prev($rows);
            }
            // Удаляем пустую строку
            else if (trim($row) === '')
            {
                unset($rows[$index]);
                $index = $nextIndex;
            }
            else
            {
                $row = self::parseString($row, $this->delimiter, $this->enclosure);
                $index = $nextIndex;
                
                $rowSize = count($row);
                $maxRowSize = ($rowSize > $maxRowSize) ? $rowSize : $maxRowSize;
            }
        }
        
        // Каждая строка должна содержать одинаковое количество полей по всему файлу,
        // по этому увеличиваем размер массива каждой строки до величины `$maxRowSize`.
        $rows = self::arrayPadMap(array_values($rows), $maxRowSize);
        
        if ($this->withHeaders)
        {
            $this->headers = $rows[0];
            unset($rows[0]);
            
            $rows = self::arrayCombineMap($this->headers, array_values($rows));
        }
        
        $this->exchangeArray($rows);
        
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
    public static function parseString($str, $delimiter, $enclosure)
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
        
        if ($file)
        {
            while (($buffer = fgets($file)) !== false)
            {
                $data .= $buffer;
            }
            
            fclose($file);
        }
        
        return $this->parse($data);
    }
    
    /**
     * Присваивает значения для заданной строки
     *
     * @param mixed $index
     * @param array $values One-dimensional array
     *
     * @throws \InvalidArgumentException
     */
    public function offsetSet($index, $values)
    {
        if (!is_array($values) || count(array_filter($values, 'is_array')))
        {
            throw new \InvalidArgumentException('Argument `$values` is not a one-dimensional array');
        }
        
        // Каждая строка должна содержать одинаковое количество полей по всему файлу.
        $csvFirstRowKey = key($this->getArrayCopy());
        
        if (null !== $csvFirstRowKey)
        {
            $newRowSize = count($values);
            $csvRowSize = count($this[$csvFirstRowKey]);
            
            if ($csvRowSize > $newRowSize)
            {
                $values = array_pad($values, $csvRowSize, null);
            }
            else if ($csvRowSize < $newRowSize)
            {
                $this->exchangeArray(self::arrayPadMap($this->getArrayCopy(), $newRowSize));
            }
        }
        
        if ($this->issetHeaders())
        {
            $values = array_combine($this->getHeaders(), $values);
        }
        
        parent::offsetSet($index, $values);
    }
    
    /**
     * Добавляет новую строку
     *
     * @param array $values One-dimensional array
     */
    public function append($values)
    {
        $this->offsetSet(null, $values);
    }
    
    /**
     * Преобразует массив данных в CSV-формат
     * 
     * @return string
     */
    public function __toString()
    {
        $enclosureChars = [$this->delimiter, "\n", "\r"]; // and $this->enclosure
        $rows = $this->getArrayCopy();
        
        if ($this->issetHeaders())
        {
            $rows = array_merge([$this->headers], $rows);
        }
        
        return implode(self::CRLF, array_map(function($row) use ($enclosureChars) {
            
            return implode($this->delimiter, array_map(function($cell) use ($enclosureChars) {
                
                $countEnclosure = mb_substr_count($cell, $this->enclosure, $this->outputEncoding);
                $flagEnclosure = false;
                
                if ($countEnclosure)
                {
                    $cell = str_replace($this->enclosure, ($this->enclosure . $this->enclosure), $cell);
                    $flagEnclosure = true;
                }
                else
                {
                    // Вместо постоянного использования mb_substr_count по 4 раза для каждого элемента 🤔
                    foreach ($enclosureChars as $char)
                    {
                        if (mb_substr_count($cell, $char, $this->outputEncoding))
                        {
                            $flagEnclosure = true;
                            break;
                        }
                    }
                }
                
                if ($flagEnclosure)
                {
                    $cell = $this->enclosure . $cell . $this->enclosure;
                }
                
                return $cell;
                
            }, $row));
            
        }, $rows));
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
     * @param bool $deleteFirstRow Для удаления первой строки (если в ней находятся заголовки)
     * 
     * @return $this
     */
    public function setHeaders(array $headers, $deleteFirstRow = false)
    {
        $this->headers = $headers;
        
        if ($this->count())
        {
            if ($deleteFirstRow)
            {
                $firstRowKey = key($this->getArrayCopy());
                unset($this[$firstRowKey]);
            }
            
            $this->exchangeArray(self::arrayCombineMap($headers, $this->getArrayCopy()));
        }
        
        return $this->withHeaders(true);
    }
    
    /**
     * Возвращает заголовки полей
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * @return bool
     */
    public function issetHeaders()
    {
        return $this->withHeaders && !empty($this->getHeaders());
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
     * @param string $str
     * @param string $inputEncoding
     * @param string $outputEncoding
     * 
     * @return string
     */
    public static function encode($str, $inputEncoding, $outputEncoding)
    {
        if ($inputEncoding !== $outputEncoding)
        {
            $str = mb_convert_encoding($str, $outputEncoding, $inputEncoding);
        }
        
        return $str;
    }
    
    /**
     * @param array $array One-dimensional array
     * @param string $inputEncoding
     * @param string $outputEncoding
     * 
     * @return array
     */
    public static function encodeArray(array $array, $inputEncoding, $outputEncoding)
    {
        if ($inputEncoding !== $outputEncoding)
        {
            $array = array_map(function($str) use ($inputEncoding, $outputEncoding) {
                return self::encode($str, $inputEncoding, $outputEncoding);
            }, $array);
        }
        
        return $array;
    }
    
    /**
     * @param string $encoding
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
     * Изменяет кодировку вывода и всех элементов объекта
     * 
     * @param string $encoding
     * 
     * @return $this
     */
    public function setOutputEncoding($encoding)
    {
        if ($encoding !== $this->outputEncoding)
        {
            $countHeaders = count($this->headers);
            
            if ($countHeaders)
            {
                $this->headers = self::encodeArray($this->headers, $this->outputEncoding, $encoding);
            }
            
            if ($this->count())
            {
                $this->exchangeArray(array_map(function($row) use ($encoding, $countHeaders) {
                    
                    if ($countHeaders)
                    {
                        $row = array_combine($this->headers, $row);
                    }
                    
                    return self::encodeArray($row, $this->outputEncoding, $encoding);
                    
                }, $this->getArrayCopy()));
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
     * @param array $keys One-dimensional array
     * @param array $array Two-dimensional array
     * 
     * @return array
     */
    public static function arrayCombineMap(array $keys, array $array)
    {
        return array_map(function($row) use ($keys) {
            return array_combine($keys, $row);
        }, $array);
    }
    
    /**
     * @param array $array Two-dimensional array
     * @param int $size
     * @param mixed $value
     * 
     * @return array
     */
    public static function arrayPadMap(array $array, $size, $value = null)
    {
        return array_map(function($row) use ($size, $value) {
            return array_pad($row, $size, $value);
        }, $array);
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