# Auth Module

A drop-in authentication module for Laravel with email/password authentication and password reset functionality.

## Features

- ✅ User registration with email and password
- ✅ Email verification on registration (async/queued)
- ✅ User login with email and password
- ✅ Password reset (forgot password) flow (async/queued)
- ✅ Password reset confirmation email with account lock option (async/queued)
- ✅ Account locking feature for unauthorized password resets
- ✅ Token-based authentication using Laravel Sanctum
- ✅ Fully configurable
- ✅ Easy to integrate into any Laravel project
- ✅ All emails sent asynchronously via queues for better performance

## Installation

### Option 1: As a Local Package (Development)

1. Add the package to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./src/AuthModule"
        }
    ],
    "require": {
        "periscope/auth-module": "@dev"
    }
}
```

2. Run `composer require periscope/auth-module`

### Option 2: As a Git Repository

1. Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yourusername/auth-module.git"
        }
    ],
    "require": {
        "periscope/auth-module": "dev-main"
    }
}
```

2. Run `composer require periscope/auth-module`

## Setup

### 1. Publish Configuration

```bash
php artisan vendor:publish --tag=auth-module-config
```

This will create `config/auth-module.php` in your project.

### 2. Publish Migrations (Optional)

If you need the Sanctum migrations:

```bash
php artisan vendor:publish --tag=auth-module-migrations
php artisan migrate
```

### 3. Update Your User Model

Add the required traits to your `User` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Periscope\AuthModule\Models\Concerns\HasPasswordReset;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPasswordReset;

    // ... rest of your model
}
```

### 4. Configure Environment Variables

Add to your `.env` file:

```env
FRONTEND_URL=http://localhost:3000
AUTH_MODULE_USER_MODEL=App\Models\User
AUTH_MODULE_ROUTE_PREFIX=api
```

## API Endpoints

All endpoints are prefixed with the configured route prefix (default: `api`).

### Public Endpoints

- `POST /api/register` - Register a new user
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

- `POST /api/login` - Login
  ```json
  {
    "email": "john@example.com",
    "password": "password123"
  }
  ```

- `POST /api/forgot-password` - Request password reset
  ```json
  {
    "email": "john@example.com"
  }
  ```

- `POST /api/reset-password` - Reset password
  ```json
  {
    "email": "john@example.com",
    "token": "reset-token-from-email",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }
  ```
  After successful reset, user receives email asking to confirm if they initiated the reset.

- `POST /api/lock-account` - Lock account if password reset was unauthorized
  ```json
  {
    "id": "1",
    "token": "lock-token-from-email",
    "expires": "1234567890",
    "signature": "xyz789..."
  }
  ```
  Note: These parameters come from the lock account link in the email.

- `POST /api/verify-email` - Verify email address (from email link)
  ```json
  {
    "id": "1",
    "hash": "abc123...",
    "expires": "1234567890",
    "signature": "xyz789..."
  }
  ```
  Note: These parameters come from the verification link in the email.

### Protected Endpoints (Require Bearer Token)

- `POST /api/logout` - Logout
  - Headers: `Authorization: Bearer {token}`

- `GET /api/me` - Get current user
  - Headers: `Authorization: Bearer {token}`

- `POST /api/resend-verification-email` - Resend verification email
  - Headers: `Authorization: Bearer {token}`

## Configuration

Edit `config/auth-module.php` to customize:

- `user_model`: The User model class (default: `App\Models\User`)
- `frontend_url`: Frontend URL for password reset links
- `route_prefix`: API route prefix (default: `api`)
- `route_middleware`: Middleware for routes (default: `['api']`)

## Queue Configuration

**All emails (verification and password reset) are sent asynchronously via Laravel's queue system** for better performance. Make sure you have:

1. **Queue connection configured** in `.env`:
   ```env
   QUEUE_CONNECTION=database
   # or
   QUEUE_CONNECTION=redis
   ```

2. **Queue tables migrated** (if using database driver):
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

3. **Queue worker running**:
   ```bash
   php artisan queue:work
   # or for development
   php artisan queue:listen
   ```

For production, use a process manager like Supervisor to keep the queue worker running.

## Requirements

- PHP ^8.2
- Laravel ^12.0
- Laravel Sanctum ^4.2
- Queue worker running (for password reset emails)

## License

MIT
