<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Survos\BookmarkBundle\Contract\{BookmarkInterface, BookmarkOwnerInterface, FolderInterface};
use Survos\BookmarkBundle\Enum\FolderVisibility;
use Survos\BookmarkBundle\Repository\FolderRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\Table(name: 'folder')]
#[ORM\UniqueConstraint(name: 'uniq_folder_user_slug', fields: ['user', 'slug'])]
class Folder extends FolderBase implements FolderInterface
{
    /** @var Collection<int, BookmarkInterface> */
    #[ORM\OneToMany(targetEntity: BookmarkInterface::class, mappedBy: 'folder')]
    public protected(set) Collection $bookmarks;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: BookmarkOwnerInterface::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['folder:read'])]
        public BookmarkOwnerInterface $user,
        string $name,
        string $slug,
        FolderVisibility $visibility = FolderVisibility::Private,
    ) {
        $this->id = new Ulid();
        $this->created = new \DateTimeImmutable();
        $this->name = $name;
        $this->slug = $slug;
        $this->visibility = $visibility;
        $this->bookmarks = new ArrayCollection();
    }

    #[Groups(['folder:read'])]
    public int $bookmarkCount { get => $this->bookmarks->count(); }
}
