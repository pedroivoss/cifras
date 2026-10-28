<?php

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    public function test_paragraphs_lines_bold_and_italic(): void
    {
        $paragraphs = RichText::parse("Linha *um*\nLinha **dois**\n\n\n**- Graças a Deus.**");

        $this->assertCount(2, $paragraphs);
        $this->assertCount(2, $paragraphs[0]);
        $this->assertSame([['text' => 'Linha ', 'bold' => false, 'italic' => false], ['text' => 'um', 'bold' => false, 'italic' => true]], $paragraphs[0][0]);
        $this->assertSame([['text' => '- Graças a Deus.', 'bold' => true, 'italic' => false]], $paragraphs[1][0]);
    }

    public function test_html_is_escaped_and_plain_symbols_survive(): void
    {
        $html = (string) RichText::toHtml("<script>x</script> 2 * 3 = 6, nome_do_arquivo\n**ok**");

        $this->assertSame('<p>&lt;script&gt;x&lt;/script&gt; 2 * 3 = 6, nome_do_arquivo<br><strong>ok</strong></p>', $html);
        $this->assertSame([], RichText::parse("  \n "));
        $this->assertSame('Texto ok', RichText::plain("**Texto**\n\nok"));
    }
}
