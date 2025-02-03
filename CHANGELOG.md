# Changelog

All notable changes to `php-grok-ai` will be documented in this file.

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
