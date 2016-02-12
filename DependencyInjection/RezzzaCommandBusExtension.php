<?php

namespace Rezzza\CommandBusBundle\DependencyInjection;

use Rezzza\CommandBus\Domain\CommandBusInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RezzzaCommandBusExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config    = $processor->processConfiguration(new Configuration(), $configs);

        foreach ($config['buses'] as $name => $busConfig) {
            $this->createBus($name, $busConfig, $container);
        }

        if (isset($config['handlers'])) {
            $this->loadHandlers($config['handlers'], $container);
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('services.xml');
    }

    private function createBus($name, array $config, ContainerBuilder $container)
    {
        $providerName          = current(array_keys($config));
        $commandBusServiceName = $this->getCommandBusServiceName($name);
        $config                = current($config);

        switch ($providerName) {
            case 'direct':
                $service = new Definition('%rezzza_command_bus.direct_bus.class%', [
                    new Reference('rezzza_command_bus.command_handler_locator.container'),
                    new Reference('rezzza_command_bus.command_handler.method_resolver')
                ]);
                $container->setDefinition($commandBusServiceName, $service);
                $this->decorateBus($commandBusServiceName, $container);
                break;
            case 'snc_redis':
                $this->createSncRedisBusCommandBus($commandBusServiceName, $config, $container);
                $this->decorateBus($commandBusServiceName, $container);
                break;
            case 'service':
                $container->setAlias($commandBusServiceName, $config['id']);
                break;
            default:
                throw new \LogicException(sprintf('Unknown command bus provider "%s"', $providerName));
                break;
        }
    }

    private function createSncRedisBusCommandBus($commandBusServiceName, $config, ContainerBuilder $container)
    {
        $client       = new Reference(sprintf('snc_redis.%s_client', $config['client']));
        $serializer   = new Reference($config['serializer']);
        $keyGenerator = new Reference($config['key_generator']);

        $service = new Definition('%rezzza_command_bus.snc_redis_bus.class%', [
            $client,
            $keyGenerator,
            $serializer,
        ]);
        $service->setLazy(true);
        // because snc redis will initiate connection, and we may not want it.
        $container->setDefinition($commandBusServiceName, $service);

        $defaultConsumerProvider = new Definition('%rezzza_command_bus.snc_redis_provider.class%', [
            $client,
            $keyGenerator,
            $serializer,
            $config['read_block_timeout']
        ]);

        foreach ($config['consumers'] as $consumerName => $consumerConfig) {
            $this->createConsumerDefinition($consumerName, $defaultConsumerProvider, $consumerConfig, $commandBusServiceName, $container);
        }
    }

    private function createConsumerDefinition($name, Definition $defaultProvider, array $config, $commandBusServiceName, ContainerBuilder $container)
    {
        $consumerDefinition = new Definition('%rezzza_command_bus.consumer.class%',
            [
                $config['provider'] !== null ? new Reference($config['provider']) : $defaultProvider,
                new Reference($this->getCommandBusServiceName($config['bus'])),
                $this->createFailStrategyDefinition($config['fail_strategy'], $commandBusServiceName),
                new Reference('rezzza_command_bus.event_dispatcher')
            ]
        );

        $container->setDefinition(sprintf('rezzza_command_bus.command_bus.consumer.%s', $name), $consumerDefinition);
    }

    private function createFailStrategyDefinition(array $config, $commandBusServiceName)
    {
        $name   = current(array_keys($config));
        $config = current($config);

        switch ($name) {
            case 'retry_then_fail':
                return new Definition('%rezzza_command_bus.fail_strategy.retry_then_fail.class%', [
                    new Reference($commandBusServiceName),
                    $config['attempts'],
                    $config['requeue_on_fail'],
                    $this->getPriorityValue($config['priority']),
                    $this->createLoggerReference()
                ]);

                break;
            case 'requeue':
                return new Definition('%rezzza_command_bus.fail_strategy.requeue.class%', [
                    new Reference($commandBusServiceName),
                    $this->getPriorityValue($config['priority']),
                    $this->createLoggerReference()
                ]);
                break;
            case 'none':
                return new Definition('%rezzza_command_bus.fail_strategy.none.class%', [
                    $this->createLoggerReference()
                ]);
                break;
            case 'service':
                return new Reference($config['id']);
                break;
            default:
                throw new \LogicException(sprintf('Unknown fail strategy "%s"', $failStrategyName));
                break;
        }
    }

    private function loadHandlers(array $handlers, ContainerBuilder $container)
    {
        if (isset($handlers['retry'])) {
            $config = $handlers['retry'];

            $definition = new Definition('%rezzza_command_bus.handler.retry_handler.class%', [
                    new Reference($this->getCommandBusServiceName($config['direct_bus'])),
                    $this->createLoggerReference()
                ]
            );
            $definition->addTag('rezzza_command_bus.command_handler', ['command' => 'Rezzza\CommandBus\Domain\Command\RetryCommand']);

            $container->setDefinition('rezzza_command_bus.command_handler.retry', $definition);
        }

        if (isset($handlers['failed'])) {
            $config = $handlers['failed'];

            $definition = new Definition('%rezzza_command_bus.handler.failed_handler.class%', [
                    new Reference($this->getCommandBusServiceName($config['direct_bus'])),
                    $this->createLoggerReference()
                ]
            );
            $definition->addTag('rezzza_command_bus.command_handler', ['command' => 'Rezzza\CommandBus\Domain\Command\FailedCommand']);

            $container->setDefinition('rezzza_command_bus.command_handler.failed', $definition);
        }
    }

    private function decorateBus($busServiceId, ContainerBuilder $container)
    {
        $originalBusServiceId = $busServiceId.'.original';
        $container
            ->register($busServiceId.'.with_event_dispatcher', '%rezzza_command_bus.event_dispatcher_bus.class%')
            ->addArgument(new Reference('rezzza_command_bus.event_dispatcher'))
            ->addArgument(new Reference($originalBusServiceId))
            ->setDecoratedService($busServiceId, $originalBusServiceId);
        ;

        $container
            ->register($busServiceId.'.with_logger', '%rezzza_command_bus.logger_bus.class%')
            ->addArgument(new Reference('logger'))
            ->addArgument(new Reference($busServiceId.'.with_event_dispatcher'))
            ->addArgument(new Reference('rezzza_command_bus.command_serializer'))
            ->setDecoratedService($busServiceId);
        ;
    }

    private function getCommandBusServiceName($commandBus)
    {
        return sprintf('rezzza_command_bus.command_bus.%s', $commandBus);
    }

    private function getPriorityValue($priority)
    {
        switch ($priority) {
            case Configuration::PRIORITY_HIGH:
                return CommandBusInterface::PRIORITY_HIGH;
            break;
            default:
                return CommandBusInterface::PRIORITY_LOW;
            break;
        }
    }

    private function createLoggerReference()
    {
        return new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }
}
