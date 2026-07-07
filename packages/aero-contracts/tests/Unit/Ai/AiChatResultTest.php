<?php // packages/aero-contracts/tests/Unit/Ai/AiChatResultTest.php
declare(strict_types=1);

namespace Aero\Contracts\Tests\Unit\Ai;

use Aero\Contracts\Ai\AiChatResult;
use PHPUnit\Framework\TestCase;

class AiChatResultTest extends TestCase
{
    public function test_holds_a_successful_reply(): void
    {
        $r = new AiChatResult(content: 'hi', tokensUsed: 7, model: 'gemini-flash-latest');
        $this->assertTrue($r->success);
        $this->assertSame('hi', $r->content);
        $this->assertSame(7, $r->tokensUsed);
        $this->assertNull($r->error);
        $this->assertSame([], $r->toolCalls);
    }

    public function test_failed_factory_marks_unsuccessful(): void
    {
        $r = AiChatResult::failed('timeout', 'gemini-flash-latest');
        $this->assertFalse($r->success);
        $this->assertSame('timeout', $r->error);
        $this->assertSame('', $r->content);
    }
}
