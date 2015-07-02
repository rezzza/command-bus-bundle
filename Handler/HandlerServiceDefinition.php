<?php

namespace Rezzza\CommandBusBundle\Handler;

/**
 * HandlerServiceDefinition
 *
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class HandlerServiceDefinition
{
    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $method;

    /**
     * @param string $service object
     * @param string $method method
     */
    public function __construct($service, $method = null)
    {
        $this->service = $service;
        $this->method  = $method;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
}
