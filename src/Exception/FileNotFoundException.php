<?php
namespace SerginhoLD\CSV\Exception;

/**
 * Class FileNotFoundException
 * @package SerginhoLD\CSV\Exception
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