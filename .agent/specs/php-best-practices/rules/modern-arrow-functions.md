# Arrow Functions

**Impact:** MEDIUM — Concise closures with automatic variable capture

**Tags:** modern-features, arrow-functions, closures, php74

Use arrow functions for short, single-expression closures (PHP 7.4+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Verbose closures for simple operations
$numbers = [1, 2, 3, 4, 5];

$doubled = array_map(function ($n) {
    return $n * 2;
}, $numbers);

// Must explicitly use `use` to capture outer scope
$multiplier = 3;
$multiplied = array_map(function ($n) use ($multiplier) {
    return $n * $multiplier;
}, $numbers);

// Nested verbose closures
$users = [/* ... */];
$activeEmails = array_map(function ($user) {
    return $user->getEmail();
}, array_filter($users, function ($user) {
    return $user->isActive();
}));
```

## Good Example

```php
<?php

declare(strict_types=1);

$numbers = [1, 2, 3, 4, 5];

// Concise arrow functions
$doubled = array_map(fn($n) => $n * 2, $numbers);
$evens = array_filter($numbers, fn($n) => $n % 2 === 0);

// Automatic capture of outer scope - no `use` needed
$multiplier = 3;
$multiplied = array_map(fn($n) => $n * $multiplier, $numbers);

// Chained operations are more readable
$users = [/* ... */];
$activeEmails = array_map(
    fn($user) => $user->getEmail(),
    array_filter($users, fn($user) => $user->isActive())
);

// With type hints
$prices = [10.5, 20.0, 15.75];
$withTax = array_map(
    fn(float $price): float => $price * 1.1,
    $prices
);

// Sorting with arrow functions
$products = [/* ... */];
usort($products, fn($a, $b) => $a->getPrice() <=> $b->getPrice());

// Callbacks and event handlers
$button->onClick(fn() => $controller->handleClick());
$form->onSubmit(fn($data) => $controller->processForm($data));

// Higher-order functions
function createMultiplier(int $factor): Closure
{
    return fn(int $n): int => $n * $factor;
}

$double = createMultiplier(2);

echo $double(5); // 10
```

## Why

- **Concise**: Single expression without braces or return keyword
- **Auto-Capture**: Variables from outer scope captured automatically
- **Readable**: Better for functional programming patterns
- **Type Support**: Full support for parameter and return types
- **Immutable Capture**: Captured variables are by-value (safe)
- **Perfect For**: Callbacks, array functions, short lambdas
