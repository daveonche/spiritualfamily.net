# Nullsafe Operator

**Impact:** HIGH — Eliminates nested null checks in object chains

**Tags:** modern-features, nullsafe-operator, null-safety, php8

Use the nullsafe operator `?->` to short-circuit a property or method
chain when an earlier link is null (PHP 8.0+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Nested null checks for every link in the chain
function getShippingCountry(?Order $order): ?string
{
    if ($order !== null) {
        $user = $order->getUser();
        if ($user !== null) {
            $address = $user->getAddress();
            if ($address !== null) {
                return $address->getCountry();
            }
        }
    }
    return null;
}

// Verbose && chains that repeat the whole path
if (
    $order !== null
    && $order->getUser() !== null
    && $order->getUser()->getAddress() !== null
) {
    $country = $order->getUser()->getAddress()->getCountry();
}
```

## Good Example

```php
<?php

declare(strict_types=1);

// The chain short-circuits to null on the first missing link
function getShippingCountry(?Order $order): ?string
{
    return $order?->getUser()?->getAddress()?->getCountry();
}

// Combine with null coalescing for a default
$label = $invoice?->customer?->name ?? 'Unknown customer';

// Safe fire-and-forget calls
$logger?->info('Order shipped', ['id' => $orderId]);
```

## Rules

- `?->` is read-only: it cannot be used on the left side of an
  assignment (`$user?->name = 'Alice'` is an error).
- Once a link evaluates to null, the rest of the chain is not executed
  — keep chains free of side effects that must always run.

## Why

- **Flat Chains**: One line replaces nested conditionals
- **Short-Circuit**: Later links are skipped once null appears
- **Composable**: Pairs with `??` for defaults in the same expression
- **Readable Intent**: The chain shape mirrors the object graph
