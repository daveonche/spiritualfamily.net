# Named Arguments

**Impact:** HIGH — Self-documenting calls, skip optional parameters safely

**Tags:** modern-features, named-arguments, readability, php8

Use named arguments to skip optional parameters and make call sites
self-documenting (PHP 8.0+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Positional args force placeholders for skipped defaults
htmlspecialchars($userInput, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

// What do these booleans mean?
function sendNotification(
    string $to,
    bool $urgent = false,
    bool $silent = false,
    bool $retry = true,
): void {
}

sendNotification('admin@example.com', false, true, false); // Meaning unclear

// Long positional constructor calls are fragile
new Request('GET', '/users', [], [], [], ['HTTPS' => 'on']);
```

## Good Example

```php
<?php

declare(strict_types=1);

// Skip defaults by naming only what changes
htmlspecialchars($userInput, double_encode: false);

// Self-documenting flags
sendNotification(
    to: 'admin@example.com',
    silent: true,
    retry: false,
);

// Named args with constructor promotion
class Invoice
{
    public function __construct(
        public string $number,
        public Money $total,
        public ?DateTimeImmutable $dueAt = null,
        public bool $isDraft = false,
    ) {}
}

$invoice = new Invoice(
    number: 'INV-2026-001',
    total: new Money(amount: 9900, currency: 'EUR'),
);

// Named arguments in attributes
#[Route(path: '/users/{id}', methods: ['GET'])]
public function show(int $id): Response {}
```

## Rules

- Use named arguments when a call has more than one optional parameter
  or any boolean flag.
- Positional arguments must come before named ones; never skip a
  positional parameter with a placeholder just to reach a later one.
- Named arguments are evaluated in call order, not declaration order —
  avoid side effects that depend on evaluation order.

## Why

- **Self-Documenting**: `retry: false` reads clearly; a bare `false` does not
- **Skip Defaults**: No placeholder values for untouched optional parameters
- **Refactoring Safety**: Adding an optional parameter does not break named call sites
- **Boolean Clarity**: Eliminates the "sea of booleans" problem
- **Attribute Support**: Named arguments work in attribute declarations
