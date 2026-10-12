# Avoid Mixed Type

**Impact:** HIGH — Maintains type safety, use union types or generics instead

**Tags:** type-system, mixed, union-types, generics, php8

Avoid using mixed type; prefer specific types or union types instead.

## Bad Example

```php
<?php

declare(strict_types=1);

class DataProcessor
{
    // mixed accepts anything - no type safety
    public function process(mixed $data): mixed
    {
        // What can we do with $data? Anything!
        // What does this return? Who knows!
        return $data;
    }
}

class Cache
{
    // Using mixed as a crutch
    public function get(string $key): mixed
    {
        return $this->storage[$key] ?? null;
    }
}
```

## Good Example

```php
<?php

declare(strict_types=1);

class DataProcessor
{
    // Specific union type instead of mixed
    public function process(array|object $data): array
    {
        if (is_object($data)) {
            return get_object_vars($data);
        }
        return $data;
    }
}

// Type-safe cache using generics (PHPStan/Psalm templates)
/**
 * @template T
 */
class TypedCache
{
    /** @var array<string, T> */
    private array $storage = [];

    /**
     * @param T $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->storage[$key] = $value;
    }

    /**
     * @return T|null
     */
    public function get(string $key): mixed
    {
        return $this->storage[$key] ?? null;
    }
}

// When mixed is truly needed, document why
class JsonEncoder
{
    /**
     * Encodes any JSON-serializable value.
     * Mixed is appropriate here as JSON can encode any scalar,
     * array, or object.
     *
     * @param scalar|array|object|null $value
     */
    public function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
```

## Why

- **Type Safety Lost**: mixed disables all type checking
- **Hidden Bugs**: Type errors only appear at runtime
- **Poor Documentation**: Callers don't know what to pass
- **IDE Limitations**: No autocompletion or type hints
- **Maintenance Burden**: Future developers must guess types
- **Better Alternatives**: Union types, generics, or specific types are clearer
