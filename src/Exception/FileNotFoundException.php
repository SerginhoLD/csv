<?php
namespace SerginhoLD\Csv\Exception;

/**
 * Class FileNotFoundException
 * @package SerginhoLD\Csv\Exception
 */
class FileNotFoundException extends IOException
{
    /**
     * FileNotFoundException constructor.
     * @param string $path
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($path, $message = null, \Exception $previous = null)
    {
        if (null === $message)
        {
            $message = sprintf('File "%s" could not be found.', $path);
        }
        
        parent::__construct($path, $message, 1, $previous);
    }
}