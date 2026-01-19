<?php
$path = __DIR__ . '/../admin/pawning.php';
$lines = file($path);
$dq = $sq = 0;
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $dq += substr_count($line, '"');
    $sq += substr_count($line, "'");
    if ($i < 200) {
        // print counts up to 200
        echo ($i+1) . ": dq=" . $dq . " sq=" . $sq . " | " . rtrim($line, "\r\n") . PHP_EOL;
    }
}
echo "Total up to 200: dq=$dq sq=$sq\n";
