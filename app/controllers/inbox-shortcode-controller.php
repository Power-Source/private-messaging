<?php

/**
 * Inbox Shortcode Controller
 * Handles [message_inbox] shortcode and inbox functionality
 * 
 * @package PrivateMessaging
 * @since 1.0.0
 */
class Inbox_Shortcode_Controller
{
    use Template_Loader_Trait;
    
    protected $messages = array();

    public function __construct()
    {
        $this->template_base_path = dirname(__DIR__) . '/views/';
        
        add_shortcode('message_inbox', array(&$this, 'inbox'));
        add_action('wp_loaded', array(&$this, 'process_request'));
        add_action('wp_ajax_mm_send_message', array(&$this, 'send_message'));
        add_action('wp_ajax_mm_suggest_users', array(&$this, 'suggest_users'));
        add_action('wp_ajax_mm_load_conversation', array(&$this, 'load_conversation'));
        add_action('wp_ajax_mm_load_box', array(&$this, 'load_box'));
        add_action('wp_ajax_nopriv_mm_load_box', array(&$this, 'load_box'));
        add_action('wp_ajax_mm_status', array(&$this, 'change_status'));
        add_action('wp_ajax_mm_delete_conversation', array(&$this, 'delete_conversation'));
        add_action('wp_footer', array(&$this, 'footer'));
    }

