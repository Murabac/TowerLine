<?php

namespace Tests\Unit;

use App\Support\OperatorPalette;
use PHPUnit\Framework\TestCase;

class OperatorPaletteTest extends TestCase
{
    public function test_named_demo_operators_use_unique_color_blind_safe_colors(): void
    {
        $names = ['Telesom', 'Somtel', 'Sogasho', 'Truecable', 'Astaan', 'Horncable'];
        $colors = array_map(fn (string $name) => OperatorPalette::forName($name), $names);

        $this->assertCount(6, array_unique($colors));

        foreach ($colors as $color) {
            $this->assertContains($color, array_map(
                fn (string $swatch) => OperatorPalette::normalize($swatch),
                OperatorPalette::COLORS,
            ));
        }
    }

    public function test_next_available_skips_colors_already_in_use(): void
    {
        $first = OperatorPalette::COLORS[0];
        $second = OperatorPalette::COLORS[1];

        $this->assertSame(
            OperatorPalette::normalize($second),
            OperatorPalette::nextAvailable([$first]),
        );
    }

    public function test_unique_colors_for_keeps_named_swatches_and_fills_the_rest(): void
    {
        $assigned = OperatorPalette::uniqueColorsFor([
            ['id' => 1, 'name' => 'Telesom'],
            ['id' => 2, 'name' => 'Custom FM'],
            ['id' => 3, 'name' => 'Somtel'],
        ]);

        $this->assertSame(OperatorPalette::forName('Telesom'), $assigned[1]);
        $this->assertSame(OperatorPalette::forName('Somtel'), $assigned[3]);
        $this->assertNotSame($assigned[1], $assigned[2]);
        $this->assertNotSame($assigned[2], $assigned[3]);
        $this->assertCount(3, array_unique(array_values($assigned)));
    }
}
