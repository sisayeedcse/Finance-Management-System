<?php
$path = __DIR__ . '/../database/firestore-backup.sql';
if (!file_exists($path)) {
    fwrite(STDERR, "SQL backup not found at {$path}\n");
    exit(1);
}
$s = file_get_contents($path);
if (!preg_match('/INSERT INTO `members` \([^)]+\) VALUES\s*(.*);/is', $s, $m)) {
    echo "No members INSERT block found.\n";
    exit(0);
}
$vals = $m[1];
$ids = [];
// match tuples starting with ('id', ...)
if (preg_match_all("/\(\s*'([^']+)'/", $vals, $matches)) {
    $ids = $matches[1];
}
foreach ($ids as $id) {
    echo $id . PHP_EOL;
}
