<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\BookmarkBundle\Model\{BookmarkSnapshot, TargetReference};
use Survos\BookmarkBundle\Service\BookmarkSharing;
use Survos\BookmarkBundle\Message\ShareBookmark;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class BookmarkCoreTest extends TestCase
{
    public function testSharingIsAnExplicitQueuedSnapshot(): void
    {
        $transport = new InMemoryTransport();
        $senders = new SendersLocator([ShareBookmark::class => ['peer']], new ServiceLocator(['peer' => fn () => $transport]));
        $sharing = new BookmarkSharing(new MessageBus([new SendMessageMiddleware($senders)]), ['recordia']);
        $snapshot = new BookmarkSnapshot(new TargetReference('pressia.example', 'Article', '42'),
            'https://pressia.example/articles/42', 'Title', 'My notes', ['history'], 'https://pressia.example/bookmarks/123');
        self::assertCount(0, $transport->getSent());
        $sharing->share('recordia', $snapshot);
        self::assertCount(1, $transport->getSent());
        $message = $transport->getSent()[0]->getMessage();
        self::assertSame('recordia', $message->destination);
        self::assertSame($snapshot, $message->bookmark);
        $this->expectException(\InvalidArgumentException::class);
        $sharing->share('unconfigured-peer', $snapshot);
    }

    public function testLegacyEnumAndManagerAreTheCanonicalTypes(): void
    {
        self::assertSame(\Survos\BookmarkBundle\Enum\FolderVisibility::Private,
            \Survos\FolioBundle\Bookmark\Enum\FolderVisibility::Private);
        self::assertTrue(is_a(\Survos\FolioBundle\Bookmark\Service\BookmarkManager::class,
            \Survos\BookmarkBundle\Service\BookmarkManager::class, true));
        self::assertTrue(is_subclass_of(\Survos\FolioBundle\Bookmark\Entity\BookmarkBase::class,
            \Survos\BookmarkBundle\Entity\BookmarkBase::class));
    }

    public function testUnsafeUrlCannotEnterSharingSnapshot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BookmarkSnapshot(new TargetReference('pressia', 'Article', '42'), 'javascript:alert(1)');
    }
}
