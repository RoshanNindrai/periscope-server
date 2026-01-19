# Periscope Server

Laravel API server with authentication module.

## Features

- ✅ User registration with email/password
- ✅ Email verification (async/queued)
- ✅ User login with token-based authentication (Laravel Sanctum)
- ✅ Password reset flow with security notifications
- ✅ Account locking for unauthorized password resets
- ✅ Enum-based API responses for type safety
- ✅ Rate limiting on sensitive endpoints
- ✅ Fully async email processing via queues

## Quick Start

### Prerequisites

- PHP ^8.2
- Composer
- SQLite (or MySQL/PostgreSQL)

### Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy environment file:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Start the server:
   ```bash
   php artisan serve
   ```
7. Start queue worker (for async emails):
   ```bash
   php artisan queue:work
   ```

## Testing

### Postman Collection

Import the Postman collection for API testing:
- Collection: `docs/postman/Periscope-Auth-API.postman_collection.json`
- Environment: `docs/postman/Periscope-Auth-Environment.postman_environment.json`

See `docs/postman/README.md` for detailed import instructions.

### Environment Setup for Testing

Update your `.env` file:
```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:8000
MAIL_MAILER=log
QUEUE_CONNECTION=sync
```

Emails will be logged to `storage/logs/laravel.log` for testing.

## API Endpoints

All endpoints return enum-based responses with a `status` field.

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout (requires auth)
- `GET /api/me` - Get current user (requires auth)

### Password Reset
- `POST /api/forgot-password` - Request password reset
- `POST /api/reset-password` - Reset password with token
- `POST /api/lock-account` - Lock account if reset was unauthorized

### Email Verification
- `POST /api/verify-email` - Verify email address
- `POST /api/resend-verification-email` - Resend verification (requires auth)

## Response Format

**Success Response:**
```json
{
    "status": "LOGGED_IN",
    "message": "Login successful",
    "user": {...},
    "token": "..."
}
```

**Error Response:**
```json
{
    "status": "ACCOUNT_LOCKED",
    "error": "ACCOUNT_LOCKED",
    "message": "Your account has been locked. Please contact support."
}
```

## Package Structure

The authentication module is located in `src/AuthModule/` and can be used as a standalone Laravel package.

See `src/AuthModule/README.md` for package documentation.

## License

MIT
