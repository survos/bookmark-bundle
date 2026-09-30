<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Survos\BookmarkBundle\SurvosBookmarkBundle;

final class BookmarkKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private readonly bool $custom, private readonly array $overrides = [])
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new SurvosBookmarkBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/survos-bookmark-modes-'.getmypid().'/'.($this->custom ? 'custom' : 'default').'-'.md5(serialize($this->overrides));
    }

    public function getLogDir(): string { return $this->getCacheDir().'/logs'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['secret' => 'bookmark-test', 'test' => true]);
        $mapping = ['Owner' => ['type' => 'attribute', 'dir' => __DIR__.'/Owner',
            'prefix' => __NAMESPACE__.'\\Owner', 'is_bundle' => false]];
        if ($this->custom) {
            $mapping['Custom'] = ['type' => 'attribute', 'dir' => __DIR__.'/Custom',
                'prefix' => __NAMESPACE__.'\\Custom', 'is_bundle' => false];
        }
        $container->extension('doctrine', [
            'dbal' => ['logging' => false, 'profiling' => false, 'url' => 'sqlite:///:memory:', 'types' => ['ulid' => \Symfony\Bridge\Doctrine\Types\UlidType::class]],
            'orm' => ['auto_mapping' => false, 'mappings' => $mapping],
        ]);
        $config = ['owner_class' => Owner\User::class];
        if ($this->custom) {
            $config += ['auto_mapping' => false, 'bookmark_class' => Custom\Bookmark::class, 'folder_class' => Custom\Folder::class];
        }
        $container->extension('survos_bookmark', array_replace($config, $this->overrides));
    }
}
