<?php
namespace SerginhoLD\Csv\Exception;

/**
 * Class FileNotReadException
 * @package SerginhoLD\Csv\Exception
 */
class FileNotReadException extends IOException
{
    /**
     * FileNotReadException constructor.
     * @param string $path
     * @param null $message
     * @param \Exception|null $previous
     */
    public function __construct($path, $message = null, \Exception $previous = null)
    {
        if (null === $message)
        {
            $message = sprintf('File "%s" can not be read.', $path);
        }
        
    }
}
