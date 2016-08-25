<?php
namespace SerginhoLD\CSV\Exception;

class IOException extends \RuntimeException
{
    private $path;
    
    public function __construct($path, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->path = $path;
        
        parent::__construct($message, $code, $previous);
    }
    
    public function getPath()
    {
        return $this->path;
    }
}