<?php
/**
 * Database Migration Script for Product Images Gallery
 * This script creates the product_images table and migrates existing data
 */

require_once __DIR__ . '/includes/config/init.php';

try {
    echo "Starting database migration for product images gallery...\n\n";
    
    // Read the migration SQL file
    $migration_sql = file_get_contents(__DIR__ . '/database/migrations/add_product_images_table.sql');
    
    if (!$migration_sql) {
        throw new Exception("Could not read migration file");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migration_sql)));
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "✅ Created product_images table\n";
    echo "✅ Migrated existing product images\n";
    echo "✅ Added necessary indexes\n\n";
    
    echo "You can now:\n";
    echo "1. Add multiple images when creating new products in the admin panel\n";
    echo "2. View the enhanced image gallery on product details pages\n";
    echo "3. Use the lightbox feature to view full-size images\n\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
    exit(1);
}
?>
