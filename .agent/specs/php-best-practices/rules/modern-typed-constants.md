# Typed Class Constants

**Impact:** MEDIUM — Type errors in constants surface at compile time

**Tags:** modern-features, typed-constants, class-constants, php83

Declare types on class, interface, and enum constants (PHP 8.3+).

## Bad Example

```php
<?php

declare(strict_types=1);

class Config
{
    // Untyped - a wrong value type surfaces far from the declaration
    public const MAX_RETRIES = 3;
    public const API_HOST = 'api.example.com';
    public const TIMEOUT = 30.0;

    // Accidental type change: someone edits 3 to '3'
    public const MIN_PHP = '8.1'; // Now a string; comparisons silently change
}
```

## Good Example

```php
<?php

declare(strict_types=1);

class Config
{
    public const int MAX_RETRIES = 3;
    public const string API_HOST = 'api.example.com';
    public const float TIMEOUT = 30.0;
    public const string MIN_PHP = '8.1';

    // Union types are allowed
    public const string|int|null CACHE_TTL = 3600;
}

interface Roles
{
    public const string ADMIN = 'admin';
    public const string EDITOR = 'editor';
}

enum Status: string
{
    case Active = 'active';
    case Archived = 'archived';

    public const string LABEL_PREFIX = 'status.';
}
```

## Rules

- Type every class constant; the check runs at compile time.
- Allowed types mirror typed-property rules (scalars, classes, unions);
  a subclass may redeclare a parent constant only with a compatible
  type.
- Requires PHP 8.3+; check the project version before suggesting.

## Why

- **Compile-Time Safety**: Wrong value types fail immediately
- **Self-Documenting**: The type is part of the contract
- **Union Support**: `string|int|null` documents flexible constants
- **Consistency**: Matches the typed-properties mental model
