<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "=== DATABASE: " . $db . " ===\n";
echo "Total tables: " . count($tables) . "\n";
foreach ($tables as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo str_pad($t, 30) . " : " . $c . " rows\n";
    } catch (Exception $e) {
        echo str_pad($t, 30) . " : ERROR - " . $e->getMessage() . "\n";
    }
}
