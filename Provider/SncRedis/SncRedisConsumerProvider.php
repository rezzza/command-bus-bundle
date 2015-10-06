<?php

namespace Rezzza\CommandBusBundle\Provider\SncRedis;

use Rezzza\CommandBus\Domain\Serializer\CommandSerializerInterface;
use Rezzza\CommandBus\Infra\Provider\Redis\RedisConsumerProvider;
use Rezzza\CommandBus\Infra\Provider\Redis\RedisKeyGeneratorInterface;

/**
 * SncRedisConsumerProvider
 *
 * @uses RedisConsumerProvider
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class SncRedisConsumerProvider extends RedisConsumerProvider
{
    /**
     * @param object                     $client           client
     * @param RedisKeyGeneratorInterface $keyGenerator     keyGenerator
     * @param CommandSerializerInterface $serializer       serializer
     * @param int                        $readBlockTimeout readBlockTimeout
     */
    public function __construct($client, RedisKeyGeneratorInterface $keyGenerator, CommandSerializerInterface $serializer, $readBlockTimeout = 0)
    {
        $this->client           = $client;
        $this->keyGenerator     = $keyGenerator;
        $this->serializer       = $serializer;
        $this->readBlockTimeout = $readBlockTimeout;
    }
}
