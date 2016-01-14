<?php

namespace Rezzza\CommandBusBundle\Provider\SncRedis;

use Rezzza\CommandBus\Domain\Serializer\CommandSerializerInterface;
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
     * @param object                     $client          client
     * @param RedisKeyGeneratorInterface $keyGenerator    keyGenerator
     * @param CommandSerializerInterface $serializer      serializer
     */
    public function __construct($client, RedisKeyGeneratorInterface $keyGenerator, CommandSerializerInterface $serializer)
    {
        $this->client          = $client;
        $this->keyGenerator    = $keyGenerator;
        $this->serializer      = $serializer;
    }
}
