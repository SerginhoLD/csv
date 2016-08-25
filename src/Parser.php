<?php
/**
 * CSV Parser
 * 
 * @author Sergey Zubrilin
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
//class Parser implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @const string Разрыв строки
     */
    const CRLF = "\r\n";
    
    /**
     * @var string Разделитель (Только один символ)
     */
    protected $delimiter = ',';
    
    /**
     * @var string Символ ограничителя поля (Только один символ)
     */
    protected $enclosure = '"';
    
    /**
     * @var array Mime-типы
     */
    protected $mimeTypes = [
        'text/plain',
        'text/csv',
        'text/tsv',
        'application/vnd.ms-excel'
    ];
    
    /**
     * Разбор CSV-файла
     * 
     * @param string $path
     * 
     * @return $this
     */
    public function parseFile($path)
    {
        if (!is_file($path))
        {
            throw new FileNotFoundException($path);
        }
    
        if (!in_array((new \finfo(FILEINFO_MIME_TYPE))->file($path), $this->mimeTypes, true))
        {
            throw new WrongFileMimeTypeException($path);
        }
    
        //$this->parse(file_get_contents($path));
    
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
     * Разбор CSV
     * 
     * @param string $data
     * 
     * @return $this
     */
    public function parse($data)
    {
        $result = str_replace("\n", self::CRLF, $data); // Linux or Windows? 😕
        $result = explode(self::CRLF, $result);
    
        $issetNextRow = true;
        $index = 0;
        
        while ($issetNextRow)
        {
            next($result);
            $nextIndex = key($result);
            $issetNextRow = isset($result[$nextIndex]);
            
            $row = &$result[$index];
            $countEnclosure = mb_substr_count($row, $this->enclosure);
            
            // Проверяем кол-во символов ограничителя полей в текущей строке.
            // Если их нечетное кол-во, значит в одном из полей строки находится символ разделителя строки,
            // далее объединяем текущую строку со следующей и проверям заново.
            if ($countEnclosure % 2 !== 0 && $issetNextRow)
            {
                $row .= self::CRLF . $result[$nextIndex];
                unset($result[$nextIndex]);
    
                prev($result);
            }
            else
            {
                // Последний параметр `$escape` в str_getcsv равен параметру `$enclosure`.
                // Как-то работает, ничего не экранируя, ибо RFC 4180 (Поля со спец. символами заключаются в кавычки).
                $row = str_getcsv($row, $this->delimiter, $this->enclosure, $this->enclosure);
                $index = $nextIndex;
            }
        }
        
        $this->exchangeArray($result);
    
        return $this;
    }
    
    /**
     * Преобразует массив данных в CSV-формат
     * 
     * @return string
     */
    public function __toString()
    {
        $csv = null;
        
        if ($this->count())
        {
            $csvRows = [];
            
            foreach ($this as $row)
            {
                $csvRow = [];
                
                foreach ($row as $cell)
                {
                    $csvCell = $cell;
                    
                    $countEnclosure = mb_substr_count($cell, $this->enclosure);
                    $countDelimiter = mb_substr_count($cell, $this->delimiter);
                    $countCRLF = mb_substr_count($cell, self::CRLF);
    
                    if ($countEnclosure)
                    {
                        $csvCell = str_replace($this->enclosure, ($this->enclosure . $this->enclosure), $cell);
                    }
                    
                    if ($countEnclosure || $countDelimiter || $countCRLF)
                    {
                        $csvCell = $this->enclosure . $csvCell . $this->enclosure;
                    }
    
                    $csvRow[] = $csvCell;
                }
                
                $csvRows[] = implode($this->delimiter, $csvRow);
            }
    
            $csv = implode(self::CRLF, $csvRows);
        }
        
        return $csv;
    }
    
    /**
     * Сохраняет данные в CSV-файл
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
     * Устанавливает разделитель полей
     * 
     * @param $delimiter
     * 
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }
    
    /**
     * Устанавливает ограничительный символ полей
     *
     * @param $enclosure
     * 
     * @return $this
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }
    
    /**
     * Добавление новой строки
     * 
     * @param array $value
     */
    public function append($value)
    {
        parent::append((array)$value);
    }
    
    /**
     * Присваивание значения для заданной строки
     * 
     * @param mixed $index
     * @param array $value
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet($index, (array)$value);
    }
    
    /**
     * Добавляет Mime-тип для проверки CSV-файлов
     * 
     * @param $mimeType
     * 
     * @return $this
     */
    public function addMimeType($mimeType)
    {
        $this->mimeTypes[] = $mimeType;
        return $this;
    }
    
    /**
     * Если используется как функция, возвращает CSV в виде массива
     *
     * @return array
     */
    public function __invoke()
        //public function __invoke(array & $data = null)
    {
        return $this->getArrayCopy();
        //return is_null($data) ? $this->rows : $data;
    }
    /**
     * @var array Строки
     *
    protected $rows = [];
    
    /**
     * @var mixed Ключ текущего элемента
     *
    private $position;
    
    /**
     * Constructor
     *
    public function __construct()
    {
        $this->rewind();
    }
    
    /**
     * Добавление новой строки
     * 
     * @param array $row
     *
    public function append(array $row)
    {
        //$this->rows[] = $row;
        $this->offsetSet(null, $row);
    }
    
    /**
     * Присваивание значения для заданной строки
     * 
     * @param mixed $offset
     * @param mixed $value
     *
    public function offsetSet($offset, $value)
    {
        if (!is_array($value))
        {
            $value = (array)$value;
        }
        
        if (is_null($offset) /*|| !key_exists($offset, $this->rows)*)
        {
            $this->rows[] = $value;
        }
        else
        {
            $this->rows[$offset] = $value;
        }
    }
    
    /**
     * Проверка на существование строки
     * 
     * @param mixed $offset
     * 
     * @return bool
     *
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }
    
    /**
     * Удаление строки
     * 
     * @param mixed $offset
     *
    public function offsetUnset($offset)
    {
        unset($this->rows[$offset]);
    }
    
    /**
     * Возвращает заданную строку
     * 
     * @param mixed $offset
     * 
     * @return mixed|null
     *
    public function offsetGet($offset)
    {
        return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
    }
    
    /**
     * Возвращает итератор обратно на первый элемент
     * В начале цикла foreach этот метод вызывается первым, этот метод не будет вызван после цикла foreach
     *
    function rewind()
    {
        reset($this->rows);
        $this->position = key($this->rows);
    }
    
    /**
     * Возвращает текущий элемент
     * 
     * @return mixed
     *
    function current()
    {
        return $this->rows[$this->position];
    }
    
    /**
     * Возвращает ключ текущего элемента
     * 
     * @return mixed
     *
    function key()
    {
        return $this->position;
    }
    
    /**
     * Передвигает текущую позицию к следующему элементу
     * Метод вызывается после каждой итерации foreach
     *
    function next()
    {
        next($this->rows);
        $this->position = key($this->rows);
    }
    
    /**
     * Этот метод вызывается после Iterator::rewind() и Iterator::next() чтобы проверить, корректна ли текущая позиция
     * 
     * @return bool
     *
    function valid()
    {
        return isset($this->rows[$this->position]);
    }
    
    /**
     * Кол-во строк
     * 
     * @return int
     *
    public function count()
    {
        return count($this->rows);
    }
    /**/
}