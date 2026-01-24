<?php

/**
 * MM_Conversation_Model - Modern WordPress-Native Implementation
 * Replaces IG_DB_Model_Ex with native $wpdb prepared statements
 * 
 * @author: Hoang Ngo (Modernized)
 * @package: PrivateMessaging
 */
class MM_Conversation_Model
{
    const LOCK = -1, UNLOCK = 1;
    const TABLE = 'mm_conversation';

    // Properties
    public $id;
    public $date_created;
    public $message_count;
    public $message_index;
    public $user_index;
    public $send_from;
    public $site_id;
    public $status;
    
    public $exist = false;
    private $errors = array();

    /**
     * Find conversation by ID
     */
    public static function find($id)
    {
        global $wpdb;
        $table = self::get_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return self::from_array($result);
    }

    /**
     * Find all conversations
     */
    public static function find_all()
    {
        global $wpdb;
        $table = self::get_table();
        
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY date_created DESC", ARRAY_A);
        
        if (!$results) {
            return array();
        }

        return array_map(array(__CLASS__, 'from_array'), $results);
    }

    /**
     * Find all by IDs
     */
    public static function find_all_by_ids($ids, $force = false, $ignore = false, $orderby = 'date_created DESC')
    {
        if (!is_array($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        if (empty($ids)) {
            return array();
        }

        global $wpdb;
        $table = self::get_table();
        
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = "SELECT * FROM $table WHERE id IN ($placeholders) ORDER BY $orderby";
        
        $results = $wpdb->get_results(
            $wpdb->prepare($sql, ...$ids),
            ARRAY_A
        );

        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Convert array to model
     */
    private static function from_array($data)
    {
        $model = new self();
        foreach ($data as $key => $value) {
            if (property_exists($model, $key)) {
                $model->$key = $value;
            }
        }
        $model->exist = true;
        return $model;
    }

    /**
     * Get current user's conversations (inbox)
     */
    public static function get_conversation()
    {
        global $wpdb;
        $per_page = mmg()->setting()->per_page;
        $paged = mmg()->get('mpaged', 1);
        $offset = ($paged - 1) * $per_page;
        
        $conv_table = self::get_table();
        $status_table = MM_Message_Status_Model::get_table();
        
        $total_pages = ceil(self::count_all() / $per_page);
        mmg()->global['conversation_total_pages'] = $total_pages;

        $sql = $wpdb->prepare(
            "SELECT conversation.* FROM $conv_table conversation
            INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
            WHERE mstat.user_id = %d 
            AND mstat.status IN (%d, %d)
            AND conversation.site_id = %d 
            AND (conversation.message_count > 1 OR conversation.send_from != %d)
            GROUP BY conversation.id 
            ORDER BY conversation.date_created DESC 
            LIMIT %d, %d",
            get_current_user_id(),
            MM_Message_Status_Model::STATUS_READ,
            MM_Message_Status_Model::STATUS_UNREAD,
            get_current_blog_id(),
            get_current_user_id(),
            $offset,
            $per_page
        );

        $results = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Get archived conversations
     */
    public static function get_archive()
    {
        global $wpdb;
        $per_page = mmg()->setting()->per_page;
        $paged = mmg()->get('mpaged', 1);
        $offset = ($paged - 1) * $per_page;
        
        $conv_table = self::get_table();
        $status_table = MM_Message_Status_Model::get_table();
        
        $total_pages = ceil(self::count_all() / $per_page);
        mmg()->global['conversation_total_pages'] = $total_pages;

        $sql = $wpdb->prepare(
            "SELECT conversation.* FROM $conv_table conversation
            INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
            WHERE mstat.user_id = %d 
            AND mstat.status = %d 
            AND conversation.site_id = %d
            GROUP BY conversation.id 
            ORDER BY conversation.date_created DESC 
            LIMIT %d, %d",
            get_current_user_id(),
            MM_Message_Status_Model::STATUS_ARCHIVE,
            get_current_blog_id(),
            $offset,
            $per_page
        );

        $results = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Get unread conversations for user
     */
    public static function get_unread($user_id = null)
    {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        global $wpdb;
        $per_page = mmg()->setting()->per_page;
        $paged = mmg()->get('mpaged', 1);
        $offset = ($paged - 1) * $per_page;
        
        $conv_table = self::get_table();
        $status_table = MM_Message_Status_Model::get_table();
        
        $total_pages = ceil(self::count_unread() / $per_page);
        mmg()->global['conversation_total_pages'] = $total_pages;

        $sql = $wpdb->prepare(
            "SELECT conversation.* FROM $conv_table conversation
            INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
            WHERE mstat.user_id = %d 
            AND mstat.status IN (%d, %d)
            AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)
            AND (conversation.message_count > 1 OR conversation.send_from != %d)
            GROUP BY conversation.id 
            ORDER BY conversation.date_created DESC 
            LIMIT %d, %d",
            $user_id,
            MM_Message_Status_Model::STATUS_READ,
            MM_Message_Status_Model::STATUS_UNREAD,
            get_current_blog_id(),
            $user_id,
            $offset,
            $per_page
        );

        $results = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Get read conversations
     */
    public static function get_read()
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $per_page = mmg()->setting()->per_page;
        $paged = mmg()->get('mpaged', 1);
        $offset = ($paged - 1) * $per_page;
        
        $conv_table = self::get_table();
        $status_table = MM_Message_Status_Model::get_table();
        
        $total_pages = ceil(self::count_read() / $per_page);
        mmg()->global['conversation_total_pages'] = $total_pages;

        $sql = $wpdb->prepare(
            "SELECT conversation.* FROM $conv_table conversation
            INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
            WHERE mstat.user_id = %d 
            AND mstat.status = %d
            AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)
            AND (conversation.message_count > 1 OR conversation.send_from != %d)
            GROUP BY conversation.id 
            ORDER BY conversation.date_created DESC 
            LIMIT %d, %d",
            $user_id,
            MM_Message_Status_Model::STATUS_READ,
            get_current_blog_id(),
            $user_id,
            $offset,
            $per_page
        );

        $results = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Get conversations sent by user
     */
    public static function get_sent()
    {
        global $wpdb;
        $per_page = mmg()->setting()->per_page;
        $paged = mmg()->get('mpaged', 1);
        $offset = ($paged - 1) * $per_page;
        
        $conv_table = self::get_table();
        $status_table = MM_Message_Status_Model::get_table();
        
        $total_pages = ceil(self::count_all() / $per_page);
        mmg()->global['conversation_total_pages'] = $total_pages;

        $messages = MM_Message_Model::find_by_attribute('send_from', get_current_user_id());
        $conversation_ids = array_unique(array_filter(array_column($messages, 'conversation_id')));
        
        if (empty($conversation_ids)) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($conversation_ids), '%d'));
        $prepared_values = array_merge(
            array(MM_Message_Status_Model::STATUS_READ, MM_Message_Status_Model::STATUS_UNREAD),
            $conversation_ids,
            array(get_current_blog_id(), $offset, $per_page)
        );
        
        $sql = $wpdb->prepare(
            "SELECT conversation.* FROM $conv_table conversation
            INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
            WHERE mstat.status IN (%d, %d)
            AND conversation.id IN ($placeholders)
            AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)
            GROUP BY conversation.id 
            ORDER BY conversation.date_created DESC 
            LIMIT %d, %d",
            ...$prepared_values
        );

        $results = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Count all conversations for current user
     */
    public static function count_all()
    {
        $cache_key = 'mm_count_all_' . get_current_user_id();
        
        if (false === ($count = get_transient($cache_key))) {
            global $wpdb;
            $conv_table = self::get_table();
            $status_table = MM_Message_Status_Model::get_table();

            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT conversation.id)
                    FROM $conv_table conversation
                    INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
                    WHERE mstat.user_id = %d 
                    AND mstat.status IN (%d, %d)
                    AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)
                    AND (conversation.message_count > 1 OR conversation.send_from != %d)",
                    get_current_user_id(),
                    MM_Message_Status_Model::STATUS_READ,
                    MM_Message_Status_Model::STATUS_UNREAD,
                    get_current_blog_id(),
                    get_current_user_id()
                )
            );

