<?php

namespace Rezzza\CommandBusBundle\Provider\SncRedis;

use Psr\Log\LoggerInterface;
use Rezzza\CommandBus\Domain\Serializer\CommandSerializerInterface;
use Rezzza\CommandBus\Infra\Provider\Redis\RedisBus;
use Rezzza\CommandBus\Infra\Provider\Redis\RedisKeyGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * SncRedisBus
 *
 * @uses RedisBus
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class SncRedisBus extends RedisBus
{
    /**
     * @param object                     $client          client
     * @param RedisKeyGeneratorInterface $keyGenerator    keyGenerator
     * @param CommandSerializerInterface $serializer      serializer
     * @param EventDispatcherInterface   $eventDispatcher eventDispatcher
     * @param LoggerInterface            $logger          logger
     */
    public function __construct($client, RedisKeyGeneratorInterface $keyGenerator, CommandSerializerInterface $serializer, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger = null)
    {
        $this->client          = $client;
        $this->keyGenerator    = $keyGenerator;
        $this->serializer      = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger          = $logger;
    }

}
