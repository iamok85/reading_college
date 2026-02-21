<?php

namespace Tests\Unit;

use App\Livewire\Chat;
use PHPUnit\Framework\TestCase;

class EssayCorrectionParserTest extends TestCase
{
    public function test_parses_spelling_grammar_and_corrected_sections(): void
    {
        $response = <<<TEXT
1) **Spelling mistakes:**  
- holidays → **Holidays** (capitalization)  
- vacaitoncara → Vacaitoncara (assuming proper noun; spelling unclear, possibly “Vacationcara”)  
- monday → Monday  
- castel → castle  
- rocol → rock wall (unclear word; corrected to most likely intended)  
- seperated → separated  
- castal → castle  
- whore → were  
- vacaitincana → Vacaitincana (possibly same as above; spelling unclear)  
- meseged → messaged  
- Bed wars → Bedwars  

2) **Grammar mistakes:**  
- "in the holidays at vacaitoncara" → "**During** the holidays at Vacaitoncara"  
- "at monday" → "On Monday"  
- "are jumping castel first" → "Our first activity was the jumping castle"  
- "it was rocol after rocol" → "After that, it was the rock wall"  
- "we had be seperated in groups" → "We were separated into groups"  
- "first I was playing handball" → "First, I was playing handball"  
- "when it was our... turn" → "When it was our turn"  
- "it was fun we whore playing tip" → "It was fun. We were playing tag"  
- "it was quick when it was finished" → "It finished quickly"  
- "The other holiday after vacaitincana" → "After the other holiday at Vacaitincana"  
- "we play Bedwars skywars" → "We played Bedwars and Skywars"  
- "Bed wars is are game" → "Bedwars is a game"  
- "that you have to break..." → Incomplete sentence  

3) **Corrected version:**  

During the holidays at Vacaitoncara, on Monday our first activity was the jumping castle. After that, we did the rock wall. We were separated into groups. First, I was playing handball. When it was our turn, I went on the jumping castle. It was **fun**. We were playing tag, and it finished quickly.  

After the other holiday at Vacaitincana, my friend messaged me, so we played Minecraft together. We played Bedwars and Skywars. Bedwars is a game where you have to break the other team’s bed.
TEXT;

        $parts = Chat::parseEssayCorrection($response);

        $this->assertSame(
            "holidays → Holidays (capitalization)\n"
            . "vacaitoncara → Vacaitoncara (assuming proper noun; spelling unclear, possibly “Vacationcara”)\n"
            . "monday → Monday\n"
            . "castel → castle\n"
            . "rocol → rock wall (unclear word; corrected to most likely intended)\n"
            . "seperated → separated\n"
            . "castal → castle\n"
            . "whore → were\n"
            . "vacaitincana → Vacaitincana (possibly same as above; spelling unclear)\n"
            . "meseged → messaged\n"
            . "Bed wars → Bedwars",
            $parts['spelling_mistakes']
        );
        $this->assertSame(
            "\"in the holidays at vacaitoncara\" → \"During the holidays at Vacaitoncara\"\n"
            . "\"at monday\" → \"On Monday\"\n"
            . "\"are jumping castel first\" → \"Our first activity was the jumping castle\"\n"
            . "\"it was rocol after rocol\" → \"After that, it was the rock wall\"\n"
            . "\"we had be seperated in groups\" → \"We were separated into groups\"\n"
            . "\"first I was playing handball\" → \"First, I was playing handball\"\n"
            . "\"when it was our... turn\" → \"When it was our turn\"\n"
            . "\"it was fun we whore playing tip\" → \"It was fun. We were playing tag\"\n"
            . "\"it was quick when it was finished\" → \"It finished quickly\"\n"
            . "\"The other holiday after vacaitincana\" → \"After the other holiday at Vacaitincana\"\n"
            . "\"we play Bedwars skywars\" → \"We played Bedwars and Skywars\"\n"
            . "\"Bed wars is are game\" → \"Bedwars is a game\"\n"
            . "\"that you have to break...\" → Incomplete sentence",
            $parts['grammar_mistakes']
        );
        $this->assertSame(
            "During the holidays at Vacaitoncara, on Monday our first activity was the jumping castle. After that, we did the rock wall. We were separated into groups. First, I was playing handball. When it was our turn, I went on the jumping castle. It was fun. We were playing tag, and it finished quickly.\n\n"
            . "After the other holiday at Vacaitincana, my friend messaged me, so we played Minecraft together. We played Bedwars and Skywars. Bedwars is a game where you have to break the other team’s bed.",
            $parts['corrected_version']
        );
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
