# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Development Commands

### Testing
```bash
# Run all tests
php vendor/bin/phpunit -c tests/phpunit.xml

# Run specific test file
php vendor/bin/phpunit -c tests/phpunit.xml tests/Rules/EchoHtmlRuleTest.php

# Run tests with coverage
php vendor/bin/phpunit -c tests/phpunit.xml --coverage-text
```

### Static Analysis
```bash
# Run PHPStan on the codebase
php vendor/bin/phpstan analyse --configuration phpstan.neon

# Clear PHPStan cache when debugging type issues
php vendor/bin/phpstan clear-result-cache --configuration phpstan.neon
```

### Code Style
```bash
# Check code style
php vendor/bin/phpcs --standard=phpcs.xml src/ tests/

# Fix code style issues
php vendor/bin/phpcbf --standard=phpcs.xml src/ tests/
```

## Architecture Overview

This PHPStan extension introduces virtual types (`safe-string` and `safehtml-string`) to prevent security vulnerabilities through static analysis.

### Core Components

1. **Custom Types** (in `src/Type/Accessory/`)
   - `AccessorySafeStringType`: Marks strings safe from SQL injection
   - `AccessorySafeHtmlStringType`: Marks strings safe from XSS

2. **Rules** (in `src/Rules/`)
   - `EchoHtmlRule`: Ensures only safe HTML strings are echoed
   - `SafeStringCallRule`: Validates method parameters expect safe strings
   - `SafeStringReturnTypeRule`: Enforces safe string return types

3. **Type Extensions** (in `src/Type/`)
   - Dynamic return type extensions for PHP functions (sprintf, implode, str_replace, trim)
   - `SafeHtmlStringReturnTypeExtension`: Configurable extension for marking functions as returning safe strings
   - `ExpressionTypeResolverExtension`: Handles type resolution for safe string expressions

### Key Design Patterns

- **Type Propagation**: Safe string types propagate through PHP string functions intelligently (e.g., sprintf with safe format string produces safe output)
- **Configuration-Based Safety**: Users configure which functions produce safe strings via `extension.neon`
- **PHPDoc Integration**: Supports `@param safe-string` and `@return safe-string` annotations
- **Constant String Exemption**: Literal strings and constants are automatically considered safe

### Testing Strategy

- Unit tests for each rule and type extension
- Integration tests in `tests/integration/` run full PHPStan analysis
- Test data files in `tests/Rules/data/` contain PHP code snippets for testing rules
- Level 10 tests in `tests/integration10/` test strictest PHPStan settings

### Extension Points

When adding new features:
1. New safe string producers: Add to `SafeHtmlStringReturnTypeExtension` configuration
2. New PHP function support: Create extension in `src/Type/Php/`
3. New validation rules: Extend `AbstractRule` in `src/Rules/`
4. Register services in `extension.neon` with appropriate tags

## Implementation Details

### Custom Tags to Avoid PHPStan Core Interference

**IMPORTANT**: Always use custom `nish.phpstan.*` tags instead of PHPStan's core `phpstan.*` tags to avoid interference with PHPStan's internal processing.

- Use `nish.phpstan.broker.dynamicFunctionReturnTypeExtension` instead of `phpstan.broker.dynamicFunctionReturnTypeExtension`
- Use `nish.phpstan.broker.dynamicMethodReturnTypeExtension` instead of `phpstan.broker.dynamicMethodReturnTypeExtension`

This separation ensures that our extensions don't conflict with PHPStan's built-in type resolution system.

### ExpressionTypeResolverExtension Implementation

The `ExpressionTypeResolverExtension` class is crucial for resolving safe string types.

**For detailed PHPStan core patterns, see: `claude-docs/phpstan-core-patterns.md`**

Key implementation aspects:
- Uses `$typeWithMethod` variable name (from PHPStan core)
- Implements lazy initialization for class indexes
- Handles class hierarchy (parent classes and interfaces)
- Uses `$class->getNativeReflection()->getInterfaceNames()` for API compatibility

### DynamicReturnTypeExtension Class

The `DynamicReturnTypeExtension` class serves as a base for function and method return type extensions:

1. **Constructor parsing**: Supports both function names and class::method notation
   - `htmlspecialchars` → function extension
   - `DateTimeInterface::format` → method extension for DateTimeInterface and its subclasses

2. **getClass() method**: Returns the class name for method extensions, used by `ExpressionTypeResolverExtension` for indexing

### Known Issues and Solutions

1. **Issue #1**: SafeHtmlStringReturnTypeExtension not triggered for internal PHP functions
   - **Cause**: PHPStan core interference when using standard tags
   - **Solution**: Use custom `nish.phpstan.*` tags

2. **API Compatibility**: See `claude-docs/phpstan-core-patterns.md#api-compatibility`

