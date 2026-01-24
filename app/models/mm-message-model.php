<?php

/**
 * MM_Message_Model - Modern WordPress-Native Implementation
 * Replaces IG_Post_Model with native WP APIs
 * 
 * @author: Hoang Ngo (Modernized)
 * @package: PrivateMessaging
 */
class MM_Message_Model
{
    const UNREAD = 'unread', READ = 'read';
    const POST_TYPE = 'mm_message';

    // Properties
    public $id;
    public $subject;
    public $content;
    public $send_to;
    public $send_from;
    public $reply_to;
    public $status;
    public $date;
    public $post_status = 'publish';
    public $attachment;
    public $conversation_id;
    
    public $exist = false; // Track if loaded from DB
    private $errors = array();

    /**
     * Validation rules
     */
    private $rules = array();

    /**
     * Load message from WordPress
     */
    public static function find($id)
    {
        $post = get_post($id, OBJECT);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return null;
        }
        
        return self::from_post($post);
    }

    /**
     * Load message by message ID array
     */
    public static function find_by_ids($ids, $single = false, $force = false, $orderby = 'ID DESC')
    {
        if (!is_array($ids)) {
            $ids = array_filter(explode(',', $ids));
        }
        
        if (empty($ids)) {
            return $single ? null : array();
        }

        $posts = get_posts(array(
            'post_type' => self::POST_TYPE,
            'post__in' => $ids,
            'posts_per_page' => -1,
            'orderby' => $orderby,
            'suppress_filters' => true,
        ));

        if (empty($posts)) {
            return $single ? null : array();
        }

        $models = array_map(array(__CLASS__, 'from_post'), $posts);
        return $single ? reset($models) : $models;
    }

    /**
     * Convert WP_Post to Model
     */
    private static function from_post($post)
    {
        $model = new self();
        $model->id = $post->ID;
        $model->subject = $post->post_title;
        $model->content = $post->post_content;
        $model->send_from = $post->post_author;
        $model->reply_to = $post->post_parent;
        $model->date = $post->post_date;
        $model->post_status = $post->post_status;
        
        // Load meta
        $model->send_to = get_post_meta($post->ID, '_send_to', true);
        $model->attachment = get_post_meta($post->ID, '_attachment', true);
        $model->conversation_id = get_post_meta($post->ID, '_conversation_id', true);
        $model->status = get_post_meta($post->ID, '_status', true);
        
        $model->exist = true;
        return $model;
    }

    /**
     * Get all by attribute
     */
    public static function find_by_attribute($attribute, $value)
    {
        $meta_keys = array('_send_to', '_attachment', '_conversation_id', '_status');
        
        if (in_array("_$attribute", $meta_keys)) {
            $posts = get_posts(array(
                'post_type' => self::POST_TYPE,
                'meta_key' => "_$attribute",
                'meta_value' => $value,
                'posts_per_page' => -1,
                'suppress_filters' => true,
            ));
        } else {
            // Direct post field
            $args = array(
                'post_type' => self::POST_TYPE,
                'posts_per_page' => -1,
                'suppress_filters' => true,
            );
            
            if ($attribute === 'post_author') {
                $args['author'] = $value;
            }
            
            $posts = get_posts($args);
        }
        
        return array_map(array(__CLASS__, 'from_post'), $posts ?: array());
    }
    
    /**
     * Find first message in a conversation
     */
    public static function find_first_in_conversation($conversation_id)
    {
        $posts = get_posts(array(
            'post_type' => self::POST_TYPE,
            'meta_key' => '_conversation_id',
            'meta_value' => $conversation_id,
            'posts_per_page' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
        ));
        
        if (empty($posts)) {
            return null;
        }
        
        return self::from_post($posts[0]);
    }

    /**
     * Validate message
     */
    public function validate()
    {
        $this->errors = array();
        
        // Set rules based on reply vs new message
        // Reply = conversation_id is set, New = conversation_id is empty
        if (!empty($this->conversation_id)) {
            // Reply to existing conversation - only content required
            $this->rules = array(
                'content' => 'required',
            );
        } else {
            // New message - need send_to, subject, content
            $this->rules = array(
                'subject' => 'required',
                'content' => 'required',
                'send_to' => 'required'
            );
        }
        
        // Normalize recipients to a comma-separated list of user IDs (strip self, dedupe)
        if (is_array($this->send_to)) {
            $this->send_to = implode(',', array_map('sanitize_text_field', $this->send_to));
        }

        if (!empty($this->send_to) && is_string($this->send_to)) {
            $raw_ids = explode(',', $this->send_to);
            $recipient_ids = array();
            foreach ($raw_ids as $token) {
                $token = trim($token);
                if ($token === '') {
                    continue;
                }
                if (ctype_digit($token)) {
                    $recipient_ids[] = intval($token);
                    continue;
                }
                $user = get_user_by('login', $token);
                if ($user && isset($user->ID)) {
                    $recipient_ids[] = intval($user->ID);
                }
            }

            $recipient_ids = array_values(array_unique($recipient_ids));

            $current_user_id = get_current_user_id();
            $recipient_ids = array_values(array_filter($recipient_ids, function ($id) use ($current_user_id) {
                return $id > 0 && $id !== $current_user_id;
            }));

            if (empty($recipient_ids)) {
                $this->errors['send_to'] = __('You cannot send a message to yourself. Please choose a recipient.', 'private_messaging');
            } else {
                $this->send_to = implode(',', $recipient_ids);
            }
        }
        
        // Simple validation
        foreach ($this->rules as $field => $rule) {
            if ($rule === 'required' && empty($this->$field)) {
                $this->errors[$field] = sprintf(__('%s ist erforderlich', 'private_messaging'), ucfirst($field));
            }
        }
        
        return empty($this->errors);
    }

    /**
     * Save message (create or update)
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        // Auto-fill sender if not set
        if (!$this->exist && empty($this->send_from)) {
            $this->send_from = get_current_user_id();
        }

        if ($this->exist) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * Insert new message
     */
    private function insert()
    {
        $post_data = array(
            'post_type' => self::POST_TYPE,
            'post_title' => sanitize_text_field($this->subject ?? ''),
            'post_content' => wp_kses_post($this->content ?? ''),
            'post_author' => absint($this->send_from),
            'post_parent' => absint($this->reply_to ?? 0),
            'post_status' => $this->post_status,
            'post_date' => $this->date ?? current_time('mysql'),
        );

        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            $this->errors['save'] = $post_id->get_error_message();
            return false;
        }

        // Save meta
        update_post_meta($post_id, '_send_to', absint($this->send_to));
        update_post_meta($post_id, '_attachment', sanitize_text_field($this->attachment ?? ''));
        update_post_meta($post_id, '_conversation_id', absint($this->conversation_id ?? 0));
        update_post_meta($post_id, '_status', sanitize_text_field($this->status ?? self::UNREAD));

        $this->id = $post_id;
        $this->exist = true;
        return $this->id;
    }

    /**
     * Update existing message
     */
    private function update()
    {
        $post_data = array(
            'ID' => $this->id,
            'post_title' => sanitize_text_field($this->subject ?? ''),
            'post_content' => wp_kses_post($this->content ?? ''),
            'post_status' => $this->post_status,
        );

        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            $this->errors['save'] = $result->get_error_message();
            return false;
        }

        // Update meta
        update_post_meta($this->id, '_send_to', absint($this->send_to));
        update_post_meta($this->id, '_attachment', sanitize_text_field($this->attachment ?? ''));
        update_post_meta($this->id, '_conversation_id', absint($this->conversation_id ?? 0));
        update_post_meta($this->id, '_status', sanitize_text_field($this->status ?? self::UNREAD));

        return $this->id;
    }

    /**
     * Delete message
     */
    public function delete()
    {
        if (!$this->exist || !$this->id) {
            return false;
        }
        
        return wp_delete_post($this->id, true) !== false;
    }

    /**
     * Set data from array (import)
     */
    public function import($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Export data to array
     */
    public function export()
    {
        return array(
            'id' => $this->id,
            'subject' => $this->subject,
            'content' => $this->content,
            'send_to' => $this->send_to,
            'send_from' => $this->send_from,
            'reply_to' => $this->reply_to,
            'status' => $this->status,
            'date' => $this->date,
            'post_status' => $this->post_status,
            'attachment' => $this->attachment,
            'conversation_id' => $this->conversation_id,
        );
    }

    /**
     * Get error
     */
    public function get_error($field = null)
    {
        if ($field) {
            return $this->errors[$field] ?? '';
        }
        return $this->errors;
    }

    /**
     * Check if has error
     */
    public function has_error($field)
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get user display name
     */
    public static function get_name($user_id)
    {
        $userdata = get_userdata($user_id);
        if (!$userdata) {
            return '';
        }
        
        $name = trim($userdata->first_name . ' ' . $userdata->last_name);
        return !empty($name) ? $name : $userdata->user_login;
    }

    /**
     * Send message to user
     */
    public static function send($user_id, $conversation_id, $data)
    {
        $message = new self();
        $message->import($data);
        $message->send_to = $user_id;
        $message->conversation_id = $conversation_id;
        $message->status = self::UNREAD;
        
        if ($message->save()) {
            do_action('mm_message_sent', $message);
            return $message->id;
        }
        
        return false;
    }

    /**
     * Reply to message
     */
    public static function reply($user_id, $message_id, $conversation_id, $data)
    {
        $original = self::find($message_id);
        if (!$original) {
            return false;
        }

        $message = new self();
        $message->import($data);
        $message->send_to = $user_id;
        $message->conversation_id = $conversation_id;
        $message->status = self::UNREAD;
        $message->reply_to = $message_id;
        $message->subject = __("Re:", 'private_messaging') . ' ' . $original->subject;
        
        if ($message->save()) {
            do_action('mm_message_sent', $message);
            return $message->id;
        }
        
        return false;
    }

    /**
     * Factory method (compatibility)
     */
    public static function model($class_name = null)
    {
        return new self();
    }
}