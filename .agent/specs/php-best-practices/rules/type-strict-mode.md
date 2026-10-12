# Strict Types Declaration

**Impact:** CRITICAL — Prevents type coercion bugs, enforces type safety

**Tags:** type-system, strict-types, type-safety, php8

`declare(strict_types=1)` enforces strict type checking for function
arguments and return values. Without it, PHP silently coerces types,
hiding bugs. Strict mode catches type errors early, improving code
reliability.

## Bad Example

```php
<?php

// No strict types - silent coercion
function calculateTotal(int $price, int $quantity): int
{
    return $price * $quantity;
}

calculateTotal("10", "5"); // Returns 50 - numeric strings coerced to int
calculateTotal(10.99, 2);  // Returns 20 - float truncated (deprecated in 8.1)
```

## Good Example

```php
<?php

declare(strict_types=1);

function calculateTotal(int $price, int $quantity): int
{
    return $price * $quantity;
}

calculateTotal(10, 5);     // Returns 50
calculateTotal("10", "5"); // TypeError
calculateTotal(10.99, 2);  // TypeError
```

## Declaration Rules

- `declare(strict_types=1);` MUST be the first statement in the file,
  before the `namespace` declaration.
- Strict mode is per file and applies to the function calls made in
  that file (including internal PHP function calls), not to where the
  called function was defined; a non-strict calling file still coerces.
- Return types are strictly enforced too: returning `"42"` from an
  `int` return type is a TypeError.

```php
<?php

declare(strict_types=1);

namespace App\Services;

class UserService
{
    // ...
}
```

## Why

- **Type Safety**: Catches type bugs at runtime immediately
- **No Surprises**: No silent type coercion
- **Static Analysis**: Works with PHPStan/Psalm for maximum safety
- **Self-Documenting**: Code intent is explicit and enforced
- **Industry Standard**: Required for reliable modern PHP