### Testing Integration

When running integration tests:
- Tests compare actual PHPStan output with expected errors
- `DateTimeInterface::format` calls should not produce "not safehtml-string" errors when configured
- Test files in `tests/integration/` contain real-world usage examples

## Static Method Support

### Implementation (Added to avoid PHPStan core interference)

The extension also supports static method return type extensions through custom tags:

1. **Tag**: `nish.phpstan.broker.dynamicStaticMethodReturnTypeExtension`
2. **Variables**:
   - `$dynamicStaticMethodReturnTypeExtensions`: Array of static method extensions
   - `$dynamicStaticMethodReturnTypeExtensionsByClass`: Indexed by class name (lazy initialization)

3. **Key Implementation Details**: See `claude-docs/phpstan-core-patterns.md#mutatingscope-method-call-pattern`

4. **Example Configuration**:
   ```yaml
   -
     factory: Nish\PHPStan\Type\SafeHtmlStringReturnTypeExtension(SomeClass::staticMethod)
     tags: [nish.phpstan.broker.dynamicStaticMethodReturnTypeExtension]
   ```

### Why No Tests for Static Methods

- This feature exists to avoid PHPStan core interference
- Finding PHP standard library static methods that PHPStan core already handles is difficult
- The implementation follows the same pattern as dynamic methods (which are tested)
- If future conflicts arise with PHPStan core, this infrastructure is ready

### Type Safety

For PHPDoc type casting patterns, see: `claude-docs/phpstan-core-patterns.md#phpstan-api-usage-and-type-safety`

## PHPStan Core Type Priority

For detailed information about type resolution and recursion prevention, see: `claude-docs/phpstan-core-patterns.md#type-resolution-and-recursion-prevention`

Key points:
- PHPStan core's specific types (like `'0'|'1'|...|'6'` for `format('w')`) are preserved
- Per-node recursion tracking using `spl_object_hash()`
- RuleHelper::accepts() determines which types are already safe

## Performance Optimization

For detailed performance optimization patterns, see: `claude-docs/phpstan-core-patterns.md#performance-optimization-pattern`

Key optimizations:
- Check extension support before calling PHPStan core
- Cache core extension lookups
- Unified handling for method/static calls

### Performance Benefits
- Eliminates unnecessary `$scope->getType()` calls
- Direct extension invocation is faster than full type resolution
- Cache prevents repeated lookups for the same method/function
- Lazy initialization avoids upfront computation cost

### PHPStan API Usage Note
When using internal PHPStan APIs like `DynamicReturnTypeExtensionRegistry`, add to `phpstan.neon`:
```yaml
ignoreErrors:
  - identifier: phpstanApi.method
    path: src/Type/ExpressionTypeResolverExtension.php
```

## Code Patterns and Conventions

### PHPStan Type Analysis Considerations

1. **Null State Tracking**: PHPStan cannot always track property state changes through method calls:
   ```php
   // This pattern causes PHPStan errors:
   if ($this->property === null) {
       $this->buildProperty(); // Sets $this->property
   }
   // PHPStan still thinks $this->property might be null here

   // Solution: Inline the initialization
   if ($this->property === null) {
       $this->property = /* initialization code */;
   }
   // PHPStan now knows $this->property is not null
   ```

2. **Return Type Awareness**: Some PHPStan methods always return non-null types:
   - `InitializerExprTypeResolver::getType()` always returns `Type`, never null
   - Avoid unnecessary null checks on such return values

### Optimization Techniques in ExpressionTypeResolverExtension

The class uses several optimization patterns:

1. **Lazy Extension Checking**: Only checks PHPStan core extensions when our extension supports the method/function
2. **Two-Level Caching**:
   - Extension index by class (built on first use)
   - Core extension support cache (which core extensions support which methods)
3. **Early Returns**: Reduces nesting and improves readability
4. **Unified Processing**: Single method handles both MethodCall and StaticCall to reduce duplication

### Method Organization

- `getType()`: Entry point, delegates to specific handlers
- `getTypeConcat()`: Handles string concatenation with safety checking
- `getTypeFunction()`: Processes function calls
- `getTypeMethodCall()`: Unified handler for method and static calls
- `getCoreTypeFor*()`: Checks PHPStan core extensions (with caching)
- `getDynamic*ExtensionsForClass()`: Gets our extensions for a specific class

## Refactoring with MethodCallProxy Pattern

### Problem
The `ExpressionTypeResolverExtension` had complex if/else branches for handling dynamic methods vs static methods:
- Repeated type checking with `instanceof`
- Duplicated logic for method support checking
- Difficult to maintain PHPStan type safety through conditional branches
- PHPCS errors when using PHPDoc type hints for PHPStan

