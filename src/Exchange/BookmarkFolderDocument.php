<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Exchange;

use Survos\BookmarkBundle\Enum\FolderVisibility;
use Survos\BookmarkBundle\Model\BookmarkSnapshot;
use Survos\BookmarkBundle\Model\TargetReference;

/** A versioned, owner-free import envelope. JSON and YAML use exactly the same shape. */
final readonly class BookmarkFolderDocument
{
    public const string FORMAT = 'survos/bookmark-folder';
    public const int VERSION = 1;

    /** @param list<BookmarkSnapshot> $bookmarks List order is folder order. */
    public function __construct(
        public string $name,
        public string $slug,
        public FolderVisibility $visibility,
        public array $bookmarks,
    ) {
        if ('' === trim($name) || mb_strlen($name) > 120 || strlen($slug) > 140
            || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
            throw new \InvalidArgumentException('Invalid folder name or slug.');
        }
        if (!array_is_list($bookmarks) || count($bookmarks) > 10000) {
            throw new \InvalidArgumentException('Expected an ordered list of at most 10000 bookmarks.');
        }
        $seen = [];
        foreach ($bookmarks as $bookmark) {
            if (!$bookmark instanceof BookmarkSnapshot) {
                throw new \InvalidArgumentException('Expected BookmarkSnapshot entries.');
            }
            $key = json_encode($bookmark->target, JSON_THROW_ON_ERROR);
            if (isset($seen[$key])) {
                throw new \InvalidArgumentException('Duplicate bookmark target in document.');
            }
            $seen[$key] = true;
        }
    }

    public static function fromArray(array $data): self
    {
        self::keys($data, ['format', 'version', 'folder', 'bookmarks']);
        if (self::FORMAT !== self::required($data, 'format') || self::VERSION !== self::required($data, 'version')) {
            throw new \InvalidArgumentException('Unsupported bookmark folder format/version.');
        }
        $folder = self::required($data, 'folder');
        $entries = self::required($data, 'bookmarks');
        if (!is_array($folder) || !is_array($entries) || !array_is_list($entries)) {
            throw new \InvalidArgumentException('Expected folder mapping and bookmarks list.');
        }
        self::keys($folder, ['name', 'slug', 'visibility']);
        $visibility = FolderVisibility::tryFrom(self::string($folder, 'visibility'))
            ?? throw new \InvalidArgumentException('Invalid folder visibility.');
        $bookmarks = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                throw new \InvalidArgumentException('Expected a bookmark mapping.');
            }
            self::keys($entry, ['target', 'url', 'label', 'notes', 'tags', 'origin', 'mediaCode']);
            $target = self::required($entry, 'target');
            if (!is_array($target)) {
                throw new \InvalidArgumentException('Expected target mapping.');
            }
            self::keys($target, ['source', 'collection', 'class', 'identifier']);
            $tags = array_key_exists('tags', $entry) ? $entry['tags'] : [];
            if (!is_array($tags)) {
                throw new \InvalidArgumentException('Expected tags list.');
            }
            $bookmarks[] = new BookmarkSnapshot(
                new TargetReference(self::string($target, 'source'), self::string($target, 'class'),
                    self::string($target, 'identifier'), self::string($target, 'collection')),
                self::string($entry, 'url'), self::optionalString($entry, 'label'),
                self::optionalString($entry, 'notes'), $tags, self::optionalString($entry, 'origin'), self::optionalString($entry, 'mediaCode'),
            );
        }

        return new self(self::string($folder, 'name'), self::string($folder, 'slug'), $visibility, $bookmarks);
    }

    public function toArray(): array
    {
        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'folder' => ['name' => $this->name, 'slug' => $this->slug, 'visibility' => $this->visibility->value],
            'bookmarks' => array_map(static fn (BookmarkSnapshot $bookmark): array => [
                'target' => ['source' => $bookmark->target->source, 'collection' => $bookmark->target->collection,
                    'class' => $bookmark->target->class, 'identifier' => $bookmark->target->identifier],
                'url' => $bookmark->url, 'label' => $bookmark->label, 'notes' => $bookmark->notes,
                'tags' => $bookmark->tags, 'origin' => $bookmark->origin, 'mediaCode' => $bookmark->mediaCode,
            ], $this->bookmarks),
        ];
    }

    private static function required(array $data, string $key): mixed
    {
        if (!array_key_exists($key, $data)) {
            throw new \InvalidArgumentException("Missing required field: $key.");
        }

        return $data[$key];
    }

    private static function string(array $data, string $key): string
    {
        $value = self::required($data, $key);
        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException("Expected nonempty string: $key.");
        }

        return $value;
    }

    private static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        if (null !== $value && !is_string($value)) {
            throw new \InvalidArgumentException("Expected string or null: $key.");
        }

        return $value;
    }

    private static function keys(array $data, array $allowed): void
    {
        if ($unknown = array_diff(array_keys($data), $allowed)) {
            throw new \InvalidArgumentException('Unknown fields: '.implode(', ', $unknown));
        }
    }
}
