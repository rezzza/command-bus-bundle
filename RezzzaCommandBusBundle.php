<?php

namespace Rezzza\CommandBusBundle;

use Rezzza\CommandBusBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\DependencyInjection\RegisterListenersPass;

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
