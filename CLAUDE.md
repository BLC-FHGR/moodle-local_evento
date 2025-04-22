# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build/Test Commands
- Run all PHPUnit tests: `vendor/bin/phpunit local/evento/tests`
- Run a specific test: `vendor/bin/phpunit local/evento/tests/evento_service_test.php`
- Run a specific test method: `vendor/bin/phpunit --filter test_init_call local/evento/tests/evento_service_test.php`
- Run code linting: `php -l file.php`
- Check code style: `vendor/bin/phpcs --standard=moodle local/evento`

## Code Style Guidelines
- Follow Moodle coding style: https://docs.moodle.org/dev/Coding_style
- Namespaces: Use `namespace local_evento\{component}` for all new classes
- Class naming: Use snake_case for legacy classes (local_evento_*) and PascalCase for namespaced
- Method naming: Use camelCase for methods (e.g., getEventById)
- Parameters/returns: Use PHP type declarations and nullable types (PHP 7.1+)
- DocBlocks: Required for all classes and methods with @package, @copyright, @license tags
- Error handling: Use try/catch with detailed logging and moodle_exception for user messages
- OOP: Follow interface segregation; implement interfaces for dependency injection
- Testing: Write PHPUnit tests for all public methods using advanced_testcase base class
- Architecture: Use dependency injection; separate interfaces from implementations