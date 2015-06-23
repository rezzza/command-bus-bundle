<?php

namespace Rezzza\CommandBusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 * @uses ConfigurationInterface
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
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
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function($v) { return is_scalar($v); })
                            ->then(function($v) { return ['id' => $v]; })
                        ->end()
                        ->validate()
                            ->ifTrue(function($v) {
                                return $v['id'] === 'snc_redis' && false === isset($v['client']);
                            })
                            ->thenInvalid('“snc_redis“ bus needs a client. See documentation.')
                        ->end()
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('client')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('consumers')
                    ->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('provider')
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return is_scalar($v); })
                                        ->then(function($v) { return ['id' => $v]; })
                                    ->end()
                                    ->validate()
                                        ->ifTrue(function($v) {
                                            return $v['id'] === 'snc_redis' && false === isset($v['client']);
                                        })
                                    ->thenInvalid('“snc_redis“ bus needs a client. See documentation.')
                                ->end()
                                ->children()
                                    ->scalarNode('id')->isRequired()->end()
                                    ->scalarNode('client')->end()
                                ->end()
                            ->end()
                            ->scalarNode('direct_bus')->isRequired()->end()
                            ->scalarNode('fail_strategy')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('fail_strategies')
                    ->beforeNormalization()
                        ->ifTrue(function($v) { return is_scalar($v); })
                        ->then(function($v) {
                            return ['id' => $v];
                        })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('bus')->end()
                            ->scalarNode('attempts')->defaultValue(100)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('handlers')
                    ->children()
                        ->arrayNode('retry')
                            ->children()
                                ->scalarNode('direct_bus')->isRequired()->end()
                                ->scalarNode('fail_strategy')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $tb;
    }
}
