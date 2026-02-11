<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;

class CrispApiExceptionTest extends TestCase
{
    public function test_can_be_instantiated_with_message(): void
    {
        $exception = new CrispApiException('API Error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('API Error', $exception->getMessage());
    }

    public function test_can_be_thrown(): void
    {
        $this->expectException(CrispApiException::class);
        $this->expectExceptionMessage('Test exception');

        throw new CrispApiException('Test exception');
    }
}
