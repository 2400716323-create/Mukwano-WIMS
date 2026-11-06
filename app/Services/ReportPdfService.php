<?php

namespace App\Services;

class ReportPdfService
{
    /**
     * @param array{
     *     title: string,
     *     columns: array<string, string>,
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, int|float|string>
     * } $report
     * @param  array<string, mixed>  $filters
     */
    public function render(array $report, array $filters): string
    {
        $lines = [
            $report['title'],
            'Generated: '.now()->format('Y-m-d H:i:s T'),
            'Filters: '.($this->filterDescription($filters) ?: 'None'),
            'Totals: '.implode(' | ', array_map(
                fn (string $key, int|float|string $value): string => "{$key}: {$value}",
                array_keys($report['totals']),
                array_values($report['totals']),
            )),
            implode(' | ', array_values($report['columns'])),
        ];

        foreach ($report['rows'] as $row) {
            $lines[] = implode(' | ', array_map(
                fn (string $key): string => $this->text((string) ($row[$key] ?? '')),
                array_keys($report['columns']),
            ));
        }

        if ($report['rows'] === []) {
            $lines[] = 'No records matched the selected filters.';
        }

        $pages = array_chunk($lines, 43);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $pageReferences = [];

        foreach ($pages as $index => $pageLines) {
            $pageObject = 4 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageReferences[] = "{$pageObject} 0 R";
            $commands = "BT\n/F1 8 Tf\n36 560 Td\n";

            foreach ($pageLines as $lineIndex => $line) {
                if ($lineIndex > 0) {
                    $commands .= "0 -12 Td\n";
                }
                $commands .= '('.$this->escapePdfText($this->truncate($line)).") Tj\n";
            }
            $commands .= 'ET';

            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] '
                ."/Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObject} 0 R >>";
            $objects[$contentObject] = '<< /Length '.strlen($commands)." >>\nstream\n{$commands}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.count($pages).' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filterDescription(array $filters): string
    {
        $labels = [
            'from' => 'From',
            'to' => 'To',
            'product_id' => 'Product ID',
            'batch_id' => 'Batch ID',
            'location_id' => 'Location ID',
            'status' => 'Status',
        ];
        $applied = [];
        foreach ($labels as $key => $label) {
            if (isset($filters[$key])) {
                $applied[] = "{$label}: {$filters[$key]}";
            }
        }

        return implode(', ', $applied);
    }

    private function truncate(string $text): string
    {
        return strlen($text) > 160 ? substr($text, 0, 157).'...' : $text;
    }

    private function text(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', $value);
    }

    private function escapePdfText(string $text): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
    }
}
