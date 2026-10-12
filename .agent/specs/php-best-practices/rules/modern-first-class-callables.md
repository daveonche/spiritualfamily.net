# First-Class Callable Syntax

**Impact:** MEDIUM — Concise callable references with IDE support

**Tags:** modern-features, first-class-callables, closures, php81

Use first-class callable syntax to create closures from callables
(PHP 8.1+).

## Bad Example

```php
<?php

declare(strict_types=1);

class StringProcessor
{
    public function toUpperCase(string $str): string
    {
        return strtoupper($str);
    }
}

// Old way - verbose Closure::fromCallable
$processor = new StringProcessor();
$upper = Closure::fromCallable([$processor, 'toUpperCase']);

// String-based callable - no IDE support, error-prone
$callback = [$processor, 'toUpperCase'];
array_map($callback, $strings); // Works but fragile
```

## Good Example

```php
<?php

declare(strict_types=1);

class StringProcessor
{
    public function toUpperCase(string $str): string
    {
        return strtoupper($str);
    }

    public function getProcessors(): array
    {
        // First-class callable syntax - concise and type-safe
        return [
            'upper' => $this->toUpperCase(...),
        ];
    }
}

// Instance methods
$processor = new StringProcessor();
$upper = $processor->toUpperCase(...);

$strings = ['hello', 'world'];
$uppercased = array_map($upper, $strings); // ['HELLO', 'WORLD']

// Static methods
class DateFormatter
{
    public static function format(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}

$formatDate = DateFormatter::format(...);

// Built-in functions
$trim = trim(...);
$strlen = strlen(...);

$cleaned = array_map($trim, $dirtyStrings);

// Practical use case: subscribe methods as first-class callables
class EventDispatcher
{
    /** @var array<string, Closure[]> */
    private array $listeners = [];

    public function subscribe(string $event, Closure $listener): void
    {
        $this->listeners[$event][] = $listener;
    }
}

class UserController
{
    public function __construct(
        private EventDispatcher $dispatcher,
    ) {
        $this->dispatcher->subscribe('user.created', $this->onUserCreated(...));
    }

    private function onUserCreated(User $user): void
    {
        // Handle event
    }
}

// Pipeline pattern
class Pipeline
{
    /** @var Closure[] */
    private array $stages = [];

    public function pipe(Closure $stage): self
    {
        $this->stages[] = $stage;
        return $this;
    }

    public function process(mixed $payload): mixed
    {
        return array_reduce(
            $this->stages,
            fn($carry, $stage) => $stage($carry),
            $payload
        );
    }
}

$pipeline = new Pipeline();
$pipeline
    ->pipe(trim(...))
    ->pipe(strtolower(...))
    ->pipe($processor->toUpperCase(...));

$result = $pipeline->process('  Hello World  '); // 'HELLO WORLD'
```

## Why

- **Concise Syntax**: `$obj->method(...)` instead of `Closure::fromCallable()`
- **IDE Support**: Full autocompletion, refactoring, and navigation
- **Type Safety**: Closures maintain the callable's type signature
- **Refactoring Safe**: Renaming methods updates references automatically
- **Consistency**: Same syntax for instance, static, and global functions
- **Functional PHP**: Enables cleaner functional programming patterns
