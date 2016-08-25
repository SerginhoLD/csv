<?php
namespace SerginhoLD\CSV\Exception;

class WrongFileMimeTypeException extends IOException
{
    public function __construct($path, $message = null, \Exception $previous = null)
    {
        if (null === $message)
        {
            $message = sprintf('Wrong file "%s" mime-type.', $path);
        }
        
        parent::__construct($path, $message, 2, $previous);
    }
}