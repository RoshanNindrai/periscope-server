# API Documentation

## Postman Collection

This folder contains Postman collection files for testing the API.

### Files

- **Periscope-Auth-API.postman_collection.json** - Complete API collection with all endpoints and test scripts
- **Periscope-Auth-Environment.postman_environment.json** - Environment variables for testing

Both files are in this `docs/postman/` directory.

### Import Instructions

1. Open Postman
2. Click **Import**
3. Select both JSON files
4. Select the environment from the dropdown (top right)
5. Update `base_url` if your server runs on a different port

### Testing

All requests include automated test scripts that validate:
- HTTP status codes
- Response structure (`status` field)
- Enum values
- Auto-saving of tokens and user IDs

### Environment Variables

The collection automatically manages:
- `auth_token` - Authentication token (auto-saved after login/register)
- `user_id` - Current user ID (auto-saved)
- `email` - Test email address

For password reset and email verification flows, you'll need to extract tokens from `storage/logs/laravel.log` (when `MAIL_MAILER=log`).
