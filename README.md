# Periscope Server

Laravel API server with authentication module.

> **Cursor / AI agents:** Read [`.cursor/rules/project-standards.mdc`](.cursor/rules/project-standards.mdc) before making changes. It defines SOLID, contracts, repository pattern, security (no PII in logs), performance, response structure, and mobile-first API standards.

## Features

- ✅ User registration with phone number (passwordless)
- ✅ Phone verification via SMS (async/queued)
- ✅ Passwordless login with OTP codes
- ✅ Token-based authentication (Laravel Sanctum)
- ✅ Configurable SMS providers (Twilio or AWS SNS)
- ✅ Enum-based API responses for type safety
- ✅ Rate limiting on sensitive endpoints
- ✅ Fully async SMS processing via queues

## Quick Start

### Prerequisites

- PHP ^8.2
- Composer
- MySQL (or SQLite for local development)

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

- **Collection:** `docs/postman/Periscope.postman_collection.json`

Optional: create a Postman environment with `base_url` (e.g. `http://localhost:8000`), `phone`, `login_code`, `verification_code`, and `auth_token` (set automatically after Register or Login - Verify OTP).

### Environment Setup for Testing

Update your `.env` file:
```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:8000
QUEUE_CONNECTION=sync

# AWS SNS Configuration for SMS
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_SNS_REGION=us-east-1

# Local Development: Leave AWS credentials empty to enable log-only mode
# SMS messages will be logged to storage/logs/laravel.log
```

**Local Testing:** When `APP_ENV=local` and SMS credentials are not set, SMS messages will be logged to `storage/logs/laravel.log` instead of being sent.

## API Endpoints

All endpoints return enum-based responses with a `status` field.

### Authentication
- `POST /api/register` - Register new user with phone number
- `POST /api/login` - Send OTP code to phone (passwordless login)
- `POST /api/verify-login` - Verify OTP code and complete login
- `POST /api/logout` - Logout (requires auth)
- `GET /api/me` - Get current user (requires auth)

### Phone Verification
- `POST /api/verify-phone` - Verify phone number with OTP code
- `POST /api/resend-verification-sms` - Resend verification code (requires auth)

### Health Check
- `GET /api/health` - Server health check

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

## Deployment

The application is configured for AWS Elastic Beanstalk deployment with:
- Automatic .env configuration
- Queue worker managed by systemd (Amazon Linux 2023 compatible)
- RDS database integration
- SQS queue processing
- SMS delivery via AWS SNS

### Queue Worker Setup

The queue worker runs as a systemd service that:
- ✅ Auto-starts on boot
- ✅ Auto-restarts on crash (5 second delay)
- ✅ Handles graceful shutdown for long-running jobs (3600s timeout)
- ✅ Logs to `/var/app/current/storage/logs/queue-worker.log`

**After deployment, verify the worker is running:**

SSH into your EC2 instance and run:

```bash
# Check service status
sudo systemctl status laravel-worker.service

# Check process
ps aux | grep "artisan queue:work"

# View logs
tail -f /var/app/current/storage/logs/queue-worker.log

# View systemd journal
journalctl -u laravel-worker.service -f

# Manual control (if needed)
sudo systemctl restart laravel-worker.service
sudo systemctl stop laravel-worker.service
sudo systemctl start laravel-worker.service
```

**Troubleshooting:**

If jobs fail with path errors:
```bash
# Check working directory is correct
sudo systemctl status laravel-worker.service | grep WorkingDirectory

# Should show: WorkingDirectory=/var/app/current
# NOT /var/app/staging
```

If logs show permission errors:
```bash
# Fix permissions
sudo mkdir -p /var/app/current/storage/logs
sudo chown -R webapp:webapp /var/app/current/storage
sudo chmod -R 775 /var/app/current/storage
sudo systemctl restart laravel-worker.service
```

### Configuration Files

- `.ebextensions/07-queue-worker.config` - EB configuration for log tailing and permissions
- `.platform/hooks/postdeploy/02_laravel_queue_worker.sh` - Creates and starts systemd service
- `.platform/hooks/postdeploy/99_verify_queue_worker.sh` - Post-deploy verification script

## License

MIT
