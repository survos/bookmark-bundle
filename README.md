# Bookmark bundle

Local user-owned bookmarks for arbitrary resources, with optional dispatch to peers.
No Folio, ActivityPub, HTTP client or Messenger dependency is needed for local CRUD.

## Install and configure

Require `survos/bookmark-bundle` and register `Survos\BookmarkBundle\SurvosBookmarkBundle`.
The new package is currently developed through the monorepo Composer path repository;
publish/split it before replacing that development dependency with a tagged release.

```yaml
survos_bookmark:
    bookmark_class: App\Entity\Bookmark
    folder_class: App\Entity\Folder
```

The application owns its concrete Doctrine entities and User associations. Extend
`Entity\BookmarkBase` and `Entity\FolderBase`; use `Entity\ShareableBookmarkBase`
when you need URL targets, notes, tags and origin metadata. Do not map these base
classes into a separate entity manager. They inherit into the host's own mapping.

Retain the unique constraint on `(user, provider, dataset, coreCode, localId)`.
Concrete Bookmark constructors accept named parameters `user, provider, dataset,
coreCode, localId, folder=null, dtoType=null, label=null`. Folder constructors accept
`user, name, slug, visibility`. Initialize `id` (Ulid) and `created` in both.
Repository bases and `FolderVisibility` are included. All folder assignments must
belong to the bookmark owner; the manager enforces this on add and move.

## Resource references

```php
$target = new TargetReference(
    source: 'pressia.example', // stable provider/site namespace; use the same value on every peer
    class: 'Article',        // vocabulary term, never dynamically instantiated
    identifier: '42',
    collection: 'external', // default; use a stable dataset name when applicable
);
$bookmark = $manager->save($user, $target, label: 'An article');
```

Existing physical column names remain unchanged for compatibility:
source→provider, collection→dataset, class→coreCode, identifier→localId.
The core does not fetch the resource. Site-specific adapters resolve Folio routes,
article URLs, display metadata and permitted excerpts. A saved resource and a user's
bookmark are separate identities. Different users can save the same target.

## Receive a snapshot

```php
$snapshot = new BookmarkSnapshot($target, 'https://pressia.example/articles/42',
    label: 'An article', notes: 'Why I saved it', tags: ['history'],
    origin: 'https://pressia.example/bookmarks/123');
$bookmark = $manager->receive($authenticatedUser, $snapshot);
```

`receive()` requires a concrete `ShareableBookmarkBase` and a persisted, managed
owner. It uses a transaction and owner lock to serialize concurrent receipts.
It is create-if-absent by owner and target identity: retries preserve existing local
notes, tags, folder and label. Multiple incoming annotations for one target do not
silently merge. It never republishes a receipt. Authentication and consent belong
to the host endpoint, which chooses the owner; a remote payload cannot choose one.

`origin` identifies the originating bookmark; `url` identifies its target. Preserve
origin when deliberately forwarding. Neither value proves authorship. The receiving
site controls its local copy. Remote updates, withdrawals and conflict resolution
are not implemented by this initial receipt operation.

## Optional sharing dispatch

Local saves are always local. Enable a dispatch service only where needed:

```yaml
survos_bookmark:
    bookmark_class: App\Entity\Bookmark
    folder_class: App\Entity\Folder
    sharing:
        enabled: true
        destinations: [recordia, another_archive]
framework:
    messenger:
        routing:
            Survos\BookmarkBundle\Message\ShareBookmark: async
```

Require Symfony Messenger when enabling sharing. After the user authorizes a share,
call `BookmarkSharing::share('recordia', $snapshot)`. It dispatches an immutable
`ShareBookmark` message. The message contains a configured destination name and
snapshot, never an ORM entity or credential. Unknown destinations are rejected.

**The host must supply the delivery handler/transport**, endpoint credentials and
retry/failure policy. This package provides dispatch, not automatic HTTP delivery.
No handler or bus is required in local-only installations. Receiving never invokes
sharing, preventing automatic forwarding loops. ZM's authenticated receiving endpoint
is the first host adapter; any other site can implement the same receipt contract.
Public ActivityPub publication is a separate adapter and consent decision.

## Existing Folio applications

Folio requires this package and retains compatibility names for entities, repositories,
the visibility enum and manager. Its BookmarkBase adds only `folioCode` and
`rowRouteParams`; existing database columns and constructor calls remain unchanged.
Legacy `survos_folio.bookmark_class/folder_class` configuration still registers the
canonical manager and a compatible legacy service. No schema migration is needed just for
this extraction. Opting into `ShareableBookmarkBase` adds `target_url`, `origin`,
`notes` (if not already present), and `tags`; generate a host migration.

## Postmarks influence

Postmarks demonstrates publishing a bookmark as a distinct social object and reviewing
received messages before saving them locally. Its federation lifecycle includes create,
update and delete: https://github.com/ckolderup/postmarks/blob/main/FEDERATION.md

We adopt portable annotations and a distinct origin reference. We keep saving separate
from sharing and public publication, and leave update/delete synchronization explicit.
Postmarks' single-actor model and outbox-generated Note IDs do not fit multi-user,
repeatable peer receipts. This implementation contains no copied Postmarks code.
