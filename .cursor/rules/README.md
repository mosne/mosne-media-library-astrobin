# WordPress Development Rules

This directory contains a comprehensive set of rules for WordPress development, organized by category. These rules are designed to help maintain consistent coding practices and standards across WordPress projects.

## Using These Rules

Each rule file follows a consistent format:

```
---
description: Brief description of the rule
globs: "**/*.php,**/*.js,**/*.scss" (file patterns where this rule applies)
---

# Rule Title

You're a WordPress [area] expert. Generate high-quality code that follows best practices for [specific area].

## General Guidelines

1. **Guideline Category:**
   - Specific guideline
   - Another guideline
   - More guidelines

2. **Another Guideline Category:**
   - Specific guideline
   - Another guideline
   - More guidelines

## Code Examples

### Example Title
```code
// Code example
```

### Another Example Title
```code
// Another code example
```
```

To reference a rule from another rule, use the following syntax:

```
[wp-rule-name.mdc](mdc:rules/wp-rule-name.mdc)
```

or for rules in the same category:

```
[wp-rule-name.mdc](wp-rule-name.mdc)
```
