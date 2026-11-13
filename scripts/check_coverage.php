<?php

declare(strict_types=1);

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

$metrics = $xml->project->metrics ?? null;
if ($metrics === null) {
    fwrite(STDERR, "No metrics in clover report\n");
    exit(1);
}

$statements = (int) ($metrics['statements'] ?? 0);
$covered = (int) ($metrics['coveredstatements'] ?? 0);

if ($statements === 0) {
    fwrite(STDERR, "No statements measured\n");
    exit(1);
}

$percent = round(($covered / $statements) * 100, 2);
echo "Coverage: {$percent}% (minimum: {$min}%)\n";

if ($percent < $min) {
    fwrite(STDERR, "FAIL: coverage {$percent}% < {$min}%\n");
    exit(1);
}

echo "OK: coverage meets threshold\n";
exit(0);
