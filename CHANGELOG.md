# Changelog

All notable changes to `grok-php-client` will be documented in this file.

## v1.3.0 - 2025-03-10

#### Added

- **Laravel 11+ Integration** as a first-class framework implementation
- Laravel Service Provider and Facade for seamless integration
- `grok:install` Artisan command for configuration setup
- Automatic environment configuration (.env) management
- Publishable configuration file with base URL customization
- Dependency injection support via Laravel's service container
- Framework-specific testing scaffolding

#### Fixed

- Complete test suite overhaul with proper API mocking
- Resolved all environment variable handling issues
- Fixed streaming callback type declarations
- Addressed enum serialization/deserialization errors
- Corrected image URL validation logic
- Fixed parameter validation ranges across all endpoints
- Resolved 40+ test errors from previous implementation

#### Changed

- **BC Break:** Configuration structure now separates core and Laravel-specific options
- Updated CI/CD pipeline to handle framework-specific tests
- Improved error messages for API key validation
- Refactored HTTP client handling for better test isolation

*Upgrade Note: Existing users should run `php artisan grok:install` after upgrading for new configuration format.*

## v1.2.0 - 2025-02-06

### Added

- **Structured Output** support with JSON schema validation
- `DataModel` base class for PHP attribute-based schema definitions
- `SchemaProperty` attribute for declarative field configuration
- `generateStructured()` method in Chat endpoint for schema-constrained responses
- Automatic response hydration into PHP data objects

### Changes/Fixes

- Enhanced type safety for model parameters using PHP enums
- Updated embedding handling to support new structured format
- Improved parameter validation ranges for API compliance
- Extended documentation for structured output usage patterns

*Implements official xAI structured output specification from [API docs](https://docs.x.ai/docs/guides/structured-outputs)*

## v1.1.0 - 2025-02-03

### Added

- Embedding feature for handling embedding
- New methods in ChatMessage for better message handling.
- New Params class for a more robust way of handling parameters - included with proper validations

### Changes/Fixes

- Updated GrokClient to automatically load the .env file and retrieve the API key internally.
- Updated ResponseParser to include embedding response type.
- Updated Model selection feature for easier usage along with strict validations
- Refactored ChatCompletion and GrokClient to utilize Model enum.
- Refactored other files that made use of model selection and parameter specification to align with new changes.
- Updated test suites
- Made minor bug fixes

*BREAKING CHANGE: Users no longer need to manually load the .env file and retrieve the API key before instantiating GrokClient.*

## v1.0.0 - 2025-01-27

Initial Release ✨
