<?php
namespace SerginhoLD\CSV\Exception;

/**
 * Class IOException
 * @package SerginhoLD\CSV\Exception
 */
class IOException extends \RuntimeException
{
    /**
     * @var string
     */
    private $path;
    
    /**
     * IOException constructor.
     * @param string $path
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($path, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->path = $path;
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}