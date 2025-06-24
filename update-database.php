<?php
/**
 * Database Update Script für PS Chat
 * Führe dieses Script aus, um die Datenbank-Tabellen zu reparieren
 */

// WordPress Bootstrap
if (file_exists('../../../wp-config.php')) {
    require_once '../../../wp-config.php';
} elseif (file_exists('../../../../wp-config.php')) {
    require_once '../../../../wp-config.php';
} else {
    die("WordPress wp-config.php nicht gefunden!\n");
}

echo "=== PS Chat Database Update ===\n\n";

// Lade die Database-Klasse
require_once 'includes/core/database.php';

echo "1. Lösche alte Tabellen...\n";
global $wpdb;

$old_tables = [
    $wpdb->prefix . 'psource_chat_messages',
    $wpdb->prefix . 'psource_chat_sessions',
    $wpdb->prefix . 'psource_chat_user_sessions',
    $wpdb->prefix . 'psource_chat_logs'
];

foreach ($old_tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
    echo "   - $table gelöscht\n";
}

echo "\n2. Erstelle neue Tabellen...\n";
\PSSource\Chat\Core\Database::create_tables();
echo "   ✅ Tabellen erstellt\n";

echo "\n3. Prüfe Tabellenstruktur...\n";
$messages_table = \PSSource\Chat\Core\Database::get_table_name('messages');
$columns = $wpdb->get_results("SHOW COLUMNS FROM $messages_table");

echo "   Spalten in $messages_table:\n";
foreach ($columns as $column) {
    echo "   - {$column->Field} ({$column->Type})\n";
}

echo "\n✅ Database Update abgeschlossen!\n";
echo "\nJetzt kannst Du das Dashboard-Widget testen.\n";
