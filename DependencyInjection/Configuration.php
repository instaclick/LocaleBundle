<?php

namespace Lunetics\LocaleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lunetics_locale');

        $rootNode
            ->children()
                ->arrayNode('change_language')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('show_first_uppercase')
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('show_foreign_languagenames')
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('show_languagetitle')
                            ->defaultValue(false)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('allowed_languages')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                        ->validate()
                            ->ifTrue(function($v) { return 0 === preg_match('/^[a-z]{2}$/',$v); })
                            ->thenInvalid('The lunetics_locale.allowed_languages config %s is not a valid language.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('switch_router')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('redirect_to_route')
                        ->end()
                        ->scalarNode('redirect_to_url')
                            ->defaultValue('/')
                            ->cannotBeEmpty()
                        ->end()
                        ->booleanNode('use_referrer')
                            ->defaultValue(true)
                        ->end()
                    ->end()
            ->end();

        return $treeBuilder;
    }
}
