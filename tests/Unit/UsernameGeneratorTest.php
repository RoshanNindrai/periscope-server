<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use Periscope\AuthModule\Exceptions\AuthModuleException;
use Periscope\AuthModule\Support\UsernameGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UsernameGeneratorTest extends TestCase
{
    private UsernameGenerator $generator;
    private UserRepositoryInterface&MockObject $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new UsernameGenerator();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
    }

    public function test_generates_username_from_simple_name(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('John Doe', $this->userRepository);

        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
        $this->assertLessThanOrEqual(30, strlen($username));
    }

    public function test_generates_username_from_name_with_multiple_words(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('Mary Jane Watson', $this->userRepository);

        $this->assertMatchesRegularExpression('/^mary\.jane\.watson\.\d{4}$/', $username);
    }

    public function test_sanitizes_special_characters(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('Test User!', $this->userRepository);

        // Special characters should be removed
        $this->assertMatchesRegularExpression('/^test\.user\.\d{4}$/', $username);
    }

    public function test_handles_whitespace_only_name(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('   ', $this->userRepository);

        // Should fallback to "user" prefix
        $this->assertMatchesRegularExpression('/^user\.\d{4}$/', $username);
    }

    public function test_handles_name_with_only_special_characters(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('!@#$%', $this->userRepository);

        // Should fallback to "user" prefix
        $this->assertMatchesRegularExpression('/^user\.\d{4}$/', $username);
    }

    public function test_truncates_long_names(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $longName = 'Very Long Name That Exceeds The Maximum Length Limit For Username Base';
        $username = $this->generator->generateFromName($longName, $this->userRepository);

        // Should be truncated to fit within 30 chars (25 base + 5 for dot + 4 digits)
        $this->assertLessThanOrEqual(30, strlen($username));
        $this->assertMatchesRegularExpression('/^[a-z0-9.]+\.\d{4}$/', $username);
    }

    public function test_retries_when_username_exists(): void
    {
        $this->userRepository
            ->expects($this->exactly(2))
            ->method('existsByUsername')
            ->willReturnCallback(function (string $username) {
                // First attempt exists, second doesn't
                static $callCount = 0;
                $callCount++;
                return $callCount === 1;
            });

        $username = $this->generator->generateFromName('John Doe', $this->userRepository, 10);

        // Should eventually generate a unique username
        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
    }

    public function test_throws_exception_after_max_attempts(): void
    {
        $this->userRepository
            ->expects($this->exactly(10))
            ->method('existsByUsername')
            ->willReturn(true); // Always exists

        $this->expectException(AuthModuleException::class);

        $this->generator->generateFromName('John Doe', $this->userRepository, 10);
    }

    public function test_removes_consecutive_dots(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('John  Doe', $this->userRepository);

        // Should not have consecutive dots
        $this->assertStringNotContainsString('..', $username);
        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
    }

    public function test_removes_leading_and_trailing_dots(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('  John Doe  ', $this->userRepository);

        // Should not start or end with dot (except before the number)
        $this->assertFalse(str_starts_with($username, '.'), 'Username should not start with a dot');
        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
    }

    public function test_handles_single_word_name(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('John', $this->userRepository);

        $this->assertMatchesRegularExpression('/^john\.\d{4}$/', $username);
    }

    public function test_lowercases_all_characters(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('JOHN DOE', $this->userRepository);

        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
    }

    public function test_handles_name_with_numbers(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('John Doe 123', $this->userRepository);

        // Numbers in name should be preserved
        $this->assertMatchesRegularExpression('/^john\.doe\.123\.\d{4}$/', $username);
    }

    public function test_handles_underscores_in_name(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('existsByUsername')
            ->willReturn(false);

        $username = $this->generator->generateFromName('John_Doe', $this->userRepository);

        // Underscores should be preserved
        $this->assertMatchesRegularExpression('/^john_doe\.\d{4}$/', $username);
    }
}
