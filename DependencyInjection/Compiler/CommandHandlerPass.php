<?php

namespace Rezzza\CommandBusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * CommandHandlerPass
 *
 * @uses CompilerPassInterface
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class CommandHandlerPass implements CompilerPassInterface
{
     /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = array();

        foreach ($container->findTaggedServiceIds('rezzza_command_bus.command_handler') as $serviceId => $handlers) {
            foreach ($handlers as $data) {
                if (!isset($data['command'])) {
                    throw new \LogicException('Please provide a command with "rezzza_command.command_handler" tag');
                }

                $services[$data['command']] = $serviceId;
            }
        }

        $container->getDefinition('rezzza_command_bus.command_handler_locator.container')->addArgument($services);
    }
}
