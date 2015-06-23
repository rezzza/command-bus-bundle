<?php

namespace Rezzza\CommandBusBundle\Provider\SncRedis;

use Rezzza\CommandBus\Infra\Provider\Redis\RedisBus;
use Psr\Log\LoggerInterface;

/**
 * SncRedisBus
 *
 * @uses RedisBus
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class SncRedisBus extends RedisBus
{
    /**
     * @param object          $client client
     * @param LoggerInterface $logger logger
     */
    public function __construct($client, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

}
