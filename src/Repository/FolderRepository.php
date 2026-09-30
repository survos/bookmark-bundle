<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Survos\BookmarkBundle\Entity\Folder;

final class FolderRepository extends FolderRepositoryBase
{
    public function __construct(ManagerRegistry $registry, string $folderClass = Folder::class)
    {
        parent::__construct($registry, $folderClass);
    }
}
