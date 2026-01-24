<?php
define('WP_USE_THEMES', false);
require_once('/home/dern3rd/Local Sites/ps-dev/app/public/wp-load.php');

// Get the latest messages
$args = array(
    'post_type' => 'mm_message',
    'posts_per_page' => 5,
    'orderby' => 'ID',
    'order' => 'DESC'
);
$messages = get_posts($args);
echo "=== Latest Messages ===\n";
foreach ($messages as $msg) {
    echo "\nMessage ID: " . $msg->ID . "\n";
    echo "Title: " . $msg->post_title . "\n";
    echo "Content: " . substr($msg->post_content, 0, 50) . "...\n";
    
    $attachment = get_post_meta($msg->ID, '_attachment', true);
    echo "Attachment meta: " . var_export($attachment, true) . "\n";
    
    $conv_id = get_post_meta($msg->ID, '_conversation_id', true);
    echo "Conversation ID: " . $conv_id . "\n";
    
    // Check if attachment files exist
    if (!empty($attachment)) {
        $files = explode(',', $attachment);
        echo "Attachment files: " . count($files) . "\n";
        foreach ($files as $file) {
            $file = trim($file);
            if (empty($file)) continue;
            
            $upload_dir = wp_upload_dir();
            $pm_dir = $upload_dir['basedir'] . '/pm-attachments/' . $conv_id . '/';
            $file_path = $pm_dir . $file;
            
            echo "  - File: $file (" . (file_exists($file_path) ? 'EXISTS' : 'NOT FOUND') . ")\n";
            if (file_exists($file_path)) {
                echo "    Size: " . filesize($file_path) . " bytes\n";
            }
        }
    }
}