### Solution: MethodCallProxy Interface and Implementations

Created a unified interface to handle both dynamic and static method calls:

1. **Interface Design** (`src/Type/ExpressionTypeResolverExtension/MethodCallProxy.php`):
   ```php
   /**
    * @template T of StaticCall|MethodCall
    * @template U of DynamicMethodReturnTypeExtension|DynamicStaticMethodReturnTypeExtension
    */
   interface MethodCallProxy
   {
       public function isSupported($extension, MethodReflection $methodReflection): bool;
       public function getTypeFromMethodCall($extension, MethodReflection $methodReflection, $normalizedNode, Scope $scope): ?Type;
   }
   ```

2. **Implementations**:
   - `MethodCallProxyDynamic`: Handles `MethodCall` with `DynamicMethodReturnTypeExtension`
   - `MethodCallProxyStatic`: Handles `StaticCall` with `DynamicStaticMethodReturnTypeExtension`

3. **Factory Pattern**:
   ```php
   private function createMethodCallResolver(MethodCall|StaticCall $call): MethodCallProxy
   {
       return $call instanceof StaticCall
           ? new MethodCallProxyStatic($extensions)
           : new MethodCallProxyDynamic($extensions);
   }
   ```

### Benefits
- Eliminated conditional branches in main logic
- Single responsibility for each proxy class
- Type safety maintained through generics
- Easier to test and maintain

### PHPCS Compatibility Issue

**Problem**: PHPCS requires native type hints based on PHPDoc annotations, but adding concrete types to interface implementations violates PHP's variance rules.

**Solution**:
1. Keep interface methods without native type hints
2. Use PHPDoc for type information
3. Add PHPCS exclusion rule in `phpcs.xml`:
   ```xml
   <rule ref="SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint">
       <exclude-pattern>src/Type/ExpressionTypeResolverExtension/MethodCallProxy*.php</exclude-pattern>
   </rule>
   ```

This approach maintains type safety through PHPStan while satisfying PHPCS requirements.

### Implementation Notes

1. **Public Properties**: The proxy classes use public `$extensions` property for direct access in `ExpressionTypeResolverExtension`
2. **Generics Usage**: Leverages PHPStan's generic types for compile-time type checking
3. **No Additional Files**: All proxy-related code is contained within the `ExpressionTypeResolverExtension` directory

The refactoring successfully simplified the code while maintaining all functionality and type safety.

## PHP Function Extensions Simplification

### Background
After implementing the performance optimization in `ExpressionTypeResolverExtension`, the PHP function extensions in `src/Type/Php/` became redundant in their PHPStan core checking functionality.

### Previous Architecture
Each PHP function extension (sprintf, implode, replace, trim) was designed to:
1. Accept PHPStan core's equivalent extension as a constructor parameter
2. Call the core extension first to get the original type
3. Check if the result was already safe using `RuleHelper::accepts()`
4. Only add `AccessorySafeStringType` if needed

### New Architecture
With the optimized `ExpressionTypeResolverExtension`:
1. It checks if our extension supports the function/method
2. If yes, it checks PHPStan core's type first
3. Only calls our extension if the core type is not safe

This made the core checking in individual extensions redundant.

### Changes Made

1. **Removed PHPStan Core Dependencies**:
   ```php
   // Before
   public function __construct(
       private \PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension $parentClass,
   ) {}

   // After
   // Constructor completely removed - no longer needed
   ```

2. **Simplified `isFunctionSupported()`**:
   ```php
   // Before
   return $this->parentClass->isFunctionSupported($functionReflection);

   // After
   return $functionReflection->getName() === 'sprintf' || $functionReflection->getName() === 'vsprintf';
   ```

3. **Simplified `getTypeFromFunctionCall()`**:
   ```php
   // Before: Complex logic with core type checking
   // After: Simple safe-string check
   if (RuleHelper::isSafeAllArgs($functionCall, $scope)) {
       return new IntersectionType([
           new StringType(),
           new AccessorySafeStringType(),
       ]);
   }
   return null;
   ```

### Important: IntersectionType Usage

See `claude-docs/phpstan-core-patterns.md#common-mistakes-and-correct-patterns` for the correct way to construct safe-string types.

### Files Modified
- `src/Type/Php/SprintfFunctionDynamicReturnTypeExtension.php`
- `src/Type/Php/ImplodeFunctionDynamicReturnTypeExtension.php`
- `src/Type/Php/ReplaceFunctionsDynamicReturnTypeExtension.php`
- `src/Type/Php/TrimFunctionDynamicReturnTypeExtension.php`

All files now have no constructor at all, making them stateless and focused solely on type checking logic.

### Configuration Updates
- Removed `identifier: phpstanApi.method` for `path: src/Type/Php/*` from `phpstan.neon` as these files no longer use PHPStan internal APIs

