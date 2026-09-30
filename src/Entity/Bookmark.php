<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\BookmarkBundle\Contract\{BookmarkInterface, BookmarkOwnerInterface, FolderInterface};
use Survos\BookmarkBundle\Repository\BookmarkRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: BookmarkRepository::class)]
#[ORM\Table(name: 'bookmark')]
#[ORM\UniqueConstraint(name: 'uniq_bookmark_row', fields: ['user', 'provider', 'dataset', 'coreCode', 'localId'])]
class Bookmark extends ShareableBookmarkBase implements BookmarkInterface
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: BookmarkOwnerInterface::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['bookmark:read'])]
        public BookmarkOwnerInterface $user,
        string $provider,
        string $dataset,
        string $coreCode,
        string $localId,
        #[ORM\ManyToOne(targetEntity: FolderInterface::class, inversedBy: 'bookmarks')]
        #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
        #[Groups(['bookmark:read', 'bookmark:write'])]
        public ?FolderInterface $folder = null,
        ?string $dtoType = null,
        ?string $label = null,
        ?string $notes = null,
    ) {
        $this->id = new Ulid();
        $this->created = new \DateTimeImmutable();
        $this->provider = $provider;
        $this->dataset = $dataset;
        $this->coreCode = $coreCode;
        $this->localId = $localId;
        $this->dtoType = $dtoType;
        $this->label = $label;
        $this->notes = $notes;
    }
}
