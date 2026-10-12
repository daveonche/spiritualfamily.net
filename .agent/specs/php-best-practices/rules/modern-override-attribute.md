# Override Attribute

**Impact:** MEDIUM — Catches broken overrides at load time, not in production

**Tags:** modern-features, override-attribute, inheritance, php83

Mark methods intended to override a parent with `#[\Override]` so
renames and typos fail loudly (PHP 8.3+).

## Bad Example

```php
<?php

declare(strict_types=1);

class FileStorage
{
    public function save(string $path, string $contents): void
    {
        // ...
    }
}

class S3Storage extends FileStorage
{
    // Typo: "sve" instead of "save" - silently becomes a NEW method
    public function sve(string $path, string $contents): void
    {
        // Never called; the parent implementation runs instead
    }
}

// Parent rename breaks overrides silently:
// FileStorage::save() renamed to store() leaves S3Storage::save() orphaned
```

## Good Example

```php
<?php

declare(strict_types=1);

class FileStorage
{
    public function save(string $path, string $contents): void
    {
        // ...
    }
}

class S3Storage extends FileStorage
{
    #[\Override]
    public function save(string $path, string $contents): void
    {
        // Guaranteed to override FileStorage::save()
    }
}

// A typo now fails when the class is loaded:
// #[\Override] public function sve(...) - fatal: no parent to override

// Works for interface implementations too
interface Cacheable
{
    public function cacheKey(): string;
}

class User implements Cacheable
{
    #[\Override]
    public function cacheKey(): string
    {
        return 'user:' . $this->id;
    }
}
```

## Rules

- Add `#[\Override]` to every method that intentionally overrides a
  parent or implements an interface method.
- The check runs when the class is loaded: a mismatch is a fatal
  error, not a silent new method.
- Combine with `final` on leaf implementations to lock the contract.

## Why

- **Fail Fast**: Renamed or typo'd overrides error at load time
- **Intent Documentation**: Marks which methods are part of the contract
- **Refactoring Safety**: Parent renames surface every affected child
- **Zero Runtime Cost**: Checked once at class load
