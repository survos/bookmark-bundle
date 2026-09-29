<?php

declare(strict_types=1);

namespace Survos\BookmarkBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Survos\BookmarkBundle\Enum\FolderVisibility;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Generic bookmark/folder business logic, parameterized by the host app's
 * concrete Bookmark/Folder class names (configured via survos_bookmark.bookmark_class
 * / folder_class). Only registered when both are configured — see
 * SurvosBookmarkBundle::loadExtension(). $user/$bookmark/$folder are typed
 * `object` throughout since this class never knows the host's concrete
 * User/Bookmark/Folder classes.
 *
 * $bookmarkClass's constructor must accept these named parameters: user,
 * provider, dataset, coreCode, localId, folder (nullable), dtoType (nullable),
 * label (nullable). $folderClass's constructor must accept: user, name, slug,
 * visibility. See README.md.
 */
class BookmarkManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
        private readonly string $bookmarkClass,
        private readonly string $folderClass,
    ) {
    }

    /** Save any resource using a typed reference; legacy scalar methods remain available. */
    public function save(object $user, \Survos\BookmarkBundle\Model\TargetReference $target, ?object $folder = null, ?string $label = null): object
    {
        return $this->add($user, $target->source, $target->collection, $target->class, $target->identifier, $folder, label: $label);
    }

    /** Create-if-absent receipt: preserve local annotations and never trigger outbound sharing. */
    public function receive(object $user, \Survos\BookmarkBundle\Model\BookmarkSnapshot $snapshot): object
    {
        if (!is_a($this->bookmarkClass, \Survos\BookmarkBundle\Entity\ShareableBookmarkBase::class, true)) {
            throw new \LogicException('Receiving requires a ShareableBookmarkBase entity.');
        }
        return $this->em->wrapInTransaction(function () use ($user, $snapshot): object {
            $this->em->lock($user, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            $target = $snapshot->target;
            if ($existing = $this->findBookmark($user, $target->source, $target->collection, $target->class, $target->identifier)) {
                return $existing;
            }
            $class = $this->bookmarkClass;
            $bookmark = new $class(user: $user, provider: $target->source, dataset: $target->collection,
                coreCode: $target->class, localId: $target->identifier, label: $snapshot->label);
            $bookmark->targetUrl = $snapshot->url;
            $bookmark->origin = $snapshot->origin;
            $bookmark->notes = $snapshot->notes;
            $bookmark->tags = $snapshot->tags;
            $this->em->persist($bookmark);
            return $bookmark;
        });
    }

    public function isBookmarked(object $user, string $provider, string $dataset, string $coreCode, string $localId): bool
    {
        return $this->findBookmark($user, $provider, $dataset, $coreCode, $localId) !== null;
    }

    /**
     * Idempotent: returns the existing bookmark for this row if present (re-filing into
     * $folder when given), otherwise creates and returns a new one.
     */
    public function add(
        object $user,
        string $provider,
        string $dataset,
        string $coreCode,
        string $localId,
        ?object $folder = null,
        ?string $dtoType = null,
        ?string $label = null,
    ): object {
        if ($folder !== null && $folder->user !== $user) {
            throw new \InvalidArgumentException('Bookmark folder must belong to its owner.');
        }
        $existing = $this->findBookmark($user, $provider, $dataset, $coreCode, $localId);

        if ($existing !== null) {
            $existing->folder = $folder;

            if ($dtoType !== null) {
                $existing->dtoType = $dtoType;
            }

            if ($label !== null) {
                $existing->label = $label;
            }

            $this->em->flush();

            return $existing;
        }

        $class = $this->bookmarkClass;
        $bookmark = new $class(
            user: $user,
            provider: $provider,
            dataset: $dataset,
            coreCode: $coreCode,
            localId: $localId,
            folder: $folder,
            dtoType: $dtoType,
            label: $label,
        );
        $this->em->persist($bookmark);
        $this->em->flush();

        return $bookmark;
    }

    public function remove(object $user, string $provider, string $dataset, string $coreCode, string $localId): void
    {
        $existing = $this->findBookmark($user, $provider, $dataset, $coreCode, $localId);
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    /**
     * Adds the bookmark if absent, removes it if present. Returns the new state.
     */
    public function toggle(
        object $user,
        string $provider,
        string $dataset,
        string $coreCode,
        string $localId,
        ?string $dtoType = null,
        ?string $label = null,
        ?object $folder = null,
    ): bool {
        if ($this->isBookmarked($user, $provider, $dataset, $coreCode, $localId)) {
            $this->remove($user, $provider, $dataset, $coreCode, $localId);

            return false;
        }

        $this->add($user, $provider, $dataset, $coreCode, $localId, $folder, $dtoType, $label);

        return true;
    }

    public function move(object $bookmark, ?object $folder): void
    {
        if ($folder !== null && $folder->user !== $bookmark->user) {
            throw new \InvalidArgumentException('Bookmark folder must belong to its owner.');
        }
        $bookmark->folder = $folder;
        $this->em->flush();
    }

    public function createFolder(object $user, string $name, FolderVisibility $visibility = FolderVisibility::Private): object
    {
        $slug = $this->uniqueSlug($user, $name);
        $class = $this->folderClass;
        $folder = new $class(user: $user, name: $name, slug: $slug, visibility: $visibility);
        $this->em->persist($folder);
        $this->em->flush();

        return $folder;
    }

    /**
     * Slug is intentionally left unchanged — it's assigned once at creation and stays
     * stable so links into a folder don't break when it's renamed.
     */
    public function renameFolder(object $folder, string $name): void
    {
        $folder->name = $name;
        $this->em->flush();
    }

    public function deleteFolder(object $folder): void
    {
        // Bookmarks in this folder fall back to Unfiled via onDelete: SET NULL.
        $this->em->remove($folder);
        $this->em->flush();
    }

    private function findBookmark(object $user, string $provider, string $dataset, string $coreCode, string $localId): ?object
    {
        return $this->em->getRepository($this->bookmarkClass)->findOneBy([
            'user' => $user,
            'provider' => $provider,
            'dataset' => $dataset,
            'coreCode' => $coreCode,
            'localId' => $localId,
        ]);
    }

    private function findFolderBySlug(object $user, string $slug): ?object
    {
        return $this->em->getRepository($this->folderClass)->findOneBy(['user' => $user, 'slug' => $slug]);
    }

    private function uniqueSlug(object $user, string $name, ?object $ignoring = null): string
    {
        $base = (string) $this->slugger->slug($name)->lower();
        $slug = $base;
        $suffix = 2;

        while (($found = $this->findFolderBySlug($user, $slug)) !== null && $found !== $ignoring) {
            $slug = sprintf('%s-%d', $base, $suffix++);
        }

        return $slug;
    }
}
