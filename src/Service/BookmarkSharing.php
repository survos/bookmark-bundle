<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Service;

use Survos\BookmarkBundle\Message\ShareBookmark;
use Survos\BookmarkBundle\Model\BookmarkSnapshot;
use Symfony\Component\Messenger\MessageBusInterface;

/** Explicit dispatch only. Saving and receiving never call this service automatically. */
final readonly class BookmarkSharing
{
    public function __construct(private MessageBusInterface $bus, private array $destinations = []) {}

    public function share(string $destination, BookmarkSnapshot $bookmark): void
    {
        if (!in_array($destination, $this->destinations, true)) {
            throw new \InvalidArgumentException('Bookmark sharing destination is not enabled.');
        }
        $this->bus->dispatch(new ShareBookmark($destination, $bookmark));
    }
}
