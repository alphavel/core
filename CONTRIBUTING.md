# Contributing to Alphavel

Thank you for considering contributing to Alphavel! We welcome contributions from everyone.

## 🤝 Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code.

## 📋 How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the behavior
- **Expected behavior** vs actual behavior
- **Environment details** (PHP version, Swoole version, OS)
- **Code samples** or test cases if possible

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear title** that summarizes the suggestion
- **Provide detailed description** of the proposed functionality
- **Explain why this would be useful** to most Alphavel users
- **List similar implementations** in other frameworks if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Install dependencies**: `composer install`
3. **Make your changes** following our coding standards
4. **Add tests** for any new functionality
5. **Ensure tests pass**: `composer test`
6. **Format code**: `composer pint`
7. **Update documentation** if needed
8. **Commit with clear message** describing the changes
9. **Submit pull request** with detailed description

## 🏗️ Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/alphavel.git
cd alphavel

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer pint

# Run static analysis
composer phpstan
```

## 📝 Coding Standards

Alphavel follows PSR-12 coding standards with Laravel Pint for formatting.

### Key Principles

- **Type hints**: Always use strict types and return type declarations
- **Documentation**: Add PHPDoc blocks for complex methods
- **Naming**: Use descriptive variable/method names
- **Single Responsibility**: Keep classes and methods focused
- **DRY**: Don't repeat yourself

### Example

```php
<?php

declare(strict_types=1);

namespace Alphavel\Example;

/**
 * Handles user authentication operations.
 */
class AuthManager
{
    /**
     * Authenticate user with credentials.
     *
     * @param array<string, mixed> $credentials
     * @return bool True if authenticated
     */
    public function attempt(array $credentials): bool
    {
        // Implementation
        return true;
    }
}
```

## 🧪 Testing

All new features must include tests. We use PHPUnit for testing.

```bash
# Run all tests
composer test

# Run specific test
vendor/bin/phpunit tests/Unit/SpecificTest.php

# Run with coverage
composer test-coverage
```

### Test Structure

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Alphavel\Framework\Response;

class ResponseTest extends TestCase
{
    public function test_json_response_returns_correct_format(): void
    {
        $response = Response::json(['key' => 'value']);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    }
}
```

## 📦 Multi-Repo Structure

Alphavel uses a multi-repository structure. Each package is in its own repository:

- **alphavel/alphavel** - Framework core
- **alphavel/database** - Database layer
- **alphavel/cache** - Cache layer
- **alphavel/validation** - Validation
- **alphavel/events** - Event system
- **alphavel/logging** - Logging
- **alphavel/support** - Utilities

When contributing:
1. Identify the correct package for your change
2. Submit PR to that specific repository
3. Update tests in the same PR
4. Link related PRs if changes span multiple packages

## 🔄 Git Workflow

We use Git Flow with these branches:

- **main**: Production-ready code
- **develop**: Integration branch for features
- **feature/**: New features (`feature/add-middleware-support`)
- **bugfix/**: Bug fixes (`bugfix/fix-route-params`)
- **hotfix/**: Critical production fixes

### Branch Naming

- `feature/descriptive-name` - New features
- `bugfix/issue-description` - Bug fixes
- `hotfix/critical-fix` - Emergency fixes
- `refactor/component-name` - Code improvements

### Commit Messages

Follow conventional commits:

```
type(scope): subject

body (optional)

footer (optional)
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Formatting changes
- `refactor`: Code restructuring
- `perf`: Performance improvement
- `test`: Adding tests
- `chore`: Maintenance tasks

Examples:
```
feat(routing): add route group middleware support

Implement middleware stacking for route groups, allowing
multiple middlewares to be applied to a group of routes.

Closes #123
```

```
fix(database): resolve connection pool leak

Fixed issue where connections weren't being properly
released back to the pool after query execution.

Fixes #456
```

## 📚 Documentation

When adding features:

1. Update relevant documentation files
2. Add code examples
3. Update CHANGELOG.md
4. Add PHPDoc comments

## 🎯 Priority Areas

We especially welcome contributions in:

- **Performance optimizations**
- **Additional database drivers**
- **Cache drivers** (Memcached, File, etc)
- **Validation rules**
- **CLI commands**
- **Documentation improvements**
- **Test coverage**

## ❓ Questions?

- **Discussions**: Use GitHub Discussions for questions
- **Issues**: Only for bugs and feature requests
- **Security**: Email security@alphavel.dev (don't create public issues)

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for making Alphavel better! 🚀
