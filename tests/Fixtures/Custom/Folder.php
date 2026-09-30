<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests\Fixtures\Custom;

use Doctrine\ORM\Mapping as ORM;
use Survos\BookmarkBundle\Repository\FolderRepository;

#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\Table(name: 'custom_folder')]
#[ORM\UniqueConstraint(name: 'uniq_custom_folder', fields: ['user', 'slug'])]
class Folder extends \Survos\BookmarkBundle\Entity\Folder
{
    #[ORM\Column(length: 30)]
    public string $colour = 'blue';
}
