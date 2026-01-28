<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Support;

use App\Contracts\UserRepositoryInterface;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Periscope\AuthModule\Exceptions\AuthModuleException;

final class UsernameGenerator
{
    private const MAX_USERNAME_LENGTH = 30;
    private const BASE_LENGTH = 25; // Leave room for 4-digit number (5 chars: dot + 4 digits)
    private const MAX_ATTEMPTS = 10;
    private const FALLBACK_PREFIX = 'user';

    /**
     * Generate a unique username from a name.
     *
     * Algorithm:
     * 1. Trim whitespace
     * 2. Convert to lowercase
     * 3. Replace spaces with dots
     * 4. Sanitize to only allow a-z, 0-9, and dots
     * 5. Truncate to max 25 characters
     * 6. Append random 4-digit number
     * 7. Check uniqueness and retry if needed
     *
     * @param  string  $name  The full name to generate username from
     * @param  UserRepositoryInterface  $userRepository  Repository to check username existence
     * @param  int  $maxAttempts  Maximum number of attempts to generate unique username
     * @return string  A unique username
     *
     * @throws AuthModuleException  If unable to generate unique username after max attempts
     */
    public function generateFromName(
        string $name,
        UserRepositoryInterface $userRepository,
        int $maxAttempts = self::MAX_ATTEMPTS
    ): string {
        // Step 1-4: Normalize and sanitize the name
        $base = $this->normalizeName($name);

        // Step 5: Truncate if needed
        $base = $this->truncateBase($base);

        // Handle edge case: if base is empty after sanitization, use fallback
        if ($base === '') {
            $base = self::FALLBACK_PREFIX;
        }

        // Step 6-7: Generate unique username with retry logic
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $randomNumber = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $username = $base . '.' . $randomNumber;

            // Ensure total length doesn't exceed max (shouldn't happen, but safety check)
            if (strlen($username) > self::MAX_USERNAME_LENGTH) {
                $maxBaseLength = self::MAX_USERNAME_LENGTH - strlen($randomNumber) - 1; // -1 for the dot
                $username = substr($base, 0, $maxBaseLength) . '.' . $randomNumber;
            }

            // Check if username exists
            if (! $userRepository->existsByUsername($username)) {
                return $username;
            }
        }

        // All attempts failed
        throw new AuthModuleException(
            AuthErrorCode::REGISTRATION_FAILED
        );
    }

    /**
     * Normalize and sanitize the name.
     *
     * @param  string  $name  The input name
     * @return string  Normalized base string (lowercase, spaces to dots, sanitized)
     */
    private function normalizeName(string $name): string
    {
        // Trim whitespace
        $normalized = trim($name);

        // Convert to lowercase
        $normalized = strtolower($normalized);

        // Replace spaces with dots
        $normalized = str_replace(' ', '.', $normalized);

        // Remove any characters that don't match /^[a-z0-9._]+$/
        // Keep only lowercase letters, digits, dots, and underscores
        $normalized = preg_replace('/[^a-z0-9._]/', '', $normalized) ?? '';

        // Remove consecutive dots (e.g., "john..doe" -> "john.doe")
        $normalized = preg_replace('/\.{2,}/', '.', $normalized) ?? '';

        // Remove leading/trailing dots
        $normalized = trim($normalized, '.');

        return $normalized;
    }

    /**
     * Truncate the base string to fit within the maximum length.
     *
     * @param  string  $base  The base string to truncate
     * @return string  Truncated string (max 25 characters)
     */
    private function truncateBase(string $base): string
    {
        if (strlen($base) <= self::BASE_LENGTH) {
            return $base;
        }

        // Truncate to BASE_LENGTH
        $truncated = substr($base, 0, self::BASE_LENGTH);

        // If we cut in the middle of a word (not at a dot), try to truncate at the last dot
        // This keeps the username more readable
        $lastDot = strrpos($truncated, '.');
        if ($lastDot !== false && $lastDot > self::BASE_LENGTH - 10) {
            // If there's a dot near the end, truncate there instead
            $truncated = substr($truncated, 0, $lastDot);
        }

        // Remove trailing dot if present
        return rtrim($truncated, '.');
    }
}
