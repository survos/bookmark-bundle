<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Survos\BookmarkBundle\Exchange\BookmarkFolderDocument;

final class BookmarkFolderDocumentTest extends TestCase
{
    private static function document(): array
    {
        return ['format' => BookmarkFolderDocument::FORMAT, 'version' => 1,
            'folder' => ['name' => 'Research', 'slug' => 'research', 'visibility' => 'public'],
            'bookmarks' => [['target' => ['source' => 'nara', 'collection' => 'catalog', 'class' => 'photograph', 'identifier' => '196468'],
                'url' => 'https://catalog.archives.gov/id/196468', 'label' => 'Appeal', 'notes' => "Target, not total.\nSource evidence.", 'tags' => ['appeal'], 'origin' => null, 'mediaCode' => '0123456789abcdef']]];
    }

    public function testRoundTripPreservesEverySnapshotFieldAndOrder(): void
    {
        $data = self::document();
        $second = $data['bookmarks'][0];
        $second['target']['identifier'] = '535804';
        $second['url'] = 'https://catalog.archives.gov/id/535804';
        $data['bookmarks'][] = $second;
        self::assertSame($data, BookmarkFolderDocument::fromArray($data)->toArray());
    }

    #[DataProvider('invalidDocuments')]
    public function testMalformedOrUnsafeInputFailsBeforeImport(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BookmarkFolderDocument::fromArray($data);
    }

    public static function invalidDocuments(): iterable
    {
        $data = self::document();
        $data['version'] = 2;
        yield 'version' => [$data];
        $data = self::document();
        unset($data['bookmarks'][0]['target']['collection']);
        yield 'missing identity' => [$data];
        $data = self::document();
        $data['bookmarks'][0]['url'] = 'javascript:alert(1)';
        yield 'unsafe URL' => [$data];
        $data = self::document();
        $data['owner'] = 'someone';
        yield 'owner injection' => [$data];
        $data = self::document();
        $data['bookmarks'][] = $data['bookmarks'][0];
        yield 'duplicate' => [$data];
        $data = self::document();
        $data['folder']['visibility'] = 'publci';
        yield 'visibility typo' => [$data];
        $data = self::document();
        $data['bookmarks'][0]['tags'] = [42];
        yield 'tag type' => [$data];
        $data = self::document();
        $data['bookmarks'][0]['notes'] = ['bad'];
        yield 'notes type' => [$data];
        $data = self::document();
        $data['bookmarks'][0]['target']['identifier'] = 196468;
        yield 'numeric ID' => [$data];
    }
}
