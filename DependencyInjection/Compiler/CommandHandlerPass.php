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
                $command = $this->getCommand($serviceId, $handlers, $data);

                if (null === $command) {
                    throw new \LogicException('Please provide a command with "rezzza_command.command_handler" tag');
                }

                $services[$command] = new Definition('Rezzza\CommandBusBundle\Handler\HandlerServiceDefinition', [
                    $serviceId,
                    isset($data['method']) ? $data['method'] : null
                ]);
            }
        }

        $container->getDefinition('rezzza_command_bus.command_handler_locator.container')->addArgument($services);
    }

    private function getCommand(string $serviceId, array $handlers, array $data): ?string
    {
        if (isset($data['command'])) {
            return $data['command'];
        }

        // if no command is defined the handler should manage only one command
        if (1 < \count($handlers)) {
            return null;
        }

        $command = $this->guessCommandFromHandlerName($serviceId);

        return class_exists($command) ? $command : null;
    }

    private function guessCommandFromHandlerName(string $handler): string
    {
        $parts = preg_split('/(?=[A-Z])/',$handler);
        array_pop($parts);

        return implode('', $parts);
    }
}
