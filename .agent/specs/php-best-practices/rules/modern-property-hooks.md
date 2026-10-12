# Property Hooks

**Impact:** HIGH — Replaces trivial getter/setter boilerplate with inline logic

**Tags:** modern-features, property-hooks, encapsulation, php84

Use property hooks to attach `get`/`set` logic directly to properties
(PHP 8.4+).

## Bad Example

```php
<?php

declare(strict_types=1);

class User
{
    private string $email;

    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function __construct(
        private string $firstName,
        private string $lastName,
    ) {}

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
```

## Good Example

```php
<?php

declare(strict_types=1);

class User
{
    // Normalize on write; plain read
    public string $email {
        set => strtolower(trim($value));
    }

    // Virtual property - no backing storage, computed on read
    public string $fullName {
        get => "{$this->firstName} {$this->lastName}";
    }

    public function __construct(
        public private(set) string $firstName,
        public private(set) string $lastName,
        string $email,
    ) {
        $this->email = $email; // Runs the set hook
    }
}

$user = new User(firstName: 'Ada', lastName: 'Lovelace', email: '  ADA@EXAMPLE.COM ');
echo $user->email;    // 'ada@example.com'
echo $user->fullName; // 'Ada Lovelace'

// Validation in a full-block set hook
class PositiveAmount
{
    public int $cents {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('Amount cannot be negative');
            }
            $this->cents = $value; // Explicit storage assignment
        }
    }
}

// Hooks work in interfaces as abstract declarations
interface HasEmail
{
    public string $email { get; }
}
```

## Rules

- Prefer a `set` hook over a setter method when the only job is
  normalization or validation.
- Use virtual properties (`get` without a backing value) for computed
  values instead of `getXxx()` methods.
- In arrow-form hooks the result is assigned automatically; in
  full-block form, assign to the property explicitly.
- Requires PHP 8.4+; check the project version before suggesting.

## Why

- **No Boilerplate**: `$user->email` replaces `getEmail()`/`setEmail()`
- **Validation at the Boundary**: Invalid data cannot enter the object
- **Virtual Properties**: Computed values look like plain properties
- **Composable**: Works with promotion, asymmetric visibility, interfaces
