<?php

namespace Rezzza\CommandBusBundle\Provider\OldSoundRabbit;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Rezzza\CommandBus\Domain\CommandBusInterface;

/**
 * Class DefaultConsumer
 */
class DefaultConsumer implements ConsumerInterface
{
    protected $bus;

    /**
     * Constructor.
     *
     * @param CommandBusInterface $bus
     */
    public function __construct(CommandBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $command)
    {
        $this->bus->handle(unserialize($command->body));
    }
}
