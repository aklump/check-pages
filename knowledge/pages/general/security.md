# Security

## Overview

This document explains the purpose and usage of two specialized HTTP headers used in the Check Pages testing framework. These headers enable your application to identify and validate requests that originate from the Check Pages testing infrastructure.

## Testing Headers

### x-testing-token

The x-testing-token header serves as a validation mechanism to verify that a request is coming from the Check Pages testing framework.

#### Purpose

- **Framework Identification**: Confirms that a request originates from the Check Pages testing framework
- **Request Validation**: Helps the application distinguish between regular user traffic and test-driven traffic
- **Testing Mode Trigger**: Signals to the application that specific testing behaviors may be necessary

#### Implementation

- The token is completely arbitrary and can be any string value
- Both the Check Pages framework and the application must be configured with the same token value

### x-testing-framework

The x-testing-framework header provides information about the framework making the request.

#### Format

The header value follows this pattern:

``` 
Check Pages/0.23.4
```

This header is purely informational. The application may log this value or use it for debugging purposes, but its specific usage is entirely up to the implementation.

## Important Note

These headers are NOT authentication mechanisms. They do not provide security or access control. Their sole purpose is to allow the application to identify and validate requests coming from the Check Pages testing framework. These headers should not be confused with or used as a substitute for proper authentication headers.

## Usage

These headers are automatically included in HTTP requests made by Check Pages testing tools to the application:

```
GET /api/endpoint
Host: example.com
x-testing-token: your_arbitrary_token_here
x-testing-framework: Check Pages/0.23.4
```

## Application Configuration

The application must be configured to:

1. Recognize these headers
2. Validate the token against a list of allowed values

## Implementation Considerations

- Since these headers are only for framework identification and not security, the token value can be simple and consistent across environments
- The application code should check for the presence and validity of these headers when determining if a request comes from the Check Pages testing framework
- The behavior changes triggered by these headers should be limited to testing-specific adjustments and should not bypass any security measures
