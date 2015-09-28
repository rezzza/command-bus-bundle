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
            ->beforeNormalization()
                ->always(function($v) {
                    if (false === isset($v['consumers'])) {
                        return $v;
                    }

                    foreach ($v['consumers'] as $k => $consumer) {
                        if (is_scalar($consumer['provider'])) {
                            $provider = $consumer['provider'];
                            if (false === array_key_exists($provider, $v['buses'])) {
                                throw new \InvalidArgumentException(sprintf('Provider “%s“ unknown', $provider));
                            }

                            $v['consumers'][$k]['provider'] = $v['buses'][$provider];
                        }
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->arrayNode('buses')
                    ->cannotBeEmpty()
                    ->useAttributeAsKey('name')
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
                            ->scalarNode('key_generator')->defaultNull()->end()
                            ->scalarNode('client')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('consumers')
                    ->cannotBeEmpty()
                    ->useAttributeAsKey('name')
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
                                    ->scalarNode('key_generator')->defaultNull()->end()
                                    ->scalarNode('client')->end()
                                ->end()
                            ->end()
                            ->scalarNode('bus')->isRequired()->end()
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
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('bus')->end()
                            ->scalarNode('attempts')->defaultValue(100)->end()
                            ->booleanNode('requeue_on_fail')->defaultTrue()->end()
                            ->enumNode('priority')
                                ->values([self::PRIORITY_HIGH, self::PRIORITY_LOW])
                                ->info('Strategy will act in TOP or BOTTOM of the queue ?')
                                ->defaultValue(self::PRIORITY_LOW)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('handlers')
                    ->children()
                        ->arrayNode('retry')
                            ->beforeNormalization()
                                ->ifTrue(function($v) { return is_scalar($v); })
                                ->then(function($v) {
                                    return ['direct_bus' => $v];
                                })
                            ->end()
                            ->children()
                                ->scalarNode('direct_bus')->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode('failed')
                            ->beforeNormalization()
                                ->ifTrue(function($v) { return is_scalar($v); })
                                ->then(function($v) {
                                    return ['direct_bus' => $v];
                                })
                            ->end()
                            ->children()
                                ->scalarNode('direct_bus')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $tb;
    }
}
