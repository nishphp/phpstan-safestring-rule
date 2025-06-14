# PHPStan Core Patterns Reference

This document contains PHPStan core implementation patterns that are referenced in phpstan-safestring-rule implementation.

## Dynamic Return Type Extension Registry Pattern

### Implementation Location
- PHPStan core: `src/Type/DynamicReturnTypeExtensionRegistry.php`

### Lazy Initialization Pattern
```php
// From PHPStan core DynamicReturnTypeExtensionRegistry
private ?array $dynamicMethodReturnTypeExtensionsByClass = null;

public function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
{
    if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
        $byClass = [];
        foreach ($this->dynamicMethodReturnTypeExtensions as $extension) {
            $byClass[strtolower($extension->getClass())][] = $extension;
        }
        $this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
    }

    // Get extensions for class hierarchy
    $extensions = [];
    foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
        if (isset($this->dynamicMethodReturnTypeExtensionsByClass[strtolower($extensionClassName)])) {
            $extensions = array_merge($extensions, $this->dynamicMethodReturnTypeExtensionsByClass[strtolower($extensionClassName)]);
        }
    }
    return $extensions;
}
```

### Key Implementation Details
- Variable naming: `$dynamicMethodReturnTypeExtensionsByClass`
- Lazy initialization pattern: Check null before building index
- API note: Use `$class->getNativeReflection()->getInterfaceNames()` for interface names

## MutatingScope Method Call Pattern

### Implementation Location
- PHPStan core: `src/Analyser/MutatingScope.php:1823-1828` (methodCallReturnType method)

### Unified Method/Static Call Handling
```php
// From PHPStan core MutatingScope::methodCallReturnType
private function methodCallReturnType(
    Type $typeWithMethod,
    string $methodName,
    CallLike $methodCall,
): ?Type {
    // ... (handles both MethodCall and StaticCall)

    if ($methodCall instanceof MethodCall) {
        $normalizedMethodCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);
    } else {
        $normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
    }
}
```

### Variable Name Convention
- `$typeWithMethod`: The type on which method is called (ObjectType)
- Used consistently throughout PHPStan core

### Key Patterns
- Variable name: `$typeWithMethod` (the type on which method is called)
- Unified handling: Single method processes both MethodCall and StaticCall
- Argument normalization: Different ArgumentsNormalizer methods for each type

## Type Resolution and Recursion Prevention

### Problem Context
When our extension adds safe-string types, it must not override PHPStan core's specific types like:
- `format('w')` returns `'0'|'1'|'2'|'3'|'4'|'5'|'6'`
- `format('Y')` returns `numeric-string`

### Implementation Pattern
```php
// Node-specific recursion tracking (our implementation)
/** @var array<string, bool> */
private array $resolvingNodes = [];

public function getType(Expr $expr, Scope $scope): ?Type
{
    $nodeKey = spl_object_hash($expr);

    if (isset($this->resolvingNodes[$nodeKey])) {
        return null;
    }

    $this->resolvingNodes[$nodeKey] = true;
    try {
        $coreType = $scope->getType($expr);
    } finally {
        unset($this->resolvingNodes[$nodeKey]);
    }

    if (RuleHelper::accepts($coreType)) {
        return $coreType;
    }
}
```

### Recursion Tracking Patterns
❌ Wrong: Global `$isResolving` flag
✅ Correct: Per-node tracking with `spl_object_hash()`
Reason: PHPStan may call `$scope->getType()` recursively for different nodes during type resolution

## PHPStan API Usage and Type Safety

### PHPDoc Type Casting Pattern
```php
// When PHPStan expects specific return type
$extensions = $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
/** @var array<int, DynamicMethodReturnTypeExtension> $extensions */
return $extensions;
```

### Project-Specific Rules
1. Use `@var` casting instead of modifying method signatures
2. Add `identifier: phpstanApi.method` to phpstan.neon when using internal APIs
3. Avoid PHPCS native type hint requirements that conflict with PHPStan

