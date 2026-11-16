<?php

final class SimplePdf
{
    private array $pages = [];
    private array $current = [];
    private float $y = 780.0;

    public function __construct(private string $title = 'Lanka Renters Report')
    {
        $this->addPage();
    }

    public function addPage(): void
    {
        if ($this->current !== []) {
            $this->pages[] = $this->current;
        }
        $this->current = [];
        $this->y = 780.0;
        $this->text($this->title, 36, $this->y, 16);
        $this->line(36, $this->y - 8, 559, $this->y - 8);
        $this->y -= 28;
    }

    public function heading(string $value): void
    {
        $this->ensureSpace(34);
        $this->text($value, 36, $this->y, 12);
        $this->y -= 20;
    }

    public function text(string $value, float $x = 36, ?float $y = null, int $size = 10): void
    {
        $this->current[] = ['type' => 'text', 'value' => $this->safe($value), 'x' => $x, 'y' => $y ?? $this->y, 'size' => $size];
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->current[] = ['type' => 'line', 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2];
    }

    public function row(array $cells, array $widths, float $height = 18): void
    {
        $this->ensureSpace($height + 8);
        $x = 36.0;
        foreach ($cells as $index => $cell) {
            $width = (float)($widths[$index] ?? 80);
            $this->text($this->truncate((string)$cell, max(8, (int)($width / 5))), $x + 4, $this->y, 8);
            $x += $width;
        }
        $this->line(36, $this->y - 5, 559, $this->y - 5);
        $this->y -= $height;
    }

    public function spacer(float $height = 12): void
    {
        $this->y -= $height;
    }

    public function output(): string
    {
        if ($this->current !== []) {
            $this->pages[] = $this->current;
            $this->current = [];
        }
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $pageObject = 3;
        $contentObject = 4 + (count($this->pages) * 2);
        foreach ($this->pages as $page) {
            $kids[] = $pageObject . ' 0 R';
            $objects[$pageObject - 1] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . ($contentObject + 1) . ' 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
            $stream = $this->stream($page);
            $objects[$contentObject - 1] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $objects[$contentObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
            $pageObject += 2;
            $contentObject += 2;
        }
        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($this->pages) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $body) {
            $objectNumber = $number + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function stream(array $commands): string
    {
        $stream = "BT /F1 10 Tf 1 0 0 1 0 0 Tm ET\n";
        foreach ($commands as $command) {
            if ($command['type'] === 'line') {
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $command['x1'], $command['y1'], $command['x2'], $command['y2']);
            } else {
                $stream .= sprintf("BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET\n", $command['size'], $command['x'], $command['y'], $command['value']);
            }
        }
        return $stream;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height < 42) {
            $this->addPage();
        }
    }

    private function safe(string $value): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    }

    private function truncate(string $value, int $length): string
    {
        return strlen($value) > $length ? substr($value, 0, max(1, $length - 3)) . '...' : $value;
    }
}
