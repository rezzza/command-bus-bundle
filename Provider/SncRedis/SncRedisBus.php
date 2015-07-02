<?php

namespace Rezzza\CommandBusBundle\Provider\SncRedis;

use Psr\Log\LoggerInterface;
use Rezzza\CommandBus\Infra\Provider\Redis\RedisBus;
use Rezzza\CommandBus\Infra\Provider\Redis\RedisKeyGeneratorInterface;


/**
 * SncRedisBus
 *
 * @uses RedisBus
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class SncRedisBus extends RedisBus
{
    /**
     * @param object                     $client       client
     * @param RedisKeyGeneratorInterface $keyGenerator keyGenerator
     * @param LoggerInterface            $logger       logger
     */
    public function __construct($client, RedisKeyGeneratorInterface $keyGenerator, LoggerInterface $logger = null)
    {
        $this->client       = $client;
        $this->keyGenerator = $keyGenerator;
        $this->logger       = $logger;
    }

}
