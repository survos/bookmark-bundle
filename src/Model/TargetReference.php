<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Model;

/** Source is a stable site/provider namespace; class is a vocabulary term, never a PHP class to instantiate. */
final readonly class TargetReference
{
    public function __construct(
        public string $source,
        public string $class,
        public string $identifier,
        public string $collection = 'external',
    ) {
        foreach (['source' => 64, 'class' => 64, 'identifier' => 180, 'collection' => 128] as $field => $max) {
            if (trim($this->$field) === '' || mb_strlen($this->$field) > $max) {
                throw new \InvalidArgumentException("Invalid bookmark target $field.");
            }
        }
    }
}
