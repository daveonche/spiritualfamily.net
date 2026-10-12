# Intersection Types

**Impact:** MEDIUM — Enforces multiple interface implementations for composition

**Tags:** type-system, intersection-types, interfaces, php81

Use intersection types when a value must implement multiple interfaces
(PHP 8.1+).

## Bad Example

```php
<?php

declare(strict_types=1);

class CacheService
{
    /**
     * @param Cacheable&Encodable $item
     */
    public function store($item): void
    {
        // No type enforcement - relies on docblock
        // Could receive object implementing only one interface
        $key = $item->getCacheKey();
        $data = $item->encode();
        $this->cache->set($key, $data);
    }
}
```

## Good Example

```php
<?php

declare(strict_types=1);

interface Cacheable
{
    public function getCacheKey(): string;
    public function getCacheTtl(): int;
}

interface Encodable
{
    public function encode(): string;
    public function decode(string $data): void;
}

class CacheService
{
    // Object MUST implement both interfaces
    public function store(Cacheable&Encodable $item): void
    {
        $key = $item->getCacheKey();
        $data = $item->encode();
        $ttl = $item->getCacheTtl();

        $this->cache->set($key, $data, $ttl);
    }

    public function retrieve(
        string $key,
        Cacheable&Encodable $prototype
    ): Cacheable&Encodable {
        $data = $this->cache->get($key);
        $prototype->decode($data);
        return $prototype;
    }
}
```

## Why

- **Compound Requirements**: Enforces multiple interface implementations
- **Type Safety**: PHP enforces all required capabilities at class loading
- **Composition**: Enables type-safe composition over inheritance
- **Clear Contracts**: Explicitly states all required behaviors
- **Better Than Base Classes**: More flexible than requiring a specific base class
- **Static Analysis**: Full support in PHPStan and Psalm
