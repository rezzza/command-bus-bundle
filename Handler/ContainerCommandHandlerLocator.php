<?php

namespace Rezzza\CommandBusBundle\Handler;

use Rezzza\CommandBus\Domain\CommandInterface;
use Rezzza\CommandBus\Domain\Exception\CommandHandlerNotFoundException;
use Rezzza\CommandBus\Domain\Handler\CommandHandlerLocatorInterface;
use Rezzza\CommandBus\Domain\Handler\HandlerDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerCommandHandlerLocator implements CommandHandlerLocatorInterface
{
    private $container;
    private $handlers = [];

    /**
     * @param array $handlers handlers
     */
    public function __construct(ContainerInterface $container, array $handlers = array())
    {
        $this->container = $container;
        $this->handlers  = $handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandHandler(CommandInterface $command)
    {
        $commandClass = get_class($command);

        if (false === array_key_exists($commandClass, $this->handlers)) {
            throw new CommandHandlerNotFoundException($command);
        }

        $definition = $this->handlers[$commandClass];

        if (false === $definition instanceof HandlerServiceDefinition) {
            return $definition;
        }

        return new HandlerDefinition(
            $this->container->get($definition->getService()),
            $definition->getMethod()
        );
    }
}
