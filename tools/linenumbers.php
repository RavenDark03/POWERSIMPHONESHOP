<?php
$path = __DIR__ . '/../admin/pawning.php';
$lines = file($path);
foreach ($lines as $i => $line) {
    echo ($i+1) . ': ' . rtrim($line, "\r\n") . PHP_EOL;
}
