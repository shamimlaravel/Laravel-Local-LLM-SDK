# Contributing to Laravel Local LLM SDK

Thank you for considering contributing to Laravel Local LLM SDK!

## Code of Conduct

By participating in this project, you are expected to uphold our [Code of Conduct](https://github.com/laravel-local-llm/sdk/blob/main/CODE_OF_CONDUCT.md).

## How to Contribute

### Reporting Bugs

1. Search existing issues first
2. Create a detailed issue with:
   - Clear title
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - PHP/Laravel version

### Suggesting Features

1. Check existing discussions
2. Create an issue with:
   - Clear description
   - Use cases
   - Proposed implementation

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Follow our coding standards
4. Write tests for new features
5. Push to your fork
6. Submit a pull request

## Development Setup

```bash
# Clone repository
git clone https://github.com/laravel-local-llm/sdk.git
cd sdk

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Fix code style
composer style
```

## Coding Standards

### Type Safety

See [TYPE_SAFETY_CHECKLIST.md](docs/TYPE_SAFETY_CHECKLIST.md) for detailed requirements.

Key points:
- Always use `declare(strict_types=1)`
- All methods must have return types
- All parameters must have type hints
- Use readonly classes for DTOs

### PHPStan

We use PHPStan at level max. All errors must be fixed before merging:

```bash
composer analyse
```

### Code Style

We use Pint for code style:

```bash
# Check style
composer style:check

# Fix style
composer style
```

### Testing

All new features must include tests:

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage
```

## Commit Messages

Follow conventional commits:

```
feat: add new feature
fix: resolve bug
docs: update documentation
style: code style changes
refactor: code refactoring
test: add tests
chore: maintenance
```

## Pull Request Process

1. Update documentation for any changes
2. Add tests for new functionality
3. Ensure PHPStan passes
4. Ensure Pint shows no errors
5. Update CHANGELOG.md
6. Request review from maintainers

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
