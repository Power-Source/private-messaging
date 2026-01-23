<?php
/**
 * Database Optimization Migration
 * Adds missing indices for query performance
 * 
 * Run this via: wp eval-file database-optimization-migration.php
 * Or manually execute the SQL statements
 * 
 * @since 2.5.0
 */

if (!function_exists('mmg')) {
    return; // Only run in plugin context
}

global $wpdb;

// Migration identifier for future updates
define('MM_DB_MIGRATION_VERSION', '1.0');

/**
 * Add indices to improve query performance
 */
function mm_add_database_indices() {
    global $wpdb;
    
    $indices = array(
        array(
            'table' => $wpdb->base_prefix . 'mm_conversation',
            'index' => 'idx_site_id',
            'columns' => 'site_id',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}mm_conversation` ADD INDEX `idx_site_id` (`site_id`)"
        ),
        array(
            'table' => $wpdb->base_prefix . 'mm_conversation',
            'index' => 'idx_send_to',
            'columns' => 'send_to',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}mm_conversation` ADD INDEX `idx_send_to` (`send_to`)"
        ),
        array(
            'table' => $wpdb->base_prefix . 'mm_conversation',
            'index' => 'idx_send_from',
            'columns' => 'send_from',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}mm_conversation` ADD INDEX `idx_send_from` (`send_from`)"
        ),
        array(
            'table' => $wpdb->base_prefix . 'mm_status',
            'index' => 'idx_user_id',
            'columns' => 'user_id',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}mm_status` ADD INDEX `idx_user_id` (`user_id`)"
        ),
        array(
            'table' => $wpdb->base_prefix . 'mm_status',
            'index' => 'idx_status',
            'columns' => 'status',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}mm_status` ADD INDEX `idx_status` (`status`)"
        ),
        array(
            'table' => $wpdb->base_prefix . 'mm_status',
            'index' => 'idx_conversation_user',
            'columns' => 'conversation_id, user_id',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}mm_status` ADD INDEX `idx_conversation_user` (`conversation_id`, `user_id`)"
        ),
        array(
            'table' => $wpdb->base_prefix . 'posts',
            'index' => 'idx_post_type_author',
            'columns' => 'post_type, post_author',
            'sql' => "ALTER TABLE `{$wpdb->base_prefix}posts` ADD INDEX `idx_mm_post_type_author` (`post_type`, `post_author`) WHERE post_type='mm_conversation' OR post_type='mm_message'",
            'conditional' => true
        )
    );
    
    $created = 0;
    $skipped = 0;
    
    foreach ($indices as $index_info) {
        $table_name = $index_info['table'];
        $index_name = $index_info['index'];
        
        // Check if index already exists
        $index_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND INDEX_NAME = %s",
            DB_NAME,
            str_replace($wpdb->base_prefix, '', $table_name),
            $index_name
        ));
        
        if (empty($index_exists)) {
            // Create index
            if (!isset($index_info['conditional']) || !$index_info['conditional']) {
                $result = $wpdb->query($index_info['sql']);
                if ($result !== false) {
                    $created++;
                    error_log("[MM Database] Created index: {$index_name} on {$table_name}");
                } else {
                    error_log("[MM Database] Failed to create index: {$index_name}. Error: " . $wpdb->last_error);
                }
            }
        } else {
            $skipped++;
        }
    }
    
    // Log migration result
    update_option('mm_db_migration_version', MM_DB_MIGRATION_VERSION);
    update_option('mm_db_indices_created', $created);
    
    return array(
        'created' => $created,
        'skipped' => $skipped,
        'timestamp' => current_time('mysql')
    );
}

/**
 * Check and repair table statistics
 */
function mm_analyze_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->base_prefix . 'mm_conversation',
        $wpdb->base_prefix . 'mm_status',
    );
    
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $wpdb->query("ANALYZE TABLE `$table`");
            $wpdb->query("OPTIMIZE TABLE `$table`");
        }
    }
}

// Execute migration
if (current_user_can('manage_options')) {
    $result = mm_add_database_indices();
    mm_analyze_tables();
    
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::success("Database optimization complete. Created: {$result['created']} indices, Skipped: {$result['skipped']} existing.");
    }
}
?>
