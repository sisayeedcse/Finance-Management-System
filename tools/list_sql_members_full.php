<?php
$path = __DIR__ . '/../database/firestore-backup.sql';
if (!file_exists($path)) {
    fwrite(STDERR, "SQL backup not found at {$path}\n");
    exit(1);
}
$s = file_get_contents($path);
if (!preg_match('/INSERT INTO `members` \([^)]+\) VALUES\s*(.*);/is', $s, $m)) {
    echo "No members INSERT block found.".PHP_EOL;
    exit(0);
}
$vals = $m[1];

// match tuples and capture first three quoted fields: id, name, email
if (preg_match_all("/\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'/", $vals, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $row) {
        $id = $row[1];
        $name = $row[2];
        $email = $row[3];
        echo implode("\t", [$id, $name, $email]) . PHP_EOL;
    }
} else {
    echo "No member tuples parsed.".PHP_EOL;
}
