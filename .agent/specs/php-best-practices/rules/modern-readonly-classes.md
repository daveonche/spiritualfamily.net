# Readonly Classes

**Impact:** HIGH — Class-level immutability for DTOs and value objects

**Tags:** modern-features, readonly-classes, immutability, php82

Declare `readonly class` to make every declared property readonly
(PHP 8.2+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Mutable DTO - state can be changed anywhere after construction
class Money
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}
}

$price = new Money(1999, 'EUR');
$price->amount = -1; // No error - corrupted state

// Repetitive per-property readonly (8.1 style)
class Point
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
    ) {}
}
```

## Good Example

```php
<?php

declare(strict_types=1);

// Every property is readonly; mutation anywhere is a fatal error
readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public function add(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException('Currency mismatch');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }
}

readonly class Point
{
    public function __construct(
        public float $x = 0.0,
        public float $y = 0.0,
        public float $z = 0.0,
    ) {}
}
```

## Rules

- Use `readonly class` for DTOs, value objects, and events; it replaces
  per-property `readonly` repetition.
- All properties must be typed; untyped or static properties are a
  fatal error in a readonly class.
- A readonly class cannot use `#[AllowDynamicProperties]`, and child
  classes must be readonly too.
- Requires PHP 8.2+; check the project version before suggesting.

## Why

- **Whole-Object Immutability**: One keyword instead of N property modifiers
- **Safe Sharing**: Immutable objects can be passed without defensive copies
- **Value Semantics**: Natural fit for Money, Point, DateRange, events
- **With Promotion**: `readonly class` plus constructor promotion is the idiomatic DTO
