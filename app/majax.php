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
        // Register search handler
        add_action('wp_ajax_mm_search_conversations', array($this, 'search_conversations'));
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

    /**
     * Handle conversation search via AJAX
     * Live search for inbox
     */
    public function search_conversations()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Not authenticated'));
        }

        $query = sanitize_text_field(mmg()->get('query'));
        
        if (empty($query) || strlen($query) < 2) {
            wp_send_json_error(array('message' => 'Query too short: ' . strlen($query)));
        }

        try {
            global $wpdb;
            
            // Get search results (this will handle pagination internally)
            $results = MM_Conversation_Model::search($query);
            
            // Build response data
            $items = array();
            foreach ($results as $conversation) {
                $message = $conversation->get_last_message();
                if ($message) {
                    $first_msg = $conversation->get_first_message();
                    $items[] = array(
                        'id' => mmg()->encrypt($conversation->id),
                        'subject' => $first_msg ? apply_filters('mm_message_subject', $first_msg->subject) : '(No subject)',
                        'snippet' => mb_substr(strip_tags($message->content), 0, 60),
                        'sender' => $message->get_name($message->send_from),
                        'date' => date('j M', strtotime($message->date)),
                        'avatar' => PM_Avatar_Handler::get_avatar_html($message->send_from, 32, 'mm-search-avatar'),
                    );
                }
            }

            wp_send_json_success(array(
                'results' => $items,
                'total' => count($items),
                'query' => $query,
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'query' => $query,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ));
        }
    }
}