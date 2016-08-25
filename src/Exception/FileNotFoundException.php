<?php
namespace SerginhoLD\CSV\Exception;

class FileNotFoundException extends IOException
{
    public function __construct($path, $message = null, \Exception $previous = null)
    {
        if (null === $message)
        {
            $message = sprintf('File "%s" could not be found.', $path);
        }
        
        parent::__construct($path, $message, 1, $previous);
    }
}