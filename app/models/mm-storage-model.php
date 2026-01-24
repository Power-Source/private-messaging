<?php

/**
 * Storage Model
 * Manages user storage space calculations and validation
 * 
 * @package PrivateMessaging
 * @since 1.0.0
 */
class MM_Storage_Model
{
    /**
     * Get total storage used by a user in bytes
     * 
     * @param int $user_id
     * @return int Total bytes used
     */
    public static function get_user_storage_used($user_id)
    {
        // Check cache first
        $cache_key = 'mm_storage_used_' . intval($user_id);
        $cached = wp_cache_get($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        
        $table_posts = $wpdb->prefix . 'posts';
        $table_postmeta = $wpdb->prefix . 'postmeta';
        
        // Get total size of message content (stored as WordPress posts)
        $query = "SELECT COALESCE(SUM(
                    CHAR_LENGTH(COALESCE(post_content, '')) + 
                    CHAR_LENGTH(COALESCE(post_title, '')) +
                    CHAR_LENGTH(COALESCE(post_excerpt, ''))
                ), 0) as total_size
                 FROM $table_posts
                 WHERE post_type = 'mm_message' 
                 AND (post_author = %d)";
        
        $result = $wpdb->get_var($wpdb->prepare($query, $user_id));
        $total_size = intval($result);
        
        // Also check for messages where user is recipient (stored in postmeta)
        $recipient_query = "SELECT GROUP_CONCAT(p.ID) as ids
                           FROM $table_posts p
                           INNER JOIN $table_postmeta pm ON p.ID = pm.post_id
                           WHERE p.post_type = 'mm_message'
                           AND pm.meta_key = 'send_to'
                           AND pm.meta_value LIKE %s
                           LIMIT 10000";
        
        $ids = $wpdb->get_var($wpdb->prepare($recipient_query, '%"' . $user_id . '"%'));
        
