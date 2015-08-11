<?php

namespace Rezzza\CommandBusBundle\DependencyInjection;

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

        foreach ($config['consumers'] as $name => $consumerConfig) {
            $this->createConsumer($name, $consumerConfig, $container);
        }

        foreach ($config['fail_strategies'] as $name => $failStrategyConfig) {
            $this->createFailStrategy($name, $failStrategyConfig, $container);
        }

        if (isset($config['handlers'])) {
            $this->loadHandlers($config['handlers'], $container);
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('services.xml');
    }

    private function createBus($name, array $config, ContainerBuilder $container)
    {
        switch ($config['id']) {
            case 'direct':
                $service = new Definition('%rezzza_command_bus.direct_bus.class%', [
                    new Reference('rezzza_command_bus.command_handler_locator.container'),
                        new Reference('rezzza_command_bus.event_dispatcher'),
                    $this->createLoggerReference()
                ]);
                $container->setDefinition($this->getCommandBusServiceName($name), $service);
                break;
            case 'snc_redis':
                $service = new Definition('%rezzza_command_bus.snc_redis_bus.class%', [
                    new Reference(sprintf('snc_redis.%s_client', $config['client'])),
                    $this->createRedisKeyGeneratorReference($config['key_generator']),
                    new Reference('rezzza_command_bus.event_dispatcher'),
                    $this->createLoggerReference()
                ]);
                $service->setLazy(true);
                // because snc redis will initiate connection, and we may not want it.
                $container->setDefinition($this->getCommandBusServiceName($name), $service);
                break;
            default:
                $container->setAlias($this->getCommandBusServiceName($name), $config['id']);
                break;
        }
    }

    private function createConsumer($name, array $config, ContainerBuilder $container)
    {
        switch ($config['provider']['id']) {
            case 'snc_redis':
                $provider = new Definition('%rezzza_command_bus.snc_redis_provider.class%', [
                    new Reference(sprintf('snc_redis.%s_client', $config['provider']['client'])),
                    $this->createRedisKeyGeneratorReference($config['provider']['key_generator'])
                ]);
                break;
            default:
                $provider = new Reference($config['provider']['id']);
                break;
        }

        $consumerDefinition = new Definition('%rezzza_command_bus.consumer.class%',
            [
                $provider,
                new Reference($this->getCommandBusServiceName($config['bus'])),
                new Reference($this->getFailStrategyServiceName($config['fail_strategy'])),
                new Reference('rezzza_command_bus.event_dispatcher')
            ]
        );

        $container->setDefinition(sprintf('rezzza_command_bus.command_bus.consumer.%s', $name), $consumerDefinition);
    }

    private function createFailStrategy($name, array $config, ContainerBuilder $container)
    {
        switch ($config['id']) {
            case 'retry_then_fail':
                $definition = new Definition('%rezzza_command_bus.fail_strategy.retry_then_fail.class%', [
                    new Reference($this->getCommandBusServiceName($config['bus'])),
                    $config['attempts'],
                    $config['requeue_on_fail'],
                    $this->createLoggerReference()
                ]);
                $container->setDefinition($this->getFailStrategyServiceName($name), $definition);
                break;
            case 'requeue':
                $definition = new Definition('%rezzza_command_bus.fail_strategy.requeue.class%', [
                    new Reference($this->getCommandBusServiceName($config['bus'])),
                    $this->createLoggerReference()
                ]);
                $container->setDefinition($this->getFailStrategyServiceName($name), $definition);
                break;
            case 'none':
                $definition = new Definition('%rezzza_command_bus.fail_strategy.none.class%', [
                    $this->createLoggerReference()
                ]);
                $container->setDefinition($this->getFailStrategyServiceName($name), $definition);
                break;
            default:
                $container->setAlias($this->getFailStrategyServiceName($name), $config['id']);
                break;
        }

    }

    public function loadHandlers(array $handlers, ContainerBuilder $container)
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

    private function getCommandBusServiceName($commandBus)
    {
        return sprintf('rezzza_command_bus.command_bus.%s', $commandBus);
    }

    private function getFailStrategyServiceName($failStrategy)
    {
        return sprintf('rezzza_command_bus.fail_strategy.%s', $failStrategy);
    }

    private function createRedisKeyGeneratorReference($service)
    {
        if (empty($service)) {
            return new Definition('Rezzza\CommandBus\Infra\Provider\Redis\RedisKeyGenerator');
        }

        return new Reference($service);
    }

    private function createLoggerReference()
    {
        return new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }
}
