<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests\Fixtures\Owner;

use Doctrine\ORM\Mapping as ORM;
use Survos\BookmarkBundle\Contract\BookmarkOwnerInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_user')]
class User implements BookmarkOwnerInterface
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 40)]
        private string $id,
    ) {}

    public function getId(): string { return $this->id; }
}