        if (!empty($ids)) {
            $ids_array = array_map('intval', explode(',', $ids));
            // Limit to prevent too many placeholders
            $ids_array = array_slice($ids_array, 0, 1000);
            $placeholders = implode(',', array_fill(0, count($ids_array), '%d'));
            
            $recipient_size_query = "SELECT COALESCE(SUM(
                        CHAR_LENGTH(COALESCE(post_content, '')) + 
                        CHAR_LENGTH(COALESCE(post_title, '')) +
                        CHAR_LENGTH(COALESCE(post_excerpt, ''))
                    ), 0) as total_size
                     FROM $table_posts
                     WHERE ID IN ($placeholders)";
            
            $recipient_size_query = $wpdb->prepare($recipient_size_query, ...$ids_array);
            $recipient_size = intval($wpdb->get_var($recipient_size_query));
            $total_size += $recipient_size;
        }
        
        // Add attachment file sizes
        $total_size += self::get_attachment_size_for_user($user_id);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $total_size, '', 3600);
        
        return $total_size;
    }
    
    /**
     * Get total attachment size for a user
     * 
     * @param int $user_id
     * @return int Total bytes of attachments
     */
    public static function get_attachment_size_for_user($user_id)
    {
        global $wpdb;
        
        $table_posts = $wpdb->prefix . 'posts';
        $table_postmeta = $wpdb->prefix . 'postmeta';
        
        // Get all mm_message posts for this user (as sender) - limit 1000
        $query = "SELECT ID FROM $table_posts
                 WHERE post_type = 'mm_message' AND post_author = %d LIMIT 1000";
        
        $message_ids = $wpdb->get_col($wpdb->prepare($query, $user_id));
        
        // Also get messages where user is recipient - limit 1000
        $recipient_query = "SELECT DISTINCT p.ID FROM $table_posts p
                           INNER JOIN $table_postmeta pm ON p.ID = pm.post_id
                           WHERE p.post_type = 'mm_message'
                           AND pm.meta_key = 'send_to'
                           AND pm.meta_value LIKE %s
                           LIMIT 1000";
        
        $recipient_ids = $wpdb->get_col($wpdb->prepare($recipient_query, '%"' . $user_id . '"%'));
        
        $all_message_ids = array_unique(array_merge((array)$message_ids, (array)$recipient_ids));
        
        $total_size = 0;
        
        if (!empty($all_message_ids)) {
            // Use batch query instead of N+1
            $placeholders = implode(',', array_fill(0, count($all_message_ids), '%d'));
            
            $batch_query = "SELECT post_id, meta_key, meta_value 
                           FROM $table_postmeta
                           WHERE post_id IN ($placeholders)
                           AND meta_key IN ('mm_attachments', 'attachment')";
            
            $batch_query = $wpdb->prepare($batch_query, ...$all_message_ids);
            $results = $wpdb->get_results($batch_query);
            
            // Process all results at once
            if (!empty($results)) {
                foreach ($results as $row) {
                    $attachments = maybe_unserialize($row->meta_value);
                    if (!empty($attachments)) {
                        if (is_array($attachments)) {
                            $total_size += self::calculate_attachment_size($attachments);
                        } elseif (is_string($attachments)) {
                            // Try to decode JSON
                            $decoded = @json_decode($attachments, true);
                            if (is_array($decoded)) {
                                $total_size += self::calculate_attachment_size($decoded);
                            }
                        }
                    }
                }
            }
        }
        
        return $total_size;
    }
    
    /**
     * Calculate total attachment size from attachment array
     * 
     * @param array $attachments
     * @return int Total bytes
     */
    private static function calculate_attachment_size($attachments)
    {
        $total_size = 0;
        
        foreach ($attachments as $attachment) {
            if (isset($attachment['id']) && intval($attachment['id']) > 0) {
                $file_path = get_attached_file(intval($attachment['id']));
                if ($file_path && file_exists($file_path)) {
                    $total_size += filesize($file_path);
                }
            } elseif (isset($attachment['file']) && !empty($attachment['file'])) {
                if (file_exists($attachment['file'])) {
                    $total_size += filesize($attachment['file']);
                }
            } elseif (isset($attachment['url']) && !empty($attachment['url'])) {
                // Try to get file from URL
                $upload_dir = wp_upload_dir();
                $relative_path = str_replace($upload_dir['baseurl'], '', $attachment['url']);
                $file_path = $upload_dir['basedir'] . $relative_path;
                if (file_exists($file_path)) {
                    $total_size += filesize($file_path);
                }
            }
        }
        
        return $total_size;
    }
    
    /**
     * Get storage limit for a user
     * 
     * @param int $user_id
     * @return int|bool Storage limit in bytes or false if unlimited
     */
    public static function get_user_storage_limit($user_id)
    {
        $settings = mmg()->setting();
        
        if ($settings->storage_unlimited) {
            return false; // Unlimited
        }
        
        return $settings->storage_limit;
    }
    
    /**
     * Get remaining storage for a user
     * 
     * @param int $user_id
     * @return int|false Remaining bytes or false if unlimited
     */
    public static function get_user_storage_remaining($user_id)
    {
        $limit = self::get_user_storage_limit($user_id);
        
        if ($limit === false) {
            return false; // Unlimited
        }
        
        $used = self::get_user_storage_used($user_id);
        $remaining = $limit - $used;
        
        return max(0, $remaining);
    }
    
    /**
     * Check if user has enough storage
     * 
     * @param int $user_id
     * @param int $bytes_needed
     * @return bool
     */
    public static function has_sufficient_storage($user_id, $bytes_needed = 0)
    {
        $limit = self::get_user_storage_limit($user_id);
        
        if ($limit === false) {
            return true; // Unlimited
        }
        
        $remaining = self::get_user_storage_remaining($user_id);
        
        return $remaining >= $bytes_needed;
    }
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes
     * @param int $precision
     * @return string Formatted size
     */
    public static function format_bytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get storage percentage used
     * 
     * @param int $user_id
     * @return int Percentage (0-100) or -1 if unlimited
     */
    public static function get_storage_percentage($user_id)
    {
        $limit = self::get_user_storage_limit($user_id);
        
        if ($limit === false) {
            return -1; // Unlimited
        }
        
        $used = self::get_user_storage_used($user_id);
        $percentage = min(100, ceil(($used / $limit) * 100));
        
        return max(0, $percentage);
    }
}