### Benefits
1. **Simpler Code**: Each extension now has a single responsibility
2. **No Duplicate Checking**: Core type checking happens only in `ExpressionTypeResolverExtension`
3. **Better Performance**: Eliminates redundant core type resolution
4. **Clearer Architecture**: Separation of concerns between core checking and safe-string logic

### Testing
All tests pass after these changes, confirming that the functionality is preserved while the implementation is simplified.

## Advanced Implementation Details

### MethodCallProxy Pattern Implementation

The `ExpressionTypeResolverExtension` was refactored to use the MethodCallProxy pattern to eliminate complex if/else branches.

#### Problem Solved
- Complex conditional logic for handling dynamic vs static method calls
- Duplicated code for method support checking
- PHPStan type safety issues through conditional branches

#### Implementation Structure
```
src/Type/ExpressionTypeResolverExtension/
├── MethodCallProxy.php (interface) - lines 15-36
├── MethodCallProxyDynamic.php
└── MethodCallProxyStatic.php
```

- Interface definition: `src/Type/ExpressionTypeResolverExtension/MethodCallProxy.php:19`
- Usage in ExpressionTypeResolverExtension: `src/Type/ExpressionTypeResolverExtension.php:214-221`

#### PHPCS Compatibility Issue
```xml
<!-- In phpcs.xml -->
<rule ref="SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint">
    <exclude-pattern>src/Type/ExpressionTypeResolverExtension/MethodCallProxy*.php</exclude-pattern>
</rule>
```
Reason: PHPCS requires native type hints based on PHPDoc, but PHP's variance rules prevent adding concrete types to interface implementations.

### Recursion Prevention Implementation

#### Why Per-Node Tracking
The implementation uses node-specific tracking instead of a global flag:

```php
// From src/Type/ExpressionTypeResolverExtension.php:51-52
/** @var array<string, bool> */
private array $resolvingNodes = [];

// From src/Type/ExpressionTypeResolverExtension.php:83
$nodeKey = spl_object_hash($expr);
```

**Reason**: PHPStan may call `$scope->getType()` recursively for different nodes during type resolution. A global `$isResolving` flag would incorrectly block legitimate nested calls for different expressions.

#### Example Scenario
When resolving `sprintf($format, $arg1)`, PHPStan might:
1. Call getType() for the sprintf call
2. Internally call getType() for $format
3. Internally call getType() for $arg1

Each needs separate tracking.

### Cache Key Design

The implementation uses specific cache key formats:

```php
// From src/Type/ExpressionTypeResolverExtension.php:372
// Instance method: "DateTime::format"
// Static method: "DateTime::createFromFormat::static"
$cacheKey = $className . '::' . $methodReflection->getName() . ($call instanceof StaticCall ? '::static' : '');

// From src/Type/ExpressionTypeResolverExtension.php:430
// Function: "function::sprintf"
$cacheKey = 'function::' . $functionName;
```

**Design Decision**: The `::static` suffix distinguishes static methods from instance methods with the same name, preventing cache collisions.

### RuleHelper::accepts() Implementation Logic

`RuleHelper::accepts()` (from `src/Rules/RuleHelper.php:54-116`) determines which types don't need safety validation:

1. **Error Types**: Return true (no point in checking safety of errors)
2. **Non-String Types**: integers, booleans, null - return true
3. **Already Safe Strings**:
   - ConstantStringType (literal strings)
   - Numeric strings
   - Types with AccessorySafeStringType
   - Types with AccessorySafeHtmlStringType
4. **Objects**: Return true (they handle their own __toString)
5. **Plain Strings**: Return false (need safety validation)

### Performance Considerations

#### Extension Support Check First
The implementation always checks if our extension supports a method/function before checking PHPStan core:

```php
// From src/Type/ExpressionTypeResolverExtension.php:137-139 (functions)
if (!$dynamicFunctionReturnTypeExtension->isFunctionSupported($functionReflection)) {
    continue;
}

// From src/Type/ExpressionTypeResolverExtension.php:224-226 (methods)
if (!$methodCallProxy->isSupported($extension, $methodReflection)) {
    continue;
}
```

**Reason**: Calling `$scope->getType()` is expensive. Most methods/functions aren't covered by our extensions, so checking support first avoids unnecessary core type resolution.

### PHPDoc Type Casting Usage

When dealing with union return types from shared methods:

```php
$extensions = $this->getDynamicExtensionsForType(...);
/** @var array<int, DynamicMethodReturnTypeExtension> $extensions */
return $extensions;
```

**Why**: Avoids modifying shared method signatures while maintaining PHPStan's type safety. This pattern is used throughout when dealing with generic methods that return different types based on context.
