# Testing Evento Plugin

This directory contains tests for the Evento plugin. We use PHP-VCR for testing the SOAP API integration to ensure tests are deterministic and don't require a live connection to the Evento service.

## Test Directory Structure

- `fixtures/cassettes/` - YAML cassettes recorded by PHP-VCR for each test scenario
- `integration/` - Integration tests that test the full flow with VCR
- `mock/` - Mock objects for unit testing
- `unit/` - Unit tests for individual components
- `bootstrap.php` - PHP-VCR configuration 
- `phpunit.xml` - PHPUnit configuration

## Running Tests

### Standard Test Run (Cassette Replay)

To run tests using the pre-recorded cassettes (no network access):

```bash
vendor/bin/phpunit -c local/evento/tests/phpunit.xml
```

### Recording New Cassettes

Set environment credentials (replace with actual values):

```bash
export EVENTO_USERNAME=your_username
export EVENTO_PASSWORD=your_password
```

Then run tests with recording enabled:

```bash
VCR_MODE=record vendor/bin/phpunit --filter test_get_events
```

This will create or update the cassette for the specified test.

### Verifying No Network Access

To ensure no tests are hitting the real network:

```bash
VCR_MODE=none vendor/bin/phpunit -c local/evento/tests/phpunit.xml
```

This will fail if any test tries to make a real HTTP request.

## Test Types

### Unit Tests

Located in the `unit/` directory, these test individual components in isolation using mock objects.

### Integration Tests with VCR

Located in the `integration/` directory, these test the full flow of API calls using VCR-recorded cassettes.

## Cassette Management

### Cassette Naming

- `repository_get_events.yml` - Repository getEvents method
- `repository_get_enrollments.yml` - Repository getEnrollments method
- `repository_get_users.yml` - Repository getUsers method
- `repository_get_organizational_units.yml` - Repository getOrganizationalUnits method
- `evento_auth_failure.yml` - Authentication failure scenario

### Data Privacy

The cassettes are filtered to remove sensitive information:
- Credentials in requests are replaced with `[FILTERED]`
- Personal data is anonymized in both requests and responses

### When to Re-record

Cassettes should be re-recorded when:
1. The Evento API version changes
2. Test behavior is modified and requires different API responses
3. Cassette data becomes outdated or incompatible

## Troubleshooting

### Common Issues

- **Class 'VCR\VCR' not found**: Check composer autoloader is properly included
- **Unexpected HTTP request**: A test is missing the @vcr annotation or the cassette wasn't found
- **Failed recording**: Check credentials and network connectivity

### Tips

- Always commit cassettes to version control
- Review the recorded cassettes to ensure sensitive data is properly filtered
- Use the `--filter` option with PHPUnit to record one specific test at a time