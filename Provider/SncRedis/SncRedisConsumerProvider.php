<?php

namespace Rezzza\CommandBusBundle\Provider\SncRedis;

use Rezzza\CommandBus\Infra\Provider\Redis\RedisConsumerProvider;

/**
 * SncRedisConsumerProvider
 *
 * @uses RedisConsumerProvider
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class SncRedisConsumerProvider extends RedisConsumerProvider
{
    /**
     * @param object $client client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

}