## Performance Optimization Pattern

### Selective Core Type Checking
```php
// Check our extension support first
if (!$dynamicMethodReturnTypeExtension->isMethodSupported($methodReflection)) {
    continue;
}

// Only then check PHPStan core
$coreType = $this->getCoreTypeForMethod($className, $methodReflection, $normalizedNode, $scope);
```

### Core Extension Caching
```php
/** @var array<string, array<string, true>> */
private array $coreExtensionCache = [];

// Cache key format:
// Methods: "ClassName::methodName" or "ClassName::methodName::static"
// Functions: "function::functionName"
```

### Performance Patterns
- Check extension support before expensive operations
- Cache extension lookups to avoid repeated searches
- Use lazy initialization for index building

## Common Mistakes and Correct Patterns

### Type Construction Patterns
❌ Wrong: `TypeCombinator::intersect()` for known type combinations
✅ Correct: Direct construction with `new IntersectionType()` for known types
Reason: TypeCombinator is for runtime type combination, direct construction matches PHPDoc resolution

### Interface Names API
❌ Wrong: `$class->getInterfaceNames()`
✅ Correct: `$class->getNativeReflection()->getInterfaceNames()`
Reason: API compatibility across PHPStan versions

## PHPStan Extension Architecture

### Extension Resolution Flow
```
Type Resolution Process
├── Expression received by resolver
├── Check expression type (FuncCall/MethodCall/StaticCall)
├── Find relevant extensions
├── For each extension:
│   ├── Check if extension supports the call
│   ├── Get extension's return type
│   └── Combine or override types as needed
└── Return final resolved type
```

### Extension Registry Pattern
- Extensions are registered with tags in DI container
- Registry builds lazy indexes by class name
- Method/function lookups use these indexes for performance

## PHPStan Internal API Details

### DynamicReturnTypeExtensionRegistryProvider
```php
// Dependency injection pattern for accessing core registry
private function getCoreRegistry(): DynamicReturnTypeExtensionRegistry
{
    if ($this->coreRegistry === null) {
        $this->coreRegistry = $this->coreRegistryProvider->getRegistry();
    }
    return $this->coreRegistry;
}
```

### ArgumentsNormalizer Usage
```php
// For method calls
$normalizedCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);

// For static calls
$normalizedCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $staticCall);
```
Purpose: Handles named arguments and parameter reordering in PHP 8+

### InitializerExprTypeResolver
**Important**: The `getType()` method always returns a `Type` object, never null. Avoid unnecessary null checks:
```php
// From PHPStan core
public function getType(Expr $expr, Scope $scope): Type
{
    // Always returns Type, never null
}
```

## Type Resolution Recursion

### Why Recursion Happens
PHPStan's type resolution is recursive by nature. When resolving a method call type:
1. PHPStan may need to resolve the type of the object
2. May need to resolve types of arguments
3. May trigger other extension resolvers

Example call stack:
```
getType(MethodCall: $date->format('Y'))
├── getType(Variable: $date)
├── getType(String: 'Y')
└── Extensions may call getType() again
```

### Global Flag Problem
A global `$isResolving` flag fails because:
```php
// Wrong approach
if ($this->isResolving) {
    return null; // This would block legitimate nested calls
}
```

## PHPStan Type System Patterns

### Type Narrowing
PHPStan narrows types based on context:
- `format('w')` → `'0'|'1'|'2'|'3'|'4'|'5'|'6'`
- `format('Y')` → `numeric-string`
- `format('Y-m-d')` → `non-empty-string`

### TypeCombinator vs Direct Construction
```php
// TypeCombinator: For combining unknown types at runtime
$type = TypeCombinator::union($type1, $type2);

// Direct construction: When you know the exact types
$type = new UnionType([$type1, $type2]);
$type = new IntersectionType([new StringType(), new AccessorySafeStringType()]);
```

**Rule**: Use direct construction for known type combinations (like safe-string).
