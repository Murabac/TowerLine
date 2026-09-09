<?php

namespace App\Support;

class SimplePdf
{
    private const PAGE_W = 595.28;

    private const PAGE_H = 841.89;

    private const MARGIN = 56.0;

    private const LINE = 14.0;

    private const BOTTOM = 56.0;

    /** @var list<string> */
    private array $pageStreams = [];

    private string $current = '';

    private float $y;

    public function __construct()
    {
        $this->newPage();
    }

    public function title(string $text): void
    {
        $this->ensure(22);
        $this->writeLine($text, 16, true);
        $this->y -= 6;
    }

    public function heading(string $text): void
    {
        $this->ensure(28);
        $this->y -= 10;
        $this->writeLine($text, 12, true);
        $this->y -= 4;
    }

    public function paragraph(string $text): void
    {
        foreach ($this->wrap($text, 10) as $line) {
            $this->ensure(self::LINE);
            $this->writeLine($line, 10, false);
        }
        $this->y -= 6;
    }

    public function bullet(string $text): void
    {
        $lines = $this->wrap($text, 10, self::PAGE_W - (self::MARGIN * 2) - 18);
        foreach ($lines as $i => $line) {
            $this->ensure(self::LINE);
            $prefix = $i === 0 ? chr(149).' ' : '    ';
            $this->writeLine($prefix.$line, 10, false, self::MARGIN + 10);
        }
        $this->y -= 3;
    }

    public function output(): string
    {
        $this->flushPage();

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageIds = [];
        $contentIds = [];
        $nextId = 5;

        foreach ($this->pageStreams as $stream) {
            $pageIds[] = $nextId;
            $contentIds[] = $nextId + 1;
            $nextId += 2;
        }

        $kids = implode(' ', array_map(fn (int $id) => $id.' 0 R', $pageIds));
        $objects[] = '<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageIds).' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        foreach ($this->pageStreams as $i => $stream) {
            $contentId = $contentIds[$i];
            $objects[] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_W,
                self::PAGE_H,
                $contentId
            );
            $objects[] = '<< /Length '.strlen($stream).' >> stream'."\n".$stream."\n".'endstream';
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function newPage(): void
    {
        $this->current = '';
        $this->y = self::PAGE_H - self::MARGIN;
    }

    private function flushPage(): void
    {
        if ($this->current !== '') {
            $this->pageStreams[] = $this->current;
            $this->current = '';
        }
    }

    private function ensure(float $needed): void
    {
        if ($this->y - $needed < self::BOTTOM) {
            $this->flushPage();
            $this->newPage();
        }
    }

    private function writeLine(string $text, int $size, bool $bold, ?float $x = null): void
    {
        $font = $bold ? '/F2' : '/F1';
        $x ??= self::MARGIN;
        $this->current .= sprintf(
            "BT %s %d Tf %.2f %.2f Td (%s) Tj ET\n",
            $font,
            $size,
            $x,
            $this->y,
            $this->escape($text)
        );
        $this->y -= self::LINE + ($size > 12 ? 4 : 0);
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, int $size, ?float $width = null): array
    {
        $width ??= self::PAGE_W - (self::MARGIN * 2);
        $maxChars = max(20, (int) floor($width / ($size * 0.5)));
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current.' '.$word;

            if (strlen($trial) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $trial;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    private function escape(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        $text = $converted !== false ? $converted : $text;

        return strtr($text, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]);
    }
}
