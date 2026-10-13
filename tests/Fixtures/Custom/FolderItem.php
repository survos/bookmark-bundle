<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests\Fixtures\Custom;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'custom_folder_item')]
#[ORM\UniqueConstraint(name: 'uniq_custom_folder_item', fields: ['folder', 'bookmark'])]
class FolderItem extends \Survos\BookmarkBundle\Entity\FolderItem
{
    #[ORM\Column(nullable: true)]
    public ?int $sourceMembershipId = null;
}
