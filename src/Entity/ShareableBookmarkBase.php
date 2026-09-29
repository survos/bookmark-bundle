<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/** Opt-in metadata for arbitrary URL targets and portable personal annotations. */
#[ORM\MappedSuperclass]
abstract class ShareableBookmarkBase extends BookmarkBase
{
    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups(['bookmark:read'])]
    public ?string $targetUrl = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups(['bookmark:read'])]
    public ?string $origin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['bookmark:read', 'bookmark:write'])]
    public ?string $notes = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    #[Groups(['bookmark:read'])]
    public array $tags = [];
}
