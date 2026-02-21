<?php

namespace Tests\Unit;

use App\Livewire\Chat;
use PHPUnit\Framework\TestCase;

class EssayCorrectionParserTest extends TestCase
{
    public function test_parses_spelling_grammar_and_corrected_sections(): void
    {
        $response = <<<TEXT
1) Spelling mistakes: speling -> spelling
2) Grammar mistakes: He go -> He goes
3) Corrected version: This is the corrected essay text.
It has multiple lines.
TEXT;

        $parts = Chat::parseEssayCorrection($response);

        $this->assertSame('speling -> spelling', $parts['spelling_mistakes']);
        $this->assertSame('He go -> He goes', $parts['grammar_mistakes']);
        $this->assertSame("This is the corrected essay text.\nIt has multiple lines.", $parts['corrected_version']);
    }

    public function test_returns_null_sections_when_pattern_missing(): void
    {
        $response = "No structured response here.";

        $parts = Chat::parseEssayCorrection($response);

        $this->assertNull($parts['spelling_mistakes']);
        $this->assertNull($parts['grammar_mistakes']);
        $this->assertNull($parts['corrected_version']);
    }
}
