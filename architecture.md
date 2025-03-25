# Architecture Documentation for Local Evento Plugin

## Overview

The Local Evento plugin has been refactored to enhance SOAP request handling and partial data sync capabilities. It continues to integrate Moodle with an external Evento system via SOAP for synchronization of events, courses, users, and enrollment data.

## Core Components

### 1. Evento Service (`local_evento_evento_service`)

The central component remains `local_evento_evento_service`. New helper classes have been introduced to handle data transformations, security checks, and to further decouple SOAP call logic from business rules.

#### Key Responsibilities:
- Establishing and maintaining SOAP connections
- Executing SOAP requests with error handling and retry logic
- Converting data formats between systems
- Providing methods to retrieve various types of data:
  - Events (by ID or number)
  - Enrollments
  - User accounts (students, lecturers, employees)
  - Person information
  - Organizational units (Veranstalter)

#### Notable Methods:
- `execute_soap_request()`: Core method for making SOAP calls with error handling
- `get_event_by_id()`, `get_event_by_number()`: Retrieve event information
- `get_enrolments_by_eventid()`: Get enrollment data for an event
- `get_all_ad_accounts()`: Retrieve all user accounts from Evento
- `get_person_by_id()`: Get person details by ID
- `process_in_batches()`: Process large datasets in manageable chunks
- `sid_to_shibbolethid()`, `shibbolethid_to_sid()`: Convert between ID formats

### 2. Configuration System

The plugin uses Moodle's configuration system to store connection parameters and operational settings.

#### Key Settings:
- SOAP endpoint location (`wslocation`)
- SOAP URI (`wsuri`)
- Debug tracing options (`wstrace`)
- ID format conversion parameters (`adsidprefix`, `adshibbolethsuffix`)

### 3. Logging and Tracing

The plugin implements comprehensive logging and performance monitoring:

- Uses Moodle's `progress_trace` system for operation tracking
- Records detailed metrics for SOAP operations (duration, attempts)
- Captures and logs SOAP errors with context information
- Provides debugging information for troubleshooting

## Data Flow

1. **Initialization**: The service establishes a connection to the Evento SOAP API
2. **Data Retrieval**: Various methods request specific data from Evento
3. **Processing**: Retrieved data is processed, transformed, and validated
4. **Batch Handling**: Large datasets are processed in batches to manage memory usage
5. **Error Handling**: SOAP errors are caught, logged, and handled appropriately

## Integration Points

- **Moodle User System**: Maps Evento accounts to Moodle users
- **Moodle Course System**: Maps Evento events to Moodle courses
- **Moodle Enrollment System**: Synchronizes enrollment status between systems
- **Authentication**: Converts between Evento SIDs and Shibboleth IDs for authentication

## Error Handling Strategy

The plugin implements a robust error handling approach:
- Automatic retry for transient errors
- Detailed error logging with context information
- Graceful degradation when services are unavailable
- Performance monitoring to detect slow operations

## Testing Framework

A comprehensive test suite validates the functionality of the service:
- Mock SOAP client for testing without external dependencies
- Test cases for all major service methods
- Validation of error handling and edge cases
- Verification of data transformation logic

## Performance Considerations

- Batch processing for large datasets
- Performance metrics logging
- Configurable retry logic for handling transient errors
- Efficient data transformation

## Security Aspects

- Secure storage of connection credentials
- Proper error handling to prevent information leakage
- Validation of input and output data
- Controlled access to sensitive operations

## Configuration Requirements

To use this plugin, the following must be configured:
- Evento SOAP API endpoint details
- Authentication parameters
- ID conversion settings
- Optional debugging and logging settings

## Dependency Diagram

```
┌─────────────────────────────────────┐
│         Moodle Core System          │
└───────────────────┬─────────────────┘
                    │
┌───────────────────▼─────────────────┐
│       Local Evento Plugin           │
│                                     │
│  ┌─────────────────────────────┐    │
│  │  local_evento_evento_service│    │
│  └─────────────┬───────────────┘    │
│                │                     │
│  ┌─────────────▼───────────────┐    │
│  │        SoapClient           │    │
│  └─────────────────────────────┘    │
└───────────────────┬─────────────────┘
                    │
┌───────────────────▼─────────────────┐
│         Evento SOAP API             │
└─────────────────────────────────────┘
```

## Future Considerations

- Potential for REST API support if Evento provides it
- Caching strategies for frequently accessed data
- Enhanced monitoring and reporting capabilities
- Automated synchronization scheduling
- Additional security audits for system boundaries
- More advanced batch scheduling via Moodle's task system
**Clarifying Questions**  
1. Which new helper classes should be emphasized for advanced troubleshooting?  
2. Are there any new configuration or environment variables we need to highlight for partial sync?  
3. Should we include usage examples or references for external plugin architecture integration?  
