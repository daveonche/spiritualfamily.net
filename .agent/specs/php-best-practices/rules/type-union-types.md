# Union Types

**Impact:** HIGH — Provides type precision without using mixed

**Tags:** type-system, union-types, type-safety, php8

Use union types when a value can legitimately be one of multiple types
(PHP 8.0+).

## Bad Example

```php
<?php

declare(strict_types=1);

class ConfigLoader
{
    /**
     * @param string|array $source
     * @return mixed
     */
    public function load($source)
    {
        // No type enforcement, relies on docblock
        if (is_string($source)) {
            return $this->loadFromFile($source);
        }
        return $this->loadFromArray($source);
    }

    // Using mixed when specific types are known
    public function getValue(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
}
```

## Good Example

```php
<?php

declare(strict_types=1);

class ConfigLoader
{
    public function load(string|array $source): array
    {
        if (is_string($source)) {
            return $this->loadFromFile($source);
        }
        return $this->loadFromArray($source);
    }

    public function getValue(string $key): string|int|bool|null
    {
        return $this->config[$key] ?? null;
    }
}

class Response
{
    public function setContent(string|Stringable $content): self
    {
        $this->content = (string) $content;
        return $this;
    }
}
```

## Why

- **Type Precision**: Explicitly declares all acceptable types
- **Better Than Mixed**: More specific than mixed, enabling better analysis
- **Flexibility**: Allows legitimate polymorphic behavior with type safety
- **PHP 8 Feature**: Native language support replaces docblock annotations
- **IDE Support**: Full autocompletion and error detection
- **Self-Documenting**: Union types clearly communicate accepted values
