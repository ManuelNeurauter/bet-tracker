# BetLedger Contribution Guidelines

Thank you for considering contributing to BetLedger! This document provides guidelines and instructions for contributing.

## Code of Conduct

- Be respectful and professional
- Focus on constructive feedback
- Welcome diverse perspectives

## Getting Started

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Test thoroughly
5. Commit with clear messages: `git commit -m "Add feature: description"`
6. Push to your branch: `git push origin feature/your-feature`
7. Submit a pull request

## Code Style

### PHP
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add docblocks to classes and public methods
- Keep functions focused and concise

```php
/**
 * Calculate ROI percentage
 * 
 * @param float $profit Total profit/loss
 * @param float $staked Total staked amount
 * @return float ROI as percentage
 */
public function calculateROI($profit, $staked)
{
    if ($staked === 0) return 0;
    return round(($profit / $staked) * 100, 2);
}
```

### JavaScript
- Use camelCase for variables and functions
- Add comments for complex logic
- Use const/let, avoid var
- Keep functions small and reusable

```javascript
// Calculate potential return from odds and stake
function calculatePotentialReturn(odds, stake) {
    return (odds * stake).toFixed(2);
}
```

### CSS
- Use CSS variables for colors and spacing
- Mobile-first approach
- Use semantic class names
- Avoid inline styles

## Testing

- Test all features before submitting
- Test on multiple browsers
- Test responsive design on mobile
- Test database operations
- Verify calculations accuracy

## Database Changes

If modifying the schema:
1. Create a migration with `php database/migrate.php make short_description`
2. Put the change in it (migrations only go forward; undo with a new migration)
3. Make the same change in schema.sql and add the version to its `schema_migrations` INSERT
4. Document changes in comments
5. Test on a fresh database and on an existing one

See [database/MIGRATIONS.md](database/MIGRATIONS.md) for the details.

## Commit Messages

Use clear, descriptive commit messages:

```
Add: New feature description
Fix: Bug fix description
Update: Update to existing code
Docs: Documentation changes
Style: Code style/formatting changes
Refactor: Code refactoring
Perf: Performance improvements
```

## Pull Request Process

1. Update README if needed
2. Update CHANGELOG with description
3. Ensure code passes validation
4. Reference any related issues
5. Provide clear description of changes
6. Be responsive to feedback

## Reporting Bugs

Include:
- Clear description of the bug
- Steps to reproduce
- Expected behavior
- Actual behavior
- Browser/environment info
- Screenshots if applicable

## Feature Requests

Describe:
- The feature purpose
- Expected behavior
- Use case/benefit
- Possible implementation approach

## Questions?

Open an issue or discussion for questions about contributing.

Thank you for contributing to BetLedger!
