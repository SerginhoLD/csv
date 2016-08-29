<?php
namespace SerginhoLD\CSV\Exception;

/**
 * Class WrongFileMimeTypeException
 * @package SerginhoLD\CSV\Exception
 */
class WrongFileMimeTypeException extends IOException
{
    /**
     * WrongFileMimeTypeException constructor.
     * @param string $path
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($path, $message = null, \Exception $previous = null)
    {
        if (null === $message)
        {
            $message = sprintf('Wrong file "%s" mime-type.', $path);
        }
        
        parent::__construct($path, $message, 2, $previous);
    }
}