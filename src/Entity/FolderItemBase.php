<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\FieldBundle\Attribute\Field;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

/**
 * One ordered entry of a folder: "this bookmark, at this position, in this folder.".
 *
 * Bookmark stays one row per (owner, target) with a single home `folder`. Folder membership
 * is many-to-many and ordered, though -- the same photo in several of one owner's albums, a
 * story's stops in sequence -- so membership and order live here. Holds no relations; the
 * concrete class declares folder and bookmark, like FolderBase/BookmarkBase.
 */
#[ORM\MappedSuperclass]
abstract class FolderItemBase
{
    /** Gap between appended positions, so an item can be inserted without renumbering. */
    public const int POSITION_STEP = 10;

    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    #[Groups(['folder_item:read'])]
    public Ulid $id;

    #[ORM\Column]
    #[Groups(['folder_item:read'])]
    public \DateTimeImmutable $created;

    /** Ascending sort key within the folder. */
    #[ORM\Column]
    #[Field(sortable: true, order: 20)]
    #[Groups(['folder_item:read', 'folder_item:write'])]
    public int $position = 0;
}
