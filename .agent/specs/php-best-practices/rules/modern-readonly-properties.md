# Readonly Properties

**Impact:** CRITICAL — Write-once properties prevent accidental state changes

**Tags:** modern-features, readonly, immutability, php81

Use readonly properties for values that must not change after
initialization (PHP 8.1+).

## Bad Example

```php
<?php

declare(strict_types=1);

class User
{
    // Mutable identity - can be reassigned anywhere
    public string $id;
    public string $email;

    public function __construct(string $id, string $email)
    {
        $this->id = $id;
        $this->email = $email;
    }
}

$user = new User('u-123', 'ada@example.com');
$user->id = 'u-999'; // Silently corrupts identity
```

## Good Example

```php
<?php

declare(strict_types=1);

class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
    ) {}
}

$user = new User('u-123', 'ada@example.com');
// $user->id = 'u-999'; // Error: cannot modify readonly property

// One initialization from the declaring scope is allowed - lazy init
class LazyConnection
{
    public function __construct(
        private string $dsn,
    ) {}

    private readonly \PDO $pdo;

    public function pdo(): \PDO
    {
        return $this->pdo ??= new \PDO($this->dsn); // First write OK
    }
}
```

## Rules

- Mark identity and value fields (`$id`, `$email`, `$createdAt`)
  readonly; keep working state private and mutable.
- A readonly property may be initialized exactly once, and only from
  the declaring class's scope; later writes, even internal ones, error.
- `unset()` on an initialized readonly property is an error.
- Requires PHP 8.1+; for whole-value objects prefer `readonly class`
  (8.2+, see `modern-readonly-classes`).

## Why

- **State Integrity**: Identity fields cannot drift after construction
- **Intent**: readonly documents "this never changes" in the type system
- **Safe Sharing**: No defensive copies needed across boundaries
- **Static Analysis**: Analyzers flag illegal reassignments
