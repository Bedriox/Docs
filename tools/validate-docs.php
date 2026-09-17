<?php

declare(strict_types=1);

// SPDX-License-Identifier: GPL-3.0-only

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$errors = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "$relative: cannot read file";
        continue;
    }

    if (!preg_match('/^# [^\r\n]+/m', $content)) {
        $errors[] = "$relative: missing level-one heading";
    }

    foreach (preg_split('/\R/', $content) ?: [] as $index => $line) {
        if (preg_match('/[ \t]+$/', $line)) {
            $errors[] = sprintf('%s:%d: trailing whitespace', $relative, $index + 1);
        }
    }

    preg_match_all('/\[[^\]]+\]\(([^)]+)\)/', $content, $matches);
    foreach ($matches[1] as $target) {
        $target = trim($target, '<>');
        if ($target === '' || str_contains($target, '://') || str_starts_with($target, '#') || str_starts_with($target, 'mailto:')) {
            continue;
        }
        $target = urldecode(explode('#', $target, 2)[0]);
        $resolved = realpath(dirname($path) . DIRECTORY_SEPARATOR . $target);
        if ($resolved === false) {
            $errors[] = "$relative: broken relative link: $target";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Documentation validation passed.\n");