            set_transient($cache_key, $count, HOUR_IN_SECONDS);
        }

        return $count;
    }

    /**
     * Count unread for current user
     */
    public static function count_unread($no_cache = false)
    {
        $cache_key = 'mm_count_unread_' . get_current_user_id();
        
        if ($no_cache || false === ($count = get_transient($cache_key))) {
            global $wpdb;
            $conv_table = self::get_table();
            $status_table = MM_Message_Status_Model::get_table();

            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT conversation.id)
                    FROM $conv_table conversation
                    INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
                    WHERE mstat.user_id = %d 
                    AND mstat.status = %d
                    AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)
                    AND (conversation.message_count > 1 OR conversation.send_from != %d)",
                    get_current_user_id(),
                    MM_Message_Status_Model::STATUS_UNREAD,
                    get_current_blog_id(),
                    get_current_user_id()
                )
            );

            set_transient($cache_key, $count, HOUR_IN_SECONDS);
        }

        return $count;
    }

    /**
     * Count read conversations
     */
    public static function count_read($no_cache = false)
    {
        $cache_key = 'mm_count_read_' . get_current_user_id();
        
        if ($no_cache || false === ($count = get_transient($cache_key))) {
            global $wpdb;
            $conv_table = self::get_table();
            $status_table = MM_Message_Status_Model::get_table();

            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT conversation.id)
                    FROM $conv_table conversation
                    INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
                    WHERE mstat.user_id = %d 
                    AND mstat.status = %d
                    AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)
                    AND (conversation.message_count > 1 OR conversation.send_from != %d)",
                    get_current_user_id(),
                    MM_Message_Status_Model::STATUS_READ,
                    get_current_blog_id(),
                    get_current_user_id()
                )
            );

            set_transient($cache_key, $count, HOUR_IN_SECONDS);
        }

        return $count;
    }

    /**
     * Count archived conversations for current user
     */
    public static function count_archive($no_cache = false)
    {
        $cache_key = 'mm_count_archive_' . get_current_user_id();

        if ($no_cache || false === ($count = get_transient($cache_key))) {
            global $wpdb;
            $conv_table = self::get_table();
            $status_table = MM_Message_Status_Model::get_table();

            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT conversation.id)
                    FROM $conv_table conversation
                    INNER JOIN $status_table mstat ON mstat.conversation_id = conversation.id
                    WHERE mstat.user_id = %d
                    AND mstat.status = %d
                    AND (conversation.site_id = %d OR conversation.site_id = 0 OR conversation.site_id IS NULL)",
                    get_current_user_id(),
                    MM_Message_Status_Model::STATUS_ARCHIVE,
                    get_current_blog_id()
                )
            );

            set_transient($cache_key, $count, HOUR_IN_SECONDS);
        }

        return $count;
    }

    /**
     * Count conversations the current user sent
     */
    public static function count_sent($no_cache = false)
    {
        $conversations = self::get_sent();
        return is_array($conversations) ? count($conversations) : 0;
    }

    /**
     * Save conversation (insert or update)
     */
    public function save()
    {
        global $wpdb;
        $table = self::get_table();

        if ($this->exist && $this->id) {
            return $wpdb->update(
                $table,
                array(
                    'message_count' => absint($this->message_count),
                    'message_index' => sanitize_text_field($this->message_index ?? ''),
                    'user_index' => sanitize_text_field($this->user_index ?? ''),
                    'status' => absint($this->status ?? 0),
                ),
                array('id' => $this->id),
                array('%d', '%s', '%s', '%d'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $table,
                array(
                    'date_created' => $this->date_created ?? current_time('mysql'),
                    'message_count' => absint($this->message_count ?? 0),
                    'message_index' => sanitize_text_field($this->message_index ?? ''),
                    'user_index' => sanitize_text_field($this->user_index ?? ''),
                    'send_from' => absint($this->send_from ?? get_current_user_id()),
                    'site_id' => absint($this->site_id ?? get_current_blog_id()),
                    'status' => absint($this->status ?? 0),
                ),
                array('%s', '%d', '%s', '%s', '%d', '%d', '%d')
            );
            
            if ($result) {
                $this->id = $wpdb->insert_id;
                $this->exist = true;
                $this->after_save();
            }
            return $result;
        }
    }

    /**
     * Delete conversation
     */
    public function delete()
    {
        if (!$this->exist || !$this->id) {
            return false;
        }

        // Clean up attachments folder for this conversation
        if (class_exists('PM_Attachment_Handler')) {
            PM_Attachment_Handler::delete_conversation_attachments($this->id);
        }

        global $wpdb;
        $table = self::get_table();
        
        $deleted = $wpdb->delete($table, array('id' => $this->id), array('%d')) > 0;

        if ($deleted) {
            $user_ids = array_filter(explode(',', $this->user_index ?? ''));
            if (!empty($this->send_from)) {
                $user_ids[] = (int) $this->send_from;
            }
            foreach ($user_ids as $user_id) {
                delete_transient('mm_count_all_' . $user_id);
                delete_transient('mm_count_read_' . $user_id);
                delete_transient('mm_count_unread_' . $user_id);
                delete_transient('mm_count_archive_' . $user_id);
                delete_transient('mm_count_sent_' . $user_id);
            }
        }

        return $deleted;
    }

    /**
     * Update message index
     */
    public function update_index($message_id, $remove = false)
    {
        $index = array_filter(explode(',', $this->message_index ?? ''));
        
        if ($remove) {
            $key = array_search($message_id, $index);
            if ($key !== false) {
                unset($index[$key]);
            }
        } else {
            $index[] = $message_id;
        }
        
        $this->message_index = implode(',', $index);

        // Update user index and message count
        $messages = $this->get_messages();
        $user_ids = array();
        
        foreach ($messages as $msg) {
            $user_ids[] = $msg->send_from;
            $send_to = array_filter(explode(',', $msg->send_to ?? ''));
            $user_ids = array_merge($send_to, $user_ids);
        }
        
        $user_ids = array_unique(array_filter($user_ids));
        $this->user_index = implode(',', $user_ids);
        $this->message_count = count($messages);

        $this->save();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MM_Conversation_Model::update_index conv=' . $this->id . ' user_index=' . $this->user_index . ' message_count=' . $this->message_count . ' message_id=' . $message_id . ' remove=' . ($remove ? '1' : '0'));
        }
    }

    /**
     * Get messages in conversation
     */
    public function get_messages()
    {
        return MM_Message_Model::find_by_ids($this->message_index, false, false, 'ID DESC');
    }

    /**
     * Get last message
     */
    public function get_last_message()
    {
        $ids = array_filter(explode(',', $this->message_index ?? ''));
        $id = end($ids);
        return $id ? MM_Message_Model::find($id) : null;
    }

    /**
     * Get first message
     */
    public function get_first_message()
    {
        $ids = array_filter(explode(',', $this->message_index ?? ''));
        $id = reset($ids);
        return $id ? MM_Message_Model::find($id) : null;
    }

    /**
     * Get users in conversation
     */
    public function get_users()
    {
        $ids = array_filter(explode(',', $this->user_index ?? ''));
        if (empty($ids)) {
            return array();
        }

        return get_users(array('include' => $ids));
    }

    /**
     * Get users in conversation as objects
     */
    public function get_users_in()
    {
        $ids = array_filter(array_unique(explode(',', $this->user_index ?? '')));
        $users = array();
        foreach ($ids as $id) {
            $user = get_user_by('id', $id);
            if ($user) {
                $users[] = $user;
            }
        }
        return $users;
    }

    /**
     * Get current user's status for this conversation
     */
    public function get_current_status()
    {
        global $wpdb;
        $status_table = MM_Message_Status_Model::get_table();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $status_table WHERE conversation_id = %d AND user_id = %d ORDER BY date_created DESC LIMIT 1",
                $this->id,
                get_current_user_id()
            ),
            ARRAY_A
        );

        if (!$result) {
            return false;
        }

        return MM_Message_Status_Model::from_array($result);
    }

    /**
     * Check if conversation is archived
     */
    public function is_archive()
    {
        $status = $this->get_current_status();
        return $status && $status->status == MM_Message_Status_Model::STATUS_ARCHIVE;
    }

    /**
     * Check if conversation is locked
     */
    public function is_lock()
    {
        return $this->status == self::LOCK;
    }

    /**
     * Check if has unread messages
     */
    public function has_unread()
    {
        $status = $this->get_current_status();
        return $status && $status->status == MM_Message_Status_Model::STATUS_UNREAD;
    }

    /**
     * Mark as read
     */
    public function mark_as_read()
    {
        $status = $this->get_current_status();
        if ($status) {
            $status->status = MM_Message_Status_Model::STATUS_READ;
            $status->save();
        }
    }

    /**
     * Clear transient caches
     */
    private function after_save()
    {
        $user_ids = array_filter(explode(',', $this->user_index ?? ''));
        if (!empty($this->send_from)) {
            $user_ids[] = (int) $this->send_from;
        }
        foreach ($user_ids as $user_id) {
            delete_transient('mm_count_all_' . $user_id);
            delete_transient('mm_count_read_' . $user_id);
            delete_transient('mm_count_unread_' . $user_id);
            delete_transient('mm_count_archive_' . $user_id);
            delete_transient('mm_count_sent_' . $user_id);
        }
    }

    /**
     * Export to array
     */
    public function export()
    {
        return array(
            'id' => $this->id,
            'date_created' => $this->date_created,
            'message_count' => $this->message_count,
            'message_index' => $this->message_index,
            'user_index' => $this->user_index,
            'send_from' => $this->send_from,
            'site_id' => $this->site_id,
            'status' => $this->status,
        );
    }

    /**
     * Search conversations by subject or participant name
     * 
     * @param string $query Search query
     * @return array Conversation models matching the query
     */
    public static function search($query)
    {
        global $wpdb;
        
        if (empty($query)) {
            return self::get_conversation();
        }

        $per_page = mmg()->setting()->per_page;
        $paged = mmg()->get('mpaged', 1);
        $offset = ($paged - 1) * $per_page;
        
        $conv_table = self::get_table();
        $status_table = MM_Message_Status_Model::get_table();
        $posts_table = $wpdb->posts;
        $postmeta_table = $wpdb->postmeta;
        
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        
        // Step 1: Find all posts matching the search term
        $post_ids_sql = $wpdb->prepare(
            "SELECT ID FROM {$posts_table} 
            WHERE post_type = %s AND (post_title LIKE %s OR post_content LIKE %s) 
            AND post_status = %s",
            MM_Message_Model::POST_TYPE,
            $search_term,
            $search_term,
            'publish'
        );
        
        $post_ids = $wpdb->get_col($post_ids_sql);
        error_log('MM Search SQL: Found ' . count($post_ids) . ' posts: ' . implode(',', $post_ids));
        
        if (empty($post_ids)) {
            mmg()->global['conversation_total_pages'] = 0;
            return array();
        }
        
        // Step 2: Get conversation IDs from the post metadata
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        $conv_ids_sql = $wpdb->prepare(
            "SELECT DISTINCT CAST(meta_value AS UNSIGNED) as conv_id 
            FROM {$postmeta_table} 
            WHERE post_id IN ($placeholders)
            AND meta_key = %s 
            AND meta_value != ''",
            array_merge($post_ids, array('_conversation_id'))
        );
        
        $conversation_ids = $wpdb->get_col($conv_ids_sql);
        error_log('MM Search SQL: Found conversation IDs: ' . implode(',', $conversation_ids));
        
        if (empty($conversation_ids)) {
            mmg()->global['conversation_total_pages'] = 0;
            return array();
        }
        
        // Step 3: Get full conversation objects - search should show ALL user's conversations
        $current_user = get_current_user_id();
        error_log('MM Search SQL: Current user = ' . $current_user);
        
        // Debug: Check conversation details
        $debug_conv_sql = "SELECT * FROM {$conv_table} WHERE id IN (" . implode(',', $conversation_ids) . ")";
        $debug_convs = $wpdb->get_results($debug_conv_sql);
        error_log('MM Search SQL: Conversation details: ' . print_r($debug_convs, true));
        
        // Use user_index to find conversations where current user is a participant
        // user_index contains comma-separated user IDs like "2,1" or "1,3,5"
        $conv_placeholders = implode(',', array_fill(0, count($conversation_ids), '%d'));
        $sql = $wpdb->prepare(
            "SELECT DISTINCT conversation.* 
            FROM {$conv_table} conversation
            WHERE conversation.id IN ($conv_placeholders)
            AND FIND_IN_SET(%d, conversation.user_index) > 0
            AND conversation.site_id = %d
            ORDER BY conversation.date_created DESC 
            LIMIT %d, %d",
            array_merge($conversation_ids, array(
                $current_user,
                get_current_blog_id(),
                $offset,
                $per_page
            ))
        );

        $results = $wpdb->get_results($sql, ARRAY_A);
        error_log('MM Search SQL: Final results = ' . count($results) . ' conversations');
        
        // Calculate total pages for pagination
        $count_sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT conversation.id) as total
            FROM {$conv_table} conversation
            INNER JOIN {$status_table} mstat ON mstat.conversation_id = conversation.id
            WHERE conversation.id IN ($conv_placeholders)
            AND mstat.user_id = %d 
            AND mstat.status IN (%d, %d)
            AND conversation.site_id = %d",
            array_merge($conversation_ids, array(
                get_current_user_id(),
                MM_Message_Status_Model::STATUS_READ,
                MM_Message_Status_Model::STATUS_UNREAD,
                get_current_blog_id()
            ))
        );
        
        $count_result = $wpdb->get_var($count_sql);
        $total_pages = ceil($count_result / $per_page);
        mmg()->global['conversation_total_pages'] = $total_pages;

        return array_map(array(__CLASS__, 'from_array'), $results ?: array());
    }

    /**
     * Get table name
     */
    public static function get_table()
    {
        global $wpdb;
        return $wpdb->base_prefix . self::TABLE;
    }

    /**
     * Factory method
     */
    public static function model($class_name = null)
    {
        return new self();
    }
}
