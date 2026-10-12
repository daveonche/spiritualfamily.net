# Pipe Operator

**Impact:** MEDIUM — Data flow reads left-to-right instead of inside-out

**Tags:** modern-features, pipe-operator, functional, php85

Use the pipe operator `|>` to pass the left value as the first argument
of the callable on the right (PHP 8.5+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Nested calls read inside-out
$title = htmlspecialchars(strtolower(trim($userInput)), ENT_QUOTES);

// Or a pile of temporary variables
$trimmed = trim($userInput);
$lower = strtolower($trimmed);
$escaped = htmlspecialchars($lower, ENT_QUOTES);
$title = $escaped;
```

## Good Example

```php
<?php

declare(strict_types=1);

// First-class callable syntax (8.1+) makes pipes clean
$title = $userInput
    |> trim(...)
    |> strtolower(...)
    |> (fn($s) => htmlspecialchars($s, ENT_QUOTES));

// Partial application supplies the extra arguments; the pipe fills the last
$slug = '  Hello World  '
    |> trim(...)
    |> strtolower(...)
    |> str_replace(' ', '-', ...); // 'hello-world'

// Any callable works: functions, closures, method references
$price = $order
    |> $this->subtotal(...)
    |> $this->applyDiscount(...)
    |> money_formatter(...);
```

## Rules

- The right side must be a callable; use first-class callable syntax
  or a closure when the call needs extra arguments.
- Pipes are expressions: each stage's return value feeds the next stage.
- Requires PHP 8.5+; check the project version before suggesting.

## Why

- **Top-Down Reading**: Data flow reads in execution order
- **No Temp Variables**: Eliminates intermediate assignment noise
- **Composable**: Stages are ordinary callables, easy to reorder
- **Pairs with 8.1+ Callables**: First-class callable syntax completes the pattern
