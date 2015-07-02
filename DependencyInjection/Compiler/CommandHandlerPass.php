<?php

namespace Rezzza\CommandBusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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

                $services[$data['command']] = new Definition('Rezzza\CommandBusBundle\Handler\HandlerServiceDefinition', [
                    $serviceId,
                    isset($data['method']) ? $data['method'] : null
                ]);
            }
        }

        $container->getDefinition('rezzza_command_bus.command_handler_locator.container')->addArgument($services);
    }
}
