<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\BookmarkBundle\Contract\{BookmarkInterface, FolderInterface, FolderItemInterface};
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'folder_item')]
#[ORM\UniqueConstraint(name: 'uniq_folder_item_bookmark', fields: ['folder', 'bookmark'])]
#[ORM\Index(name: 'idx_folder_item_position', fields: ['folder', 'position'])]
class FolderItem extends FolderItemBase implements FolderItemInterface
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: FolderInterface::class, inversedBy: 'items')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['folder_item:read'])]
        public protected(set) FolderInterface $folder,

        #[ORM\ManyToOne(targetEntity: BookmarkInterface::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['folder_item:read'])]
        public protected(set) BookmarkInterface $bookmark,

        int $position = 0,
    ) {
        if ($bookmark->user !== $folder->user) {
            throw new \InvalidArgumentException('A folder item must place a bookmark in a folder of the same owner.');
        }
        $this->id = new Ulid();
        $this->created = new \DateTimeImmutable();
        $this->position = $position;
    }
}
