<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\FieldBundle\Attribute\Field;
use Survos\FieldBundle\Enum\Widget;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

/**
 * Host-owned resource bookmark. Physical identity column names are retained for
 * schema compatibility: provider=source, dataset=collection, coreCode=class,
 * localId=identifier. No resource database or routing service is required.
 * Concrete hosts declare their own User and Folder associations.
 */
#[ORM\MappedSuperclass]
abstract class BookmarkBase
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    #[Groups(['bookmark:read'])]
    public Ulid $id;

    #[ORM\Column]
    #[Field(sortable: true, widget: Widget::Date, order: 90, format: 'datetime')]
    #[Groups(['bookmark:read'])]
    public \DateTimeImmutable $created;

    #[ORM\Column(length: 64)]
    #[Field(filterable: true, facet: true, order: 20)]
    #[Groups(['bookmark:read'])]
    public string $provider;

    #[ORM\Column(length: 128)]
    #[Field(filterable: true, facet: true, order: 21)]
    #[Groups(['bookmark:read'])]
    public string $dataset;

    #[ORM\Column(length: 64)]
    #[Field(filterable: true, order: 22)]
    #[Groups(['bookmark:read'])]
    public string $coreCode;

    #[ORM\Column(length: 180)]
    #[Groups(['bookmark:read'])]
    public string $localId;

    /** Display cache, snapshotted at bookmark time — not part of row identity. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Field(filterable: true, facet: true, order: 23)]
    #[Groups(['bookmark:read'])]
    public ?string $dtoType = null;

    /** Display cache, snapshotted at bookmark time — not part of row identity. */
    #[ORM\Column(length: 500, nullable: true)]
    #[Field(searchable: true, order: 30)]
    #[Groups(['bookmark:read'])]
    public ?string $label = null;

    public function reference(): \Survos\BookmarkBundle\Model\TargetReference
    {
        return new \Survos\BookmarkBundle\Model\TargetReference($this->provider, $this->coreCode, $this->localId, $this->dataset);
    }
}
