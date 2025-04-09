# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build/Test Commands
- Run all PHPUnit tests: `vendor/bin/phpunit local/evento/tests`
- Run a specific test: `vendor/bin/phpunit local/evento/tests/evento_service_test.php`
- Run a specific test method: `vendor/bin/phpunit --filter test_init_call local/evento/tests/evento_service_test.php`
- Lint PHP files: `php -l file.php`

## Code Style Guidelines
- Follow Moodle coding style guidelines (https://docs.moodle.org/dev/Coding_style)
- Use PHP DocBlocks for all classes and methods
- Method names: use camelCase (e.g., getEventById)
- Class constants: use UPPER_SNAKE_CASE
- Private properties: use $lowercase with descriptive names
- Follow PSR-12 for code formatting where possible
- Error handling: use try/catch blocks and log errors with appropriate context
- Type hints: use PHP type declarations for parameters and return types
- Use Moodle's moodle_exception for error conditions with appropriate language strings