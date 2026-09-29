<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Model;

/** Immutable selection shared between sites; no ORM entities, owner IDs or credentials travel in it. */
final readonly class BookmarkSnapshot
{
    public function __construct(
        public TargetReference $target,
        public string $url,
        public ?string $label = null,
        public ?string $notes = null,
        public array $tags = [],
        public ?string $origin = null,
    ) {
        foreach (array_filter([$url, $origin], static fn ($value) => $value !== null) as $uri) {
            if (strlen($uri) > 2048 || !filter_var($uri, FILTER_VALIDATE_URL)
                || !in_array(parse_url($uri, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new \InvalidArgumentException('Bookmark URLs must be absolute HTTP(S) URLs.');
            }
        }
        if (($label !== null && mb_strlen($label) > 500) || ($notes !== null && mb_strlen($notes) > 20000)) {
            throw new \InvalidArgumentException('Bookmark annotations are too long.');
        }
        if (!array_is_list($tags) || count($tags) > 50) throw new \InvalidArgumentException('Expected at most 50 tags.');
        foreach ($tags as $tag) {
            if (!is_string($tag) || trim($tag) === '' || mb_strlen($tag) > 100) {
                throw new \InvalidArgumentException('Invalid bookmark tag.');
            }
        }
    }
}
