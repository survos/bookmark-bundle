<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Contract;

/**
 * Local owner of bundle-owned bookmarks and folders. Doctrine resolves this
 * association target to owner_class; no inverse collections are required.
 * Legacy host-owned entities may continue without implementing this contract.
 */
interface BookmarkOwnerInterface
{
    public function getId(): int|string|null;
}
