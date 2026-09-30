<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Survos\BookmarkBundle\Entity\Bookmark;

final class BookmarkRepository extends BookmarkRepositoryBase
{
    public function __construct(ManagerRegistry $registry, string $bookmarkClass = Bookmark::class)
    {
        parent::__construct($registry, $bookmarkClass);
    }
}
