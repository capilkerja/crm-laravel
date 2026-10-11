# Contributing

Thank you for your interest in contributing to Liberu CRM.

## Getting started

1. Fork the repository and create a branch from `main`.
2. Set up your development environment following the installation steps in the README.
3. Make your change with accompanying tests where behaviour changes.
4. Run the applicable checks before opening a pull request.

## Running checks

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
npm run build
```

Use focused tests while iterating:

```bash
php artisan test --filter=ContactTest
```

## Pull requests

- Keep changes focused on a single outcome.
- Explain the problem and approach in the PR description.
- Include tests or explain why tests do not apply.
- Document user-visible or breaking changes.
- Respond to review with fresh commits rather than force-pushing.

## Code style

- Keep business logic in actions and services.
- Enforce access through policies.
- Preserve tenant and team boundaries when adding models, routes, jobs, or Filament resources.

## Security

Do not report security vulnerabilities in public issues or pull requests. Follow [SECURITY.md](SECURITY.md) for private reporting.
