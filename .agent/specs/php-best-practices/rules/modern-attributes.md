# PHP Attributes

**Impact:** HIGH — Type-safe metadata with IDE support, replaces annotations

**Tags:** modern-features, attributes, metadata, php8

Use native attributes for metadata instead of docblock annotations
(PHP 8.0+).

## Bad Example

```php
<?php

declare(strict_types=1);

// Using docblock annotations - parsed as strings, no type safety
class UserController
{
    /**
     * @Route("/users/{id}", methods={"GET"})
     * @Cache(maxage=3600)
     */
    public function show(int $id)
    {
        // Annotations are just comments - no IDE support for validation
    }
}

// Validation using docblocks
class CreateUserRequest
{
    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public string $email;
}
```

## Good Example

```php
<?php

declare(strict_types=1);

// Define custom attributes
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
        public ?string $name = null,
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class Cache
{
    public function __construct(
        public int $maxAge = 0,
        public bool $public = true,
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    public function __construct(
        public array $rules = [],
    ) {}
}

// Using attributes on a controller
class UserController
{
    #[Route('/users/{id}', methods: ['GET'], name: 'user.show')]
    #[Cache(maxAge: 3600)]
    public function show(int $id): Response
    {
        return new Response($this->userService->find($id));
    }

    #[Route('/users', methods: ['POST'], name: 'user.create')]
    public function create(CreateUserRequest $request): Response
    {
        return new Response($this->userService->create($request));
    }
}

// Using attributes for validation
class CreateUserRequest
{
    #[Validate(rules: ['required', 'email', 'unique:users'])]
    public string $email;

    #[Validate(rules: ['required', 'min:8', 'confirmed'])]
    public string $password;
}

// Reading attributes via reflection
function getRoutes(object $controller): array
{
    $routes = [];
    $reflection = new ReflectionClass($controller);

    foreach ($reflection->getMethods() as $method) {
        $attributes = $method->getAttributes(Route::class);
        foreach ($attributes as $attribute) {
            $route = $attribute->newInstance();
            $routes[] = [
                'path' => $route->path,
                'methods' => $route->methods,
                'handler' => [$controller, $method->getName()],
            ];
        }
    }

    return $routes;
}
```

## Why

- **Type Safety**: Attributes are real classes with typed constructors
- **IDE Support**: Full autocompletion, refactoring, and navigation
- **Validation**: Invalid attribute usage caught by static analysis tools
- **Native Feature**: Built into PHP, no external parser needed
- **Performance**: Faster than parsing docblocks at runtime
- **Named Arguments**: Clear parameter names in attribute usage
