<?php

/**
 * AJAX Handler
 * Initializes AJAX-related controllers
 * 
 * @package PrivateMessaging
 * @since 1.0.0
 */
class MAjax
{
    public function __construct()
    {
        new Notify_Controller();
        // Register attachment AJAX handlers
        add_action('wp_ajax_mm_upload_attachment', array($this, 'upload_attachment'));
        add_action('wp_ajax_mm_delete_attachment', array($this, 'delete_attachment'));
        add_action('wp_ajax_mm_download_attachment', array($this, 'download_attachment'));
        add_action('wp_ajax_mm_preview_attachment', array($this, 'preview_attachment'));
        add_action('wp_ajax_nopriv_mm_download_attachment', array($this, 'download_attachment'));
    }

    /**
     * Handle attachment upload via AJAX
     */
    public function upload_attachment()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_upload_attachment')) {
            wp_send_json_error('Security check failed');
        }

        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file provided');
        }

        $conversation_id = absint(mmg()->post('conversation_id'));
        $result = PM_Attachment_Handler::upload_file($_FILES['file'], $conversation_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * Handle attachment deletion via AJAX
     */
    public function delete_attachment()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_delete_attachment')) {
            wp_send_json_error('Security check failed');
        }

        $conversation_id = absint(mmg()->post('conversation_id'));
        $filename = sanitize_file_name(mmg()->post('filename'));

        $result = PM_Attachment_Handler::delete_file($conversation_id, $filename);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('File deleted');
    }

    /**
     * Handle attachment download via AJAX
     */
    public function download_attachment()
    {
        // Try new parameter names first, fall back to old ones for compatibility
        $conversation_id = absint(mmg()->get('conversation_id') ?: mmg()->get('conv_id'));
        $filename = sanitize_file_name(mmg()->get('filename') ?: mmg()->get('file'));

        PM_Attachment_Handler::download_file($conversation_id, $filename);
    }

    /**
     * Handle attachment preview via AJAX (image preview)
     */
    public function preview_attachment()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_preview_attachment')) {
            wp_send_json_error('Security check failed');
        }

        $conversation_id = absint(mmg()->post('conversation_id'));
        $filename = sanitize_file_name(mmg()->post('filename'));

        $result = PM_Attachment_Handler::get_preview_data($conversation_id, $filename);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }
}