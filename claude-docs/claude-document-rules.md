# Claude Documentation Rules

This document defines **absolute rules** for creating and updating documentation in the phpstan-safestring-rule project.

## [CRITICAL] Write in a Format Claude Can Easily Understand

### Language Requirement
**All documentation must be written in English.**

## Purpose of Documentation

```javascript
// Documentation decision criteria
if (information.isProjectSpecific) {
    return "write";
} else if (information.isGeneralKnowledge) {
    return "do not write";  // Claude already knows this
} else if (information.isFictitiousExample) {
    return "absolutely do not write";  // Source of confusion
}
```

## What to Write (Required)

### 1. Project-Specific Rules and Constraints
```markdown
✅ Good example:
- Custom tags use `nish.phpstan.*` prefix (not standard `phpstan.*`)
- Safe string types require IntersectionType (not TypeCombinator)
- All extensions must be registered in extension.neon
```

### 2. Patterns Extracted from Actual Code
```markdown
✅ Good example:
// From src/Type/ExpressionTypeResolverExtension.php:356-357
return TypeCombinator::intersect(new StringType(), new Accessory\AccessorySafeStringType());
```

### 3. Historical Exceptions and Mixed Patterns
```markdown
✅ Good example:
- Type resolution has evolved through 3 approaches:
  - Direct PHPStan core wrapping: Old approach, being phased out
  - Custom tags to avoid conflicts: Current approach
  - MethodCallProxy pattern: Latest refactoring for maintainability
```

### 4. Common Mistakes and Correct Patterns
```markdown
✅ Good example:
❌ Wrong: return TypeCombinator::intersect(new StringType(), new AccessorySafeStringType());
✅ Correct: return new IntersectionType([new StringType(), new AccessorySafeStringType()]);
Reason: TypeCombinator is for unknown type combinations, IntersectionType matches PHPDoc resolution

✅ Good example:
❌ Wrong: use standard phpstan.broker.dynamicMethodReturnTypeExtension tag
✅ Correct: use nish.phpstan.broker.dynamicMethodReturnTypeExtension tag
Reason: Prevents interference with PHPStan core's type resolution
```

**Important**: For patterns that are "generally correct but wrong in this project" due to project-specific constraints, always include:
1. ❌ mark with clear warnings like "NG", "will not work"
2. Reason why it's wrong (e.g., "interferes with PHPStan core", "breaks type resolution")
3. ✅ correct pattern for comparison

## PHPStan Extension Architecture Documentation Rules

### Required Elements for Extension Documentation

#### 1. Architecture Diagrams (ASCII)
```
Example: Safe String Type Hierarchy
Type
├── StringType (PHPStan core)
│   └── IntersectionType
│       ├── StringType
│       └── AccessorySafeStringType (our extension)
│
└── AccessorySafeHtmlStringType (our extension)
    └── Extends AccessorySafeStringType
```

#### 2. Important Constraints and Flag Meanings
```markdown
✅ Good example:
【RuleHelper::accepts()】
- Returns true for: constant strings, numeric strings, safe-string types
- Returns false for: plain strings that need validation
- Usage: Determines if PHPStan core type is already safe
```

#### 3. Structure Explanation with Real Examples
```
Extension Registration: extension.neon
├── ExpressionTypeResolverExtension
│   └── Tags: [phpstan.broker.expressionTypeResolverExtension]
│
├── PHP Function Extensions
│   ├── SprintfFunctionDynamicReturnTypeExtension
│   ├── ImplodeFunctionDynamicReturnTypeExtension
│   └── Registered via ExpressionTypeResolverExtension
│
└── Custom Tags (nish.phpstan.*)
    └── Prevents PHPStan core interference
```

#### 4. Common Misunderstanding Patterns
```markdown
❌ Misunderstanding: DynamicReturnTypeExtension needs PHPStan core class in constructor
✅ Correct: After refactoring, extensions are stateless with no constructor
Reason: ExpressionTypeResolverExtension handles core type checking centrally

❌ Misunderstanding: Can add native type hints to MethodCallProxy implementations
✅ Correct: Use PHPDoc annotations only, no native type hints
Reason: PHP variance rules prevent concrete types in interface implementations
```

### Extension Implementation Documentation Steps

#### Step 1: Verify with Actual Code
```bash
# Always verify with actual files (no fictional examples)
grep -n "IntersectionType" src/Type/Php/*.php
# Result: All PHP extensions use IntersectionType pattern
```

#### Step 2: Check Constraints
```php
// Service registration pattern
services:
  -
    class: Nish\PHPStan\Type\ExpressionTypeResolverExtension
    tags: [phpstan.broker.expressionTypeResolverExtension]
```

#### Step 3: Express with ASCII Diagrams
```
ExpressionTypeResolverExtension (central orchestrator)
├── Checks if our extension supports method/function
├── Gets PHPStan core type if supported
├── Calls our extension only if core type not safe
└── Returns: Core type OR Our enhanced type
```

### Extension Documentation Quality Standards

1. **Self-Contained**: New Claude instances can understand without external context
2. **ASCII-Centric**: Visual representation of hierarchies and flow
3. **Real Code**: Use actual file paths like src/Type/ExpressionTypeResolverExtension.php
4. **Verifiable**: Include grep commands or file:line references
5. **Pitfall Prevention**: Document historical decisions (e.g., custom tags)

## What Not to Write (Forbidden)

### 1. General Programming Knowledge
```markdown
❌ Bad example:
- How to write PHPDoc comments
- PHP basic syntax
- General design pattern explanations
```

### 2. Fictitious Example Code
```markdown
❌ Bad example:
class MySafeStringType extends AccessoryType {  // Non-existent class
    public function accepts(): bool { return true; }
}
```

### 3. Patterns Not Used in the Project
```markdown
❌ Bad example:
@template T of string  // Project doesn't use PHP generics syntax
```

### 4. General Explanations to Claude
```markdown
❌ Bad example:
"This follows the Decorator pattern..."  // Unnecessary explanation
```

## Required Steps When Creating Documentation

### Step 1: Verify Real Code
```bash
# Confirm the pattern exists
grep -r "IntersectionType" src/
grep -r "AccessorySafeStringType" src/

# Identify actual usage locations
# Record filename:line_number
```

### Step 2: Documentation Format
```markdown
## Feature/Pattern Name

### Implementation Location
- `src/Type/ExpressionTypeResolverExtension.php:356-357` - IntersectionType usage
- `src/Type/Php/SprintfFunctionDynamicReturnTypeExtension.php:40-44` - Pattern example

### Project-Specific Rules
1. Must use IntersectionType for safe-string (not TypeCombinator)
2. Custom tags with nish.phpstan.* prefix

### Implementation Example (from actual code)
\```php
// From src/Type/Php/SprintfFunctionDynamicReturnTypeExtension.php:40-44
return new IntersectionType([
    new StringType(),
    new AccessorySafeStringType(),
]);
\```
```

### Step 3: Verification Checklist
- [ ] All examples extracted from existing code?
- [ ] Includes filename:line_number?
- [ ] Not explaining general knowledge?
- [ ] Only project-specific information?

## Documentation Update Rules

### When to Update Immediately
```javascript
if (implementationDocumentationMismatch) {
    return "fix immediately";
} else if (newFeatureImplemented) {
    return "update related documentation";
} else if (implementationPatternChanged) {
    return "update all affected documentation";
}
```

### Update Records
```markdown
## Update History
- 2024-XX-XX: Updated after [filename] implementation change
- 2024-XX-XX: Added new section for [feature]
```

## Quality Standards

### Good Documentation Characteristics
1. **Specific**: Includes filename:line_number
2. **Real**: All examples exist in actual code
3. **Unique**: Only project-specific information
4. **Practical**: Directly usable for implementation

### Bad Documentation Characteristics
1. **Abstract**: General theory or explanations
2. **Fictitious**: Non-existent examples or patterns
3. **Redundant**: Information Claude already knows
4. **Outdated**: Diverged from implementation

## Scope of These Rules

### Apply to These Documents
- All technical documents under `claude-docs/`
- Technical sections in `CLAUDE.md`
- Implementation guides and pattern collections

### Do Not Apply to
- README.md (for humans)
- Commit messages
- Code comments (keep minimal)

## Final Verification

After creating documentation, ask yourself:

1. **Without this document, could Claude implement correctly?**
   - Yes → Necessary documentation
   - No → Possibly unnecessary

2. **Can this be solved with general PHPStan/PHP knowledge?**
   - Yes → Should be deleted
   - No → Project-specific, keep it

3. **Can a new Claude instance read this in 1 year and implement immediately?**
   - Yes → Good documentation
   - No → Needs improvement
