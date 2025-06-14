# Claude Coding Rules for PHPStan Extension

## Core Principles

```javascript
if (PHPStanCoreAlreadyHandlesIt) {
    return "do not duplicate";
} else if (existingPatternInProject) {
    return "follow existing pattern exactly";
}
```

## Type Creation Rules

### Safe String Types
```php
// ❌ WRONG
return TypeCombinator::intersect(new StringType(), new AccessorySafeStringType());

// ✅ CORRECT - Always use IntersectionType directly
return new IntersectionType([
    new StringType(),
    new AccessorySafeStringType(),
]);
```

### Return Types
```php
// ❌ WRONG - Never return Type (non-nullable)
public function getTypeFromFunctionCall(...): Type

// ✅ CORRECT - Extensions can return null
public function getTypeFromFunctionCall(...): ?Type
```

## Extension Registration

### Custom Tags Required
```yaml
# ❌ WRONG - Interferes with PHPStan core
tags: [phpstan.broker.dynamicMethodReturnTypeExtension]

# ✅ CORRECT - Use custom prefix
tags: [nish.phpstan.broker.dynamicMethodReturnTypeExtension]
```

## Architecture Rules

### Stateless Extensions
```php
// ❌ WRONG - Dependency on PHPStan core
public function __construct(
    private \PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension $parent
) {}

// ✅ CORRECT - No constructor needed
// (PHPStan core checking handled by ExpressionTypeResolverExtension)
```

## Testing Rules

### Test File Structure
```
tests/
├── Rules/
│   ├── EchoHtmlRuleTest.php        # Test class
│   └── data/
│       └── echo-html.php           # Test data (PHP code to analyze)
└── integration/
    └── safe-string.php             # Full PHPStan analysis test
```

### Test Pattern
```php
public function testRule(): void
{
    $this->analyse([__DIR__ . '/data/echo-html.php'], [
        [
            'Only safehtml-string is allowed for echo/print.',
            10,  // Line number
        ],
    ]);
}
```

## File Organization

- `src/Type/` - Type extensions only
- `src/Rules/` - Analysis rules only
- `src/Type/Accessory/` - Custom accessory types
- `extension.neon` - All service registration
