<?php

namespace AppBundle;


class SerializeJsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse
{
    /**
     * Not yet serialized data
     *
     * @var mixed
     */
    private $unserialized;
    
    /**
     * Not yet serialized
     *
     * @param mixed $unserializedData The data which should be serialized
     * @param int $status             The response status code
     * @param array $headers          An array of response headers
     */
    public function __construct($unserializedData = null, $status = 200, $headers = [])
    {
        $this->unserialized = $unserializedData;
        
        parent::__construct(null, $status, $headers, false);
    }
    
    /**
     * @return mixed
     */
    public function getUnserialized()
    {
        return $this->unserialized;
    }
    
    /**
     * Sends content for the current web response.
     */
    public function sendContent()
    {
        if ($this->unserialized !== null && $this->data === null) {
            //response has not yet been converted, so trying to let json response do this
            $this->setData($this->unserialized);
        }

        return parent::sendContent();
    }

    
    
}