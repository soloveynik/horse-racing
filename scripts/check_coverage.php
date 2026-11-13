<?php

declare(strict_types=1);

/**
 * Проверяет минимальный порог покрытия по clover.xml.
 * Запрещено снижать порог без обоснования.
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php check_coverage.php <clover.xml> <min_percent>\n");
    exit(2);
}

$file = $argv[1];
$min = (float) $argv[2];

if (!is_file($file)) {
    fwrite(STDERR, "Coverage report not found: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);
if ($xml === false) {
    fwrite(STDERR, "Cannot parse clover report\n");
    exit(1);
}

$metrics = $xml->project->metrics ?? $xml->metrics ?? null;
if ($metrics === null) {
    // PHPUnit clover structure
    $metrics = $xml->project->metrics;
}

$statements = (int) ($metrics['statements'] ?? 0);
$covered = (int) ($metrics['coveredstatements'] ?? 0);

if ($statements === 0) {
    fwrite(STDERR, "No statements measured — coverage unknown\n");
    exit(1);
}

$percent = round(($covered / $statements) * 100, 2);

echo "Coverage: {$percent}% (minimum required: {$min}%)\n";

if ($percent < $min) {
    fwrite(STDERR, "FAIL: coverage {$percent}% is below required {$min}%\n");
    exit(1);
}

echo "OK: coverage meets the threshold\n";
exit(0);
