<?php
namespace SerginhoLD\Csv\Exception;

/**
 * Class WrongFileMimeTypeException
 * @package SerginhoLD\Csv\Exception
 */
class WrongFileMimeTypeException extends FileException
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
        
        parent::__construct($path, $message, 0, $previous);
    }
}