<?php
/**
 * PM Attachment Handler
 * Manages file uploads, downloads, and cleanup for private messaging
 * 
 * @package PrivateMessaging
 * @since 2.0.0
 */

class PM_Attachment_Handler
{
    const UPLOAD_DIR = 'pm-attachments';
    const MAX_FILE_SIZE = 10485760; // 10MB
    const ALLOWED_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip'];

    /**
     * Get base upload directory for PM attachments
     */
    public static function get_base_upload_dir()
    {
        $upload_dir = wp_upload_dir();
        $base_path = $upload_dir['basedir'] . '/' . self::UPLOAD_DIR;
        $base_url = $upload_dir['baseurl'] . '/' . self::UPLOAD_DIR;
        
        return [
            'path' => $base_path,
            'url' => $base_url,
        ];
    }

    /**
     * Get conversation-specific upload directory
     */
    public static function get_conversation_upload_dir($conversation_id)
    {
        $base = self::get_base_upload_dir();
        $conv_path = $base['path'] . '/' . absint($conversation_id);
        $conv_url = $base['url'] . '/' . absint($conversation_id);
        
        return [
            'path' => $conv_path,
            'url' => $conv_url,
        ];
    }

    /**
     * Ensure upload directory exists
     */
    public static function ensure_upload_dir($conversation_id)
    {
        $dirs = self::get_conversation_upload_dir($conversation_id);
        if (!is_dir($dirs['path'])) {
            wp_mkdir_p($dirs['path']);
            // Add index.php to prevent directory listing
            file_put_contents($dirs['path'] . '/index.php', '<?php // Silence is golden');
        }
        return $dirs['path'];
    }

    /**
     * Validate file for upload
     */
    public static function validate_file($file)
    {
        if (empty($file) || !isset($file['tmp_name'])) {
            return new WP_Error('invalid_file', 'No file provided');
        }

        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return new WP_Error('file_too_large', sprintf('File size exceeds %s MB limit', self::MAX_FILE_SIZE / 1048576));
        }

        // Get file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_TYPES)) {
            return new WP_Error('invalid_type', 'File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_TYPES));
        }

        // Double-check detected type vs extension
        $type_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (!empty($type_info['ext']) && $type_info['ext'] !== $ext) {
            return new WP_Error('invalid_type', 'File extension mismatch detected');
        }

        // Basic MIME sniffing fallback
        $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : '';
        if ($mime && strpos($mime, 'php') !== false) {
            return new WP_Error('invalid_type', 'Executable file types are not allowed');
        }

        return true;
    }

    /**
     * Upload file to conversation folder
     */
    public static function upload_file($file, $conversation_id)
    {
        // User must be logged in
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', 'You must be logged in to upload files');
        }

        // Enforce role-based attachment permissions if configured
        $allowed_roles = mmg()->setting()->allow_attachment;
        if (is_array($allowed_roles) && count(array_filter($allowed_roles)) > 0) {
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;
            if (!array_intersect($user_roles, $allowed_roles)) {
                return new WP_Error('permission_denied', 'Your role cannot upload attachments');
            }
        }

        // If conversation_id > 0, validate user is part of conversation
        if ($conversation_id > 0 && !self::user_can_upload_to_conversation($conversation_id)) {
            return new WP_Error('permission_denied', 'You do not have permission to upload to this conversation');
        }

        // Validate file
        $validation = self::validate_file($file);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Ensure directory exists
        $upload_path = self::ensure_upload_dir($conversation_id);

        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = wp_hash(time() . $file['name']) . '.' . $ext;
        $target_path = $upload_path . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return new WP_Error('upload_failed', 'Failed to save file');
        }

        // Fix permissions
        chmod($target_path, 0644);

        return [
            'filename' => $filename,
            'original_name' => sanitize_file_name($file['name']),
            'size' => $file['size'],
            'url' => self::get_download_url($conversation_id, $filename),
        ];
    }

    /**
     * Get secure download URL
     */
    public static function get_download_url($conversation_id, $filename)
    {
        return add_query_arg([
            'action' => 'mm_download_attachment',
            'conversation_id' => absint($conversation_id),
            'filename' => urlencode($filename),
            '_wpnonce' => wp_create_nonce('mm_download_' . $conversation_id),
        ], admin_url('admin-ajax.php'));
    }

    /**
     * Get file information (size, extension, display name)
     */
    public static function get_file_info($conversation_id, $filename)
    {
        $dirs = self::get_conversation_upload_dir($conversation_id);
        $file_path = $dirs['path'] . '/' . $filename;

        if (!file_exists($file_path)) {
            return false;
        }

        $file_size = filesize($file_path);
        $path_info = pathinfo($filename);
        
        // Extract display name from filename (format: timestamp_originalname.ext)
        $display_name = $filename;
        if (preg_match('/^\d+_(.+)$/', $filename, $matches)) {
            $display_name = $matches[1];
        }

        return [
            'filename' => $filename,
            'display_name' => $display_name,
            'extension' => strtolower($path_info['extension'] ?? ''),
            'size' => $file_size,
            'size_formatted' => size_format($file_size, 2),
            'path' => $file_path,
            'url' => $dirs['url'] . '/' . $filename,
        ];
    }

    /**
     * Get preview data (Base64 encoded for images)
     */
    public static function get_preview_data($conversation_id, $filename)
    {
        $dirs = self::get_conversation_upload_dir($conversation_id);
        $file_path = $dirs['path'] . '/' . $filename;

        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found');
        }

        // Check file is image
        $mime_type = mime_content_type($file_path);
        if (strpos($mime_type, 'image/') === false) {
            return new WP_Error('not_image', 'File is not an image');
        }

        // Read file and encode as base64
        $file_data = file_get_contents($file_path);
        $base64 = base64_encode($file_data);
        
        return [
            'data_url' => 'data:' . $mime_type . ';base64,' . $base64,
            'filename' => $filename,
        ];
    }

    /**
     * Download file with permission check
     */
    public static function download_file($conversation_id, $filename)
    {
        // Require authenticated user
        if (!is_user_logged_in()) {
            wp_die('Authentication required', 'Unauthorized', ['response' => 403]);
        }

        // Verify nonce
        if (!wp_verify_nonce(mmg()->get('_wpnonce'), 'mm_download_' . $conversation_id)) {
            wp_die('Security check failed', 'Unauthorized', ['response' => 403]);
        }

        // Verify user can access conversation
        if (!self::user_can_access_conversation($conversation_id)) {
            wp_die('You do not have permission to download this file', 'Unauthorized', ['response' => 403]);
        }

        // Sanitize filename
        $filename = basename($filename);
        if (preg_match('/[^a-z0-9._-]/i', $filename)) {
            wp_die('Invalid filename', 'Bad Request', ['response' => 400]);
        }

        $dirs = self::get_conversation_upload_dir($conversation_id);
        $file_path = $dirs['path'] . '/' . $filename;

        // Verify file exists and is in correct directory
        if (!file_exists($file_path) || !is_file($file_path)) {
            wp_die('File not found', 'Not Found', ['response' => 404]);
        }

        // Verify file is within upload directory
            // Security: Verify file is within upload directory (Path Traversal Protection)
            $real_file_path = realpath($file_path);
            $real_upload_dir = realpath($dirs['path']);
        
            // Both paths must exist and file must be inside the directory
            if ($real_file_path === false || $real_upload_dir === false) {
            wp_die('Invalid file path', 'Forbidden', ['response' => 403]);
        }
        
            if (strpos($real_file_path, $real_upload_dir . DIRECTORY_SEPARATOR) !== 0) {
                wp_die('Path traversal attempt detected', 'Forbidden', ['response' => 403]);
            }

            // Verify MIME type (additional safety check)
            $mime_type = wp_check_filetype($real_file_path);
            if (empty($mime_type['type'])) {
                $mime_type['type'] = 'application/octet-stream';
            }

            // Additional: Blocked file types for security
            $blocked_types = array('application/x-php', 'application/x-executable', 'application/x-elf', 'application/x-mach-binary');
            if (in_array($mime_type['type'], $blocked_types, true)) {
                wp_die('File type not allowed for download', 'Forbidden', ['response' => 403]);
            }

        // Serve file
            header('Content-Type: ' . esc_attr($mime_type['type']));
            header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
            header('Content-Length: ' . filesize($real_file_path));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($real_file_path);
        exit;
    }

    /**
     * Delete file
     */
    public static function delete_file($conversation_id, $filename)
    {
        if (!self::user_can_upload_to_conversation($conversation_id)) {
            return new WP_Error('permission_denied', 'You do not have permission');
        }

        $filename = basename($filename);
        $dirs = self::get_conversation_upload_dir($conversation_id);
        $file_path = $dirs['path'] . '/' . $filename;

        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
            return true;
        }

        return new WP_Error('file_not_found', 'File does not exist');
    }

    /**
     * Delete entire conversation attachment folder
     */
    public static function delete_conversation_attachments($conversation_id)
    {
        $dirs = self::get_conversation_upload_dir($conversation_id);
        if (is_dir($dirs['path'])) {
            self::delete_directory($dirs['path']);
        }
    }

    /**
     * Recursively delete directory
     */
    private static function delete_directory($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $file_path = $path . '/' . $file;
            if (is_dir($file_path)) {
                self::delete_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
        rmdir($path);
    }

    /**
     * Check if current user can access conversation
     */
    private static function user_can_access_conversation($conversation_id)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $conversation = MM_Conversation_Model::model()->find($conversation_id);
        if (!$conversation) {
            return false;
        }

        $user_ids = $conversation->user_index;
        if (!is_array($user_ids)) {
            $user_ids = array_map('trim', explode(',', $user_ids));
        }

        return in_array(get_current_user_id(), array_map('intval', $user_ids));
    }

    /**
     * Check if current user can upload to conversation
     */
    private static function user_can_upload_to_conversation($conversation_id)
    {
        return self::user_can_access_conversation($conversation_id);
    }
    
    /**
     * Move attachments from temporary directory (0) to conversation directory
     * Called after message is sent and conversation_id is known
     */
    public static function move_attachments_to_conversation($attachment_string, $from_conv_id, $to_conv_id)
    {
        if (empty($attachment_string) || $from_conv_id == $to_conv_id) {
            return false;
        }
        
        $filenames = explode(',', $attachment_string);
        $filenames = array_filter(array_map('trim', $filenames));
        
        if (empty($filenames)) {
            return false;
        }
        
        $from_dirs = self::get_conversation_upload_dir($from_conv_id);
        $to_dirs = self::get_conversation_upload_dir($to_conv_id);
        
        // Create target directory if needed
        if (!file_exists($to_dirs['path'])) {
            wp_mkdir_p($to_dirs['path']);
        }
        
        $moved_count = 0;
        foreach ($filenames as $filename) {
            $from_path = $from_dirs['path'] . '/' . $filename;
            $to_path = $to_dirs['path'] . '/' . $filename;
            
            if (file_exists($from_path)) {
                if (rename($from_path, $to_path)) {
                    $moved_count++;
                }
            }
        }
        
        return $moved_count;
    }
}
