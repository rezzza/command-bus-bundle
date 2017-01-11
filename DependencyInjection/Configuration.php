<?php

namespace Rezzza\CommandBusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Psr\Log\LogLevel;

/**
 * Configuration
 *
 * @uses ConfigurationInterface
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    CONST PRIORITY_HIGH = 'high';
    CONST PRIORITY_LOW  = 'low';

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $tb->root('rezzza_command_bus')
            ->children()
                ->arrayNode('buses')
                    ->cannotBeEmpty()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function($v) { return is_scalar($v); })
                            ->then(function($v) { return [$v => []]; })
                        ->end()
                        ->validate()
                            ->ifTrue(function($v) { return count($v) > 1; })
                            ->thenInvalid("You can't have more than bus provider defined.")
                        ->end()
                        ->children()
                            ->arrayNode('direct')->end()
                            ->arrayNode('snc_redis')
                                ->children()
                                    ->scalarNode('client')->isRequired()->end()
                                    ->integerNode('read_block_timeout')->info('Wait x second to read in storage, see BLPOP documentation.')->defaultValue(1)->end()
                                    ->scalarNode('key_generator')->defaultValue('rezzza_command_bus.redis_key_generator')->end()
                                    ->scalarNode('serializer')->defaultValue('rezzza_command_bus.command_serializer')->end()
                                    ->append($this->createConsumersNodeDefinition())
                                ->end()
                            ->end()
                            ->arrayNode('rabbitmq')
                                ->children()
                                    ->scalarNode('producer_guesser')->defaultValue('rezzza_command_bus.old_sound_rabbit.producer_guesser')->end()
                                    ->scalarNode('consumer_bus')->defaultNull()->end()
                                ->end()
                            ->end()
                            ->arrayNode('service')
                                ->children()
                                    ->scalarNode('id')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('handlers')
                    ->children()
                        ->append($this->createHandlerNodeDefinition('retry'))
                        ->append($this->createHandlerNodeDefinition('failed'))
                    ->end()
                ->end()
                ->scalarNode('logger_normalizer')
                    ->defaultValue('serializer')
                ->end()
                ->arrayNode('logger_log_level')
                    ->children()
                        ->scalarNode('handle')->default(LogLevel::NOTICE)->end()
                        ->scalarNode('error')->default(LogLevel::ERROR)->end()
                    ->end()
                ->end()
            ->end();

        return $tb;
    }

    private function createConsumersNodeDefinition()
    {
        return (new ArrayNodeDefinition('consumers'))
            ->cannotBeEmpty()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('provider')->defaultNull()->end()
                    ->scalarNode('bus')->isRequired()->end()
                    ->arrayNode('fail_strategy')
                        ->validate()
                            ->ifTrue(function($v) { return count($v) > 1; })
                            ->thenInvalid("You can't have more than one fail strategy defined.")
                        ->end()
                        ->isRequired()
                        ->children()
                            ->arrayNode('retry_then_fail')
                                ->children()
                                    ->scalarNode('bus')->end()
                                    ->scalarNode('attempts')->defaultValue(100)->end()
                                    ->booleanNode('requeue_on_fail')->defaultTrue()->end()
                                    ->append($this->createPriorityNodeDefinition())
                                ->end()
                            ->end()
                            ->arrayNode('requeue')
                                ->children()
                                    ->scalarNode('bus')->end()
                                    ->append($this->createPriorityNodeDefinition())
                                ->end()
                            ->end()
                            ->arrayNode('none')->end()
                            ->arrayNode('service')
                                ->children()
                                    ->scalarNode('id')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function createHandlerNodeDefinition($name)
    {
        return (new ArrayNodeDefinition($name))
            ->beforeNormalization()
                ->ifTrue(function($v) { return is_scalar($v); })
                ->then(function($v) {
                    return ['direct_bus' => $v];
                })
            ->end()
            ->children()
                ->scalarNode('direct_bus')->isRequired()->end()
            ->end();
    }

    private function createPriorityNodeDefinition()
    {
        return (new EnumNodeDefinition('priority'))
            ->values([self::PRIORITY_HIGH, self::PRIORITY_LOW])
            ->info('Strategy will act in TOP or BOTTOM of the queue ?')
            ->defaultValue(self::PRIORITY_LOW);
    }
}
