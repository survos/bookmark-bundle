<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests;

use Doctrine\ORM\Tools\{SchemaTool, SchemaValidator};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Survos\BookmarkBundle\Entity\{Bookmark, Folder};
use Survos\BookmarkBundle\Model\{BookmarkSnapshot, TargetReference};
use Survos\BookmarkBundle\Repository\{BookmarkRepository, FolderRepository};
use Survos\BookmarkBundle\Service\BookmarkManager;
use Survos\BookmarkBundle\Tests\Fixtures\{BookmarkKernel, Custom, Owner\User};

final class EntityModesTest extends TestCase
{
    public static function modes(): array { return ['bundle entities' => [false], 'app subclasses' => [true]]; }

    #[DataProvider('modes')]
    public function testEntitiesPersistWithResolvedRelationsAndNoInheritanceTables(bool $custom): void
    {
        $kernel = new BookmarkKernel($custom);
        $kernel->boot();
        try {
            $container = $kernel->getContainer()->get('test.service_container');
            $em = $container->get('doctrine')->getManager();
            $metadata = $em->getMetadataFactory()->getAllMetadata();
            self::assertSame([], (new SchemaValidator($em))->validateMapping());
            (new SchemaTool($em))->createSchema($metadata);
            self::assertEqualsCanonicalizing($custom ? ['test_user', 'custom_bookmark', 'custom_folder'] : ['test_user', 'bookmark', 'folder'],
                $em->getConnection()->createSchemaManager()->listTableNames());
            $bookmarkClass = $custom ? Custom\Bookmark::class : Bookmark::class;
            $folderClass = $custom ? Custom\Folder::class : Folder::class;
            self::assertSame($bookmarkClass, $em->getClassMetadata($bookmarkClass)->rootEntityName);
            self::assertFalse($em->getClassMetadata($bookmarkClass)->isInheritanceTypeSingleTable());
            self::assertSame($folderClass, $em->getClassMetadata($bookmarkClass)->getAssociationTargetClass('folder'));
            self::assertSame(User::class, $em->getClassMetadata($bookmarkClass)->getAssociationTargetClass('user'));
            $owner = new User('local-owner');
            $other = new User('another-owner');
            $em->persist($owner);
            $em->persist($other);
            $em->flush();
            $manager = $container->get(BookmarkManager::class);
            $folder = $manager->createFolder($owner, 'Research');
            $target = new TargetReference('pressia.example', 'Article', '42');
            $bookmark = $manager->save($owner, $target, $folder, 'Title');
            self::assertInstanceOf($bookmarkClass, $bookmark);
            self::assertInstanceOf($folderClass, $folder);
            $id = $bookmark->id;
            $folderId = $folder->id;
            $em->clear();
            $bookmark = $em->find($bookmarkClass, $id);
            self::assertSame('local-owner', $bookmark->user->getId());
            self::assertSame(1, $em->find($folderClass, $folderId)->bookmarkCount);
            self::assertSame($bookmark, $container->get(BookmarkRepository::class)->findOneByTarget($bookmark->user, $target));
            self::assertCount(1, $container->get(FolderRepository::class)->findForUser($bookmark->user));
            if ($custom) self::assertSame('custom', $bookmark->annotation);
            $receipt = $manager->receive($bookmark->user, new BookmarkSnapshot($target, 'https://pressia.example/42'));
            self::assertSame($bookmark, $receipt);
            $newTarget = new TargetReference('another.example', 'Photo', '43');
            $newReceipt = $manager->receive($bookmark->user, new BookmarkSnapshot($newTarget,
                'https://another.example/43', notes: 'A note', tags: ['history']));
            self::assertInstanceOf($bookmarkClass, $newReceipt);
            self::assertSame(['history'], $newReceipt->tags);
            self::assertSame('A note', $newReceipt->notes);
            $foreignFolder = $manager->createFolder($em->find(User::class, 'another-owner'), 'Private');
            $this->expectException(\InvalidArgumentException::class);
            $manager->move($bookmark, $foreignFolder);
        } finally {
            $kernel->shutdown();
        }
    }

    public function testCustomClassesRequireMappingToBeDisabled(): void
    {
        $kernel = new BookmarkKernel(false, ['bookmark_class' => Custom\Bookmark::class, 'folder_class' => Custom\Folder::class]);
        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Disable survos_bookmark.auto_mapping');
            $kernel->boot();
        } finally {
            $kernel->shutdown();
        }
    }

    public function testDefaultEntitiesRequireAnOwnerClass(): void
    {
        $kernel = new BookmarkKernel(false, ['owner_class' => null]);
        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Configure owner_class');
            $kernel->boot();
        } finally {
            $kernel->shutdown();
        }
    }
}
