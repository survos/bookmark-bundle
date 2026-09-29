<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle;

use Survos\BookmarkBundle\Service\{BookmarkManager, BookmarkSharing};
use Survos\Kit\AbstractSurvosBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosBookmarkBundle extends AbstractSurvosBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()
            ->scalarNode('bookmark_class')->defaultNull()->end()
            ->scalarNode('folder_class')->defaultNull()->end()
            ->arrayNode('sharing')->addDefaultsIfNotSet()->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->arrayNode('destinations')->scalarPrototype()->end()->defaultValue([])->end()
            ->end()->end()
        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        if (($config['bookmark_class'] === null) !== ($config['folder_class'] === null)) {
            throw new \InvalidArgumentException('Configure bookmark_class and folder_class together.');
        }
        if ($config['bookmark_class'] !== null) {
            $container->services()->set(BookmarkManager::class)->autowire()->autoconfigure()->public()->args([
                '$bookmarkClass' => $config['bookmark_class'], '$folderClass' => $config['folder_class'],
            ]);
        }
        if ($config['sharing']['enabled']) {
            $container->services()->set(BookmarkSharing::class)->autowire()->autoconfigure()
                ->arg('$destinations', $config['sharing']['destinations']);
        }
    }
}
