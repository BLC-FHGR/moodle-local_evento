# Service Layer Documentation

## Overview

The service layer in the Local Evento plugin provides a structured interface for interacting with the Evento system via SOAP web services. This document details how these services interact with each other, with Moodle, and with the external Evento system.

## Service Architecture

### Primary Service: `local_evento_evento_service`

This is the core service class that handles all direct communication with the Evento SOAP API. It provides a comprehensive set of methods for retrieving and processing data from Evento.

## Service Interactions

### 1. SOAP Client Interaction

```
┌─────────────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│local_evento_evento_service│ ──► │   SoapClient    │ ──► │   Evento API    │
└─────────────────────────┘      └─────────────────┘      └─────────────────┘
```

- **Initialization**: The service creates a SoapClient instance during construction
- **Request Execution**: All API calls are routed through the `execute_soap_request()` method
- **Error Handling**: SOAP faults are caught and processed by `handle_soap_error()`
- **Performance Monitoring**: Each request is timed and logged via `log_sync_operation()`

### 2. Data Retrieval Services

The service provides specialized methods for retrieving different types of data:

#### Event Data Services
```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│    Event Data Methods   │
├─────────────────────────┤
│  get_event_by_id()      │
│  get_event_by_number()  │
│  get_events_by_veranstalter() │
│  get_events_by_veranstalter_years() │
└─────────────────────────┘
```

#### User Account Services
```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│  User Account Methods   │
├─────────────────────────┤
│  get_all_ad_accounts()  │
│  get_student_ad_accounts() │
│  get_lecturer_ad_accounts() │
│  get_employee_ad_accounts() │
│  get_ad_accounts_by_evento_personid() │
└─────────────────────────┘
```

#### Person Data Services
```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│   Person Data Methods   │
├─────────────────────────┤
│  get_person_by_id()     │
└─────────────────────────┘
```

#### Enrollment Services
```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│   Enrollment Methods    │
├─────────────────────────┤
│get_enrolments_by_eventid()│
└─────────────────────────┘
```

#### Organizational Unit Services
```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│  Organization Methods   │
├─────────────────────────┤
│  get_active_veranstalter() │
└─────────────────────────┘
```

### 3. Utility Services

The service provides utility methods that support the main functionality:

```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│     Utility Methods     │
├─────────────────────────┤
│  sid_to_shibbolethid()  │
│  shibbolethid_to_sid()  │
│  to_array()             │
│  process_in_batches()   │
└─────────────────────────┘
```

### 4. Tracing and Logging Services

```
┌─────────────────────────┐
│local_evento_evento_service│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐      ┌─────────────────┐
│   Logging & Tracing     │ ──► │  progress_trace  │
├─────────────────────────┤      └─────────────────┘
│  set_trace()            │
│  log_sync_operation()   │
└─────────────────────────┘
```

## Service Dependencies

### Internal Dependencies

1. **Configuration Dependency**:
   - The service depends on Moodle's configuration system to retrieve connection parameters
   - Configuration is injected during construction or loaded from global settings

2. **Tracing Dependency**:
   - The service uses Moodle's `progress_trace` system for operation tracking
   - Trace objects can be injected during construction or set via `set_trace()`

3. **SOAP Client Dependency**:
   - The service requires a SoapClient instance for API communication
   - The client can be injected during construction (useful for testing) or created internally

### External Dependencies

1. **Evento SOAP API**:
   - The service depends on the availability of the Evento SOAP API
   - API endpoints are configured via `wslocation` and `wsuri` settings

## Service Interaction Patterns

### 1. Direct Method Calls

Most interactions with the service occur through direct method calls:

```php
$service = new local_evento_evento_service();
$event = $service->get_event_by_id(123);
```

### 2. Batch Processing Pattern

For large datasets, the service uses a batch processing pattern:

```
┌─────────────────────────┐
│       Client Code       │
└───────────┬─────────────┘
            │ call
            ▼
┌─────────────────────────┐
│  get_all_ad_accounts()  │
└───────────┬─────────────┘
            │ calls
            ▼
┌─────────────────────────┐
│ get_student_ad_accounts()│
│ get_lecturer_ad_accounts()│
│ get_employee_ad_accounts()│
└───────────┬─────────────┘
            │ processes results
            ▼
┌─────────────────────────┐
│  process_in_batches()   │
└─────────────────────────┘
```

### 3. Error Handling Pattern

The service implements a consistent error handling pattern:

```
┌─────────────────────────┐
│  execute_soap_request() │
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│      try/catch block    │
└───────────┬─────────────┘
            │ on error
            ▼
┌─────────────────────────┐
│   handle_soap_error()   │
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│    log error details    │
└─────────────────────────┘
```

## Service Configuration

The service is configured through Moodle's configuration system:

```
┌─────────────────────────┐      ┌─────────────────┐
│local_evento_evento_service│ ◄── │  get_config()   │
└─────────────────────────┘      └─────────────────┘
```

Key configuration parameters include:
- `wslocation`: SOAP endpoint URL
- `wsuri`: SOAP service URI
- `wstrace`: Enable/disable detailed tracing
- `adsidprefix`: Prefix for AD SIDs
- `adshibbolethsuffix`: Suffix for Shibboleth IDs

## Testing Interactions

The service is designed to be testable through dependency injection:

```
┌─────────────────────────┐      ┌─────────────────┐
│     Test Framework      │ ──► │  Mock SoapClient │
└───────────┬─────────────┘      └────────┬────────┘
            │                             │
            ▼                             ▼
┌─────────────────────────┐      ┌─────────────────┐
│local_evento_evento_service│ ◄── │   Mock Config   │
└─────────────────────────┘      └─────────────────┘
```

## Integration with Moodle

The service integrates with Moodle through these primary touchpoints:

1. **User Integration**:
   - AD accounts from Evento map to Moodle user accounts
   - SID/Shibboleth ID conversion supports authentication

2. **Course Integration**:
   - Evento events map to Moodle courses
   - Event metadata provides course information

3. **Enrollment Integration**:
   - Evento enrollments map to Moodle course enrollments
   - Enrollment status synchronization

## Performance Considerations

The service implements several performance optimizations:

1. **Batch Processing**:
   - Large datasets are processed in configurable batches
   - Memory usage is controlled through chunking

2. **Performance Monitoring**:
   - All SOAP operations are timed and logged
   - Performance metrics help identify bottlenecks

3. **Retry Logic**:
   - Transient errors trigger automatic retries
   - Prevents unnecessary failures due to network issues

## Security Considerations

The service implements security best practices:

1. **Error Handling**:
   - Detailed errors are logged but not exposed to users
   - Prevents information leakage

2. **Input Validation**:
   - Parameters are validated before use in SOAP calls
   - Prevents injection attacks

3. **Authentication**:
   - Secure handling of identity information
   - Proper conversion between ID formats

## Conclusion

The service layer in the Local Evento plugin provides a comprehensive and structured approach to interacting with the Evento system. By centralizing all SOAP communication through a single service class with well-defined methods, the plugin ensures consistent error handling, performance monitoring, and data processing throughout the application.
