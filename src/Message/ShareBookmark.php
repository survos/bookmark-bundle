<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Message;

use Survos\BookmarkBundle\Model\BookmarkSnapshot;

/** Destination is a configured peer name. Credentials belong to its transport, not the queued message. */
final readonly class ShareBookmark
{
    public function __construct(public string $destination, public BookmarkSnapshot $bookmark) {}
}
