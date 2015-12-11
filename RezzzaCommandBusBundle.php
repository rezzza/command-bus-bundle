<?php

namespace Rezzza\CommandBusBundle;

use Rezzza\CommandBusBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RezzzaCommandBusBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\CommandHandlerPass());

        $container->addCompilerPass(new RegisterListenersPass(
            'rezzza_command_bus.event_dispatcher',
            'rezzza_command_bus.event_listener',
            'rezzza_command_bus.event_subscriber'
        ));
    }
}
