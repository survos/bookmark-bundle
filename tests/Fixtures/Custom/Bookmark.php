<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests\Fixtures\Custom;

use Doctrine\ORM\Mapping as ORM;
use Survos\BookmarkBundle\Repository\BookmarkRepository;

#[ORM\Entity(repositoryClass: BookmarkRepository::class)]
#[ORM\Table(name: 'custom_bookmark')]
#[ORM\UniqueConstraint(name: 'uniq_custom_bookmark', fields: ['user', 'provider', 'dataset', 'coreCode', 'localId'])]
class Bookmark extends \Survos\BookmarkBundle\Entity\Bookmark
{
    #[ORM\Column(length: 100)]
    public string $annotation = 'custom';
}
