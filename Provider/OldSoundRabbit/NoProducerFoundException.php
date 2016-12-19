<?php

namespace Rezzza\CommandBusBundle\Provider\OldSoundRabbit;

use Rezzza\CommandBus\Domain\CommandInterface;

/**
 * Class NoProducerFoundException
 */
class NoProducerFoundException extends \LogicException
{
    public function __construct(CommandInterface $command)
    {
        $message = sprintf('Producer not found for Command [%s]', get_class($command));

        parent::__construct($message);
    }
}
