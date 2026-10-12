# Asymmetric Visibility

**Impact:** HIGH — Enables immutable-like public API without readonly limitations

**Tags:** modern-features, asymmetric-visibility, access-control, encapsulation, php84

Use asymmetric visibility to allow public reading but restrict writing
(PHP 8.4+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Using readonly - cannot modify even internally
readonly class Order
{
    public function __construct(
        public string $id,
        public string $status, // Cannot update status after creation!
        public float $total,
    ) {}
}

// Using private with getters - verbose
class VerboseOrder
{
    public function __construct(
        private string $id,
        private string $status,
        private float $total,
    ) {}

    public function getStatus(): string
    {
        return $this->status;
    }

    public function markPaid(): void
    {
        $this->status = 'paid';
    }
}

echo $order->getStatus(); // Verbose
```

## Good Example

```php
<?php

declare(strict_types=1);

class Order
{
    public function __construct(
        // Publicly readable, only settable inside the class
        public private(set) string $id,
        public private(set) string $status,
        public private(set) float $total,
    ) {}

    public function markPaid(): void
    {
        $this->status = 'paid'; // OK - internal set
    }
}

$order = new Order('ORD-001', 'pending', 99.99);
echo $order->status;      // OK - public read: "pending"
// $order->status = 'paid'; // Error! Cannot set from outside

// Also works with protected(set)
class BaseModel
{
    public protected(set) string $table;
}

class User extends BaseModel
{
    public function __construct()
    {
        $this->table = 'users'; // OK - child class can set
    }
}

// $user->table = 'foo'; // Error! Cannot set from outside
```

## Why

- **Clean Public API**: `$order->status` instead of `$order->getStatus()`
- **Internal Mutability**: Unlike readonly, the class can still update its own properties
- **No Getter Boilerplate**: Eliminates trivial getter methods
- **Granular Control**: Choose `private(set)` or `protected(set)` for write access
- **Pairs with Hooks**: Combine with property hooks for validated writes
