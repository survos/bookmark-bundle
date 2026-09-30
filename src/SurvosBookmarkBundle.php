<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle;

use Survos\BookmarkBundle\Service\{BookmarkManager, BookmarkSharing};
use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\Traits\HasDoctrineEntities;
use Survos\BookmarkBundle\Entity\{Bookmark, Folder};
use Survos\BookmarkBundle\Contract\{BookmarkInterface, BookmarkOwnerInterface, FolderInterface};
use Survos\BookmarkBundle\Repository\{BookmarkRepository, FolderRepository};
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosBookmarkBundle extends AbstractSurvosBundle
{
    use HasDoctrineEntities { prependDoctrineMapping as private prependDefaultDoctrineMapping; }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()
            ->booleanNode('auto_mapping')->defaultTrue()->end()
            ->scalarNode('owner_class')->defaultNull()->end()
            ->scalarNode('bookmark_class')->defaultValue(Bookmark::class)->cannotBeEmpty()->end()
            ->scalarNode('folder_class')->defaultValue(Folder::class)->cannotBeEmpty()->end()
            ->arrayNode('sharing')->addDefaultsIfNotSet()->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->arrayNode('destinations')->scalarPrototype()->end()->defaultValue([])->end()
            ->end()->end()
        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        $this->validateEntityConfiguration($config);
        $container->services()->set(BookmarkManager::class)->autowire()->autoconfigure()->public()->args([
            '$bookmarkClass' => $config['bookmark_class'], '$folderClass' => $config['folder_class'],
        ]);
        $container->services()->set(BookmarkRepository::class)->autowire()->autoconfigure()->public()
            ->arg('$bookmarkClass', $config['bookmark_class']);
        $container->services()->set(FolderRepository::class)->autowire()->autoconfigure()->public()
            ->arg('$folderClass', $config['folder_class']);
        if ($config['sharing']['enabled']) {
            $container->services()->set(BookmarkSharing::class)->autowire()->autoconfigure()
                ->arg('$destinations', $config['sharing']['destinations']);
        }
    }

    protected function prependDoctrineMapping(ContainerBuilder $builder): void
    {
        $extension = $this->getContainerExtension();
        $config = (new Processor())->processConfiguration(
            $extension->getConfiguration([], $builder), $builder->getExtensionConfig($extension->getAlias()),
        );
        $this->validateEntityConfiguration($config);
        if ($config['auto_mapping']) {
            $this->prependDefaultDoctrineMapping($builder);
        }
        $targets = [];
        if (is_a($config['bookmark_class'], BookmarkInterface::class, true)) {
            $targets[BookmarkInterface::class] = $config['bookmark_class'];
        }
        if (is_a($config['folder_class'], FolderInterface::class, true)) {
            $targets[FolderInterface::class] = $config['folder_class'];
        }
        if ($config['owner_class'] !== null) {
            $targets[BookmarkOwnerInterface::class] = $config['owner_class'];
        }
        if ($targets !== []) {
            $builder->prependExtensionConfig('doctrine', ['orm' => ['resolve_target_entities' => $targets]]);
        }
    }

    private function validateEntityConfiguration(array $config): void
    {
        if ($config['auto_mapping'] && ($config['bookmark_class'] !== Bookmark::class || $config['folder_class'] !== Folder::class)) {
            throw new \InvalidArgumentException('Disable survos_bookmark.auto_mapping when configuring custom bookmark/folder entities.');
        }
        if (!$config['auto_mapping'] && ($config['bookmark_class'] === Bookmark::class || $config['folder_class'] === Folder::class)) {
            throw new \InvalidArgumentException('With auto_mapping disabled, configure both custom bookmark_class and folder_class.');
        }
        $usesOwnerContract = is_a($config['bookmark_class'], Bookmark::class, true) || is_a($config['folder_class'], Folder::class, true);
        if ($usesOwnerContract && ($config['owner_class'] === null || !is_a($config['owner_class'], BookmarkOwnerInterface::class, true))) {
            throw new \InvalidArgumentException('Configure owner_class with an entity implementing BookmarkOwnerInterface.');
        }
    }
}