    function load_box()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_load_box')) {
            exit;
        }
        $type = sanitize_text_field(mmg()->post('box', 'inbox'));
        if (isset($_POST['query']) && !empty($_POST['query'])) {
            $type = 'search';
        }
        $models = match ($type) {
            'inbox' => MM_Conversation_Model::get_conversation(),
            'unread' => MM_Conversation_Model::get_unread(),
            'read' => MM_Conversation_Model::get_read(),
            'sent' => MM_Conversation_Model::get_sent(),
            'archive' => MM_Conversation_Model::get_archive(),
            'search' => MM_Conversation_Model::search(mmg()->get('query')),
            default => MM_Conversation_Model::get_conversation(),
        };
        $total_pages = mmg()->global['conversation_total_pages'];
        $compose_html = '';
        if (is_user_logged_in()) {
            $compose_html = $this->load_template_part('shortcode/compose_inline', [], false);
        }
        $html = $this->load_template_part('shortcode/inbox_inner', array(
            'models' => $models,
            'total_pages' => $total_pages,
            'paged' => mmg()->get('mpaged', 'int', 1),
            'compose_html' => $compose_html
        ), false);
        wp_send_json(array('html' => $html));
        exit;
    }

    function footer()
    {
        ?>
        <div class="ig-container attachments-footer"></div>
    <?php
    }

    function change_status()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_status')) {
            exit;
        }
        $id = mmg()->post('id');
        $id = mmg()->decrypt($id);
        $type = mmg()->post('type');
        $model = MM_Conversation_Model::model()->find($id);
        if (is_object($model) && !is_null($type)) {
            $status = $model->get_current_status();

            $status->status = $type;
            $status->save();
        }
    }

    /**
     * Delete a conversation and its attachments
     */
    function delete_conversation()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_delete_conv')) {
            wp_send_json(array('status' => 'fail', 'message' => __('Invalid nonce', mmg()->domain)));
        }

        $id = mmg()->decrypt(mmg()->post('id'));
        $model = MM_Conversation_Model::model()->find($id);

        if (!$model || !$model->exist) {
            wp_send_json(array('status' => 'fail', 'message' => __('Conversation not found', mmg()->domain)));
        }

        // Ensure current user is part of the conversation
        $participants = array_map('intval', explode(',', $model->user_index));
        if (!in_array(get_current_user_id(), $participants, true)) {
            wp_send_json(array('status' => 'fail', 'message' => __('Not allowed to delete this conversation', mmg()->domain)));
        }

        $deleted = $model->delete();
        if ($deleted) {
            wp_send_json(array('status' => 'success'));
        }

        wp_send_json(array('status' => 'fail', 'message' => __('Deletion failed', mmg()->domain)));
    }

    function process_request()
    {
        if (isset($_POST['mm_user_setting']) && sanitize_text_field($_POST['mm_user_setting']) == 1) {
            if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_user_setting_' . get_current_user_id())) {
                exit;
            }

            $user_id = get_current_user_id();
            $enable_receipt = isset($_POST['receipt']) ? absint($_POST['receipt']) : 0;
            $prevent_receipt = isset($_POST['prevent']) ? absint($_POST['prevent']) : 0;
            $setting = get_user_meta($user_id, '_messages_setting', true);
            if (!$setting) {
                $setting = array();
            }
            $setting['enable_receipt'] = $enable_receipt;
            $setting['prevent_receipt'] = $prevent_receipt;

            update_user_meta($user_id, '_messages_setting', $setting);
            do_action('mm_user_setting_saved', $setting, get_current_user_id());
            $this->set_flash('user_setting_' . $user_id, __("Your settings have been successfully updated", mmg()->domain));
            // Return last viewed box instead of reloading
            $last_box = isset($_POST['mm_last_box']) ? sanitize_text_field($_POST['mm_last_box']) : 'inbox';
            wp_redirect(esc_url(add_query_arg('box', $last_box, $_SERVER['REQUEST_URI'])));
            exit;
        }
    }

    function load_conversation()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_load_conversation')) {
            exit;
        }

        $id = mmg()->decrypt(mmg()->post('id'));
        $model = MM_Conversation_Model::model()->find($id);
        if (!$model || !$model->exist) {
            wp_send_json(array(
                'status' => 'fail',
                'message' => __('Conversation not found', mmg()->domain),
            ));
            exit;
        }

        $html = $this->render_inbox_message($model);
        if (!$model->is_archive()) {
            $model->mark_as_read();
            do_action('mm_conversation_read', $model);
        }
        wp_send_json(array(
            'html' => $html,
            'count_unread' => MM_Conversation_Model::count_unread(true),
            'count_read' => MM_Conversation_Model::count_read(true)
        ));
        exit;
    }

    function inbox($atts)
    {
        $a = wp_parse_args($atts, array(
            'nav_view' => 'both'
        ));
        $show_nav = (bool) $this->can_show_nav($a['nav_view']);

        if (!is_user_logged_in()) {
            do_action('mmg_before_load_login_form');
            mmg()->load_script('login');
            return $this->render_with_layout('shortcode/login', array(
                'show_nav' => $show_nav
            ), $show_nav, false);
        }
        mmg()->load_script('inbox');
        //$a = shortcode_atts($atts, array());
        $type = mmg()->get('box', 'inbox');
        if (isset($_GET['query']) && !empty($_GET['query'])) {
            $type = 'search';
        }
        $total_pages = 0;
        $compose_html = '';
        if (is_user_logged_in()) {
            $compose_html = $this->load_template_part('shortcode/compose_inline', [], false);
        }
        if ($type === 'setting') {
            return $this->render_with_layout('shortcode/setting', array(
                'show_nav' => $show_nav,
                'compose_html' => $compose_html
            ), $show_nav, false);
        }

        $models = match ($type) {
            'inbox' => MM_Conversation_Model::get_conversation(),
            'unread' => MM_Conversation_Model::get_unread(),
            'read' => MM_Conversation_Model::get_read(),
            'sent' => MM_Conversation_Model::get_sent(),
            'archive' => MM_Conversation_Model::get_archive(),
            'search' => MM_Conversation_Model::search(mmg()->get('query')),
            default => MM_Conversation_Model::get_conversation(),
        };
        $total_pages = mmg()->global['conversation_total_pages'];

        return $this->render_with_layout('shortcode/inbox', array(
            'models' => $models,
            'total_pages' => $total_pages,
            'paged' => mmg()->get('mpaged', 'int', 1),
            'show_nav' => $show_nav,
            'compose_html' => $compose_html
        ), $show_nav, false);
    }

    function render_compose_form()
    {
        $this->load_template_part('shortcode/_compose_form');
    }

    function suggest_users()
    {
        if (!wp_verify_nonce(mmg()->get('_wpnonce'), 'mm_suggest_users')) {
            exit;
        }
        $query_string = mmg()->post('query');
        $query = new WP_User_Query(apply_filters('mm_suggest_users_args', array(
            'search' => '*' . mmg()->post('query') . '*',
            'search_columns' => array('user_login'),
            'exclude' => array(get_current_user_id()),
            'number' => 10,
            'orderby' => 'user_login',
            'order' => 'ASC'
        )));
        $name_query = new WP_User_Query(apply_filters('mm_suggest_users_first_last_args', array(
            'exclude' => array(get_current_user_id()),
            'number' => 10,
            'orderby' => 'user_login',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'first_name',
                    'value' => $query_string,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'last_name',
                    'value' => $query_string,
                    'compare' => 'LIKE'
                )
            )
        )));
        $results = array_merge($query->get_results(), $name_query->get_results());

        $data = array();
        foreach ($results as $user) {
            $userdata = get_userdata($user->ID);
            $name = $user->user_login;
            $full_name = trim($userdata->first_name . ' ' . $userdata->last_name);
            if (strlen($full_name)) {
                $name = $user->user_login . ' - ' . $full_name;
            }
            $obj = new stdClass();
            $obj->id = $user->ID;
            $obj->name = $name;
            $data[] = $obj;
        }

        $data = apply_filters('mm_suggest_users_result', $data);

        wp_send_json($data);

        exit;
    }

    function send_message()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'compose_message')) {
            exit;
        }

        $model = new MM_Message_Model();
        $raw_payload = isset($_POST['MM_Message_Model']) ? wp_unslash($_POST['MM_Message_Model']) : array();
        $model->import($raw_payload);
        $model = apply_filters('mm_before_send_message', $model);

        // Determine if this is a reply or new message BEFORE validation
        $is_reply = !empty($raw_payload['conversation_id']);
        
        if ($model->validate()) {
            // Check if this is a reply (conversation_id is set)
            if ($is_reply) {
                // Reply to existing conversation
                $conv_id = absint($model->conversation_id);
                $c_model = MM_Conversation_Model::model()->find($conv_id);
                
                if (!$c_model || !$c_model->exist) {
                    wp_send_json(array(
                        'status' => 'fail',
                        'errors' => array('conversation_id' => __('Conversation not found', mmg()->domain))
                    ));
                    exit;
                }
                
                $user_ids = $c_model->user_index;
                $user_ids = $this->logins_to_ids($user_ids);
                // Remove current user from recipients
                unset($user_ids[array_search(get_current_user_id(), $user_ids)]);
                
                $user_ids = apply_filters('mm_reply_user_ids', $user_ids);
                
                // Update all recipients to unread
                foreach ($user_ids as $user_id) {
                    MM_Message_Status_Model::model()->status($conv_id, MM_Message_Status_Model::STATUS_UNREAD, $user_id);
                }
                
                // Get first message in conversation to extract subject
                $first_message = MM_Message_Model::find_first_in_conversation($conv_id);
                if (!$first_message) {
                    wp_send_json(array(
                        'status' => 'fail',
                        'errors' => array('conversation_id' => __('Could not find original message', mmg()->domain))
                    ));
                    exit;
                }
                
                // Create reply message
                $message_id = MM_Message_Model::reply(implode(',', $user_ids), $first_message->id, $conv_id, $model->export());
                $c_model->update_index($message_id);
                
                // Move attachments from temporary (0) to conversation directory
                if (!empty($model->attachment)) {
                    PM_Attachment_Handler::move_attachments_to_conversation($model->attachment, 0, $conv_id);
                }
                
                $this->set_flash('mm_sent_' . get_current_user_id(), __("Deine Nachricht wurde gesendet.", mmg()->domain));
                wp_send_json(array('status' => 'success'));
            } else {
                // New message - check for existing conversation with same recipient + subject
                $send_to = $model->send_to;
                $user_ids = $this->logins_to_ids($send_to);
                $user_ids = array_map('intval', array_unique(array_filter($user_ids)));

                $current_user_id = get_current_user_id();

                // Strip current user to avoid self-send
                $user_ids = array_values(array_filter($user_ids, function($id) use ($current_user_id) {
                    return $id > 0 && $id !== $current_user_id;
                }));

                $cc_list = mmg()->post('cc');
                $cc_list = explode(',', $cc_list);
                $cc_list = array_map('intval', array_unique(array_filter($cc_list)));
                $cc_list = array_values(array_filter($cc_list, function($id) use ($current_user_id) {
                    return $id > 0 && $id !== $current_user_id;
                }));

                if (empty($user_ids) && empty($cc_list)) {
                    wp_send_json(array(
                        'status' => 'fail',
                        'errors' => array('send_to' => __('Du kannst keine Nachricht an dich selbst senden. Bitte wähle einen Empfänger.', mmg()->domain))
                    ));
                    exit;
                }
                
                if (empty($cc_list)) {
                    // Single recipient: always create a new conversation
                    foreach ($user_ids as $user_id) {
                        $this->_send_message($user_id, $model);
                    }
                } else {
                    // Group conversation: merge primary + CC, deduplicate
                    $all_recipients = array_values(array_unique(array_merge($user_ids, $cc_list)));
                    $this->_send_message_group($all_recipients, $model);
                }

                $this->set_flash('mm_sent_' . get_current_user_id(), __("Deine Nachricht wurde gesendet.", mmg()->domain));
                wp_send_json(array(
                    'status' => 'success'
                ));
            }
        } else {
            wp_send_json(array(
                'status' => 'fail',
                'errors' => $model->get_error(),
                'payload' => $raw_payload
            ));
        }
        exit;
    }

    function _reply_message($conv_id, $message_id, $user_ids, $model)
    {
        //load conversation
        $conversation = MM_Conversation_Model::model()->find($conv_id);
        foreach ($user_ids as $user_id) {
            MM_Message_Status_Model::model()->status($conversation->id, MM_Message_Status_Model::STATUS_UNREAD, $user_id);
        }
        $id = MM_Message_Model::reply(implode(',', $user_ids), $message_id, $conv_id, $model->export());
        //update index
        $conversation->update_index($id);
    }

    function _send_message($user_id, $model)
    {
        $current_user_id = get_current_user_id();
        $user_id = intval($user_id);
        if ($user_id <= 0 || $user_id === $current_user_id) {
            return false;
        }

        //create new conversation
        $conversation = new MM_Conversation_Model();
        $conversation->save();
        // Apply status for recipient only (do not add sender to status to keep sender out of inbox)
        MM_Message_Status_Model::model()->status($conversation->id, MM_Message_Status_Model::STATUS_UNREAD, $user_id);
        $id = MM_Message_Model::send($user_id, $conversation->id, $model->export());
        $conversation->update_index($id);
        
        // Move attachments from temporary (0) to conversation directory
        if (!empty($model->attachment)) {
            PM_Attachment_Handler::move_attachments_to_conversation($model->attachment, 0, $conversation->id);
        }
        
        return $id;
    }
    
    /**
     * Find existing conversation with same recipient and subject
     */
    function find_existing_conversation($user_id, $subject)
    {
        return false;
    }

    function _send_message_group($user_ids, $model)
    {
        $current_user_id = get_current_user_id();
        $user_ids = array_values(array_filter(array_map('intval', (array)$user_ids), function($id) use ($current_user_id) {
            return $id > 0 && $id !== $current_user_id;
        }));

        if (empty($user_ids)) {
            return false;
        }

        //create new conversation
        $conversation = new MM_Conversation_Model();
        $conversation->save();
        // Apply status for recipients only
        foreach ($user_ids as $user_id) {
            MM_Message_Status_Model::model()->status($conversation->id, MM_Message_Status_Model::STATUS_UNREAD, $user_id);
        }
        $message_id = MM_Message_Model::send(implode(',', $user_ids), $conversation->id, $model->export());
        $conversation->update_index($message_id);
    }

    function logins_to_ids($users)
    {
        if (!is_array($users)) {
            $users = explode(',', $users);
        }
        $data = array();
        foreach ($users as $username) {
            if (filter_var($username, FILTER_VALIDATE_INT)) {
                $user = get_user_by('id', $username);
                if (is_object($user)) {
                    $data[] = $user->ID;
                }
            } else {
                $user = get_user_by('login', $username);
                if (is_object($user)) {
                    $data[] = $user->ID;
                }
            }
        }
        return apply_filters('mm_send_to_this_users', $data);
    }

    function render_inbox_message(MM_Conversation_Model $model)
    {
        //get all the message from this conversation
        $messages = $model->get_messages();
        $this->messages = $messages;
        return $this->load_template_part('shortcode/_inbox_message', array(
            'messages' => $messages
        ), false);
    }

    public function can_show_nav($condition)
    {
        if ($condition == 'both') {
            return true;
        }
        if ($condition == 'loggedin') {
            return is_user_logged_in();
        }
        if ($condition == 'loggedout') {
            return !is_user_logged_in();
        }
    }
}