<?php

/**
 * MM_Message_Status_Model - Modern WordPress-Native Implementation
 * Replaces IG_DB_Model_Ex with native $wpdb prepared statements
 * 
 * @author: Hoang Ngo (Modernized)
 * @package: PrivateMessaging
 */
class MM_Message_Status_Model
{
    const STATUS_UNREAD = 0, STATUS_READ = 1, STATUS_ARCHIVE = -1, STATUS_DELETE = -2, STATUS_LOCK = -3;
    const TYPE_MESSAGE = 1, TYPE_CONVERSATION = 2;
    const TABLE = 'mm_status';

    // Properties
    public $id;
    public $conversation_id;
    public $message_id;
    public $user_id;
    public $status;
    public $date_created;
    public $type;
    
    public $exist = false;

    /**
     * Find status by ID
     */
    public static function find($id)
    {
        global $wpdb;
        $table = self::get_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        return $result ? self::from_array($result) : null;
    }

    /**
     * Find by attributes
     */
    public static function find_one_with_attributes($attributes, $orderby = 'id DESC')
    {
        global $wpdb;
        $table = self::get_table();
        
        $where = '';
        $values = array();
        
        foreach ($attributes as $key => $value) {
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $where .= "$key = %d";
            $values[] = $value;
        }

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT 1", ...$values),
            ARRAY_A
        );

        return $result ? self::from_array($result) : null;
    }

    /**
     * Convert array to model
     */
    public static function from_array($data)
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
     * Save status (insert or update)
     */
    public function save()
    {
        global $wpdb;
        $table = self::get_table();

        $this->date_created = $this->date_created ?? current_time('mysql');

        if ($this->exist && $this->id) {
            return $wpdb->update(
                $table,
                array(
                    'status' => absint($this->status),
                    'date_created' => $this->date_created,
                ),
                array('id' => $this->id),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $table,
                array(
                    'conversation_id' => absint($this->conversation_id ?? 0),
                    'message_id' => absint($this->message_id ?? 0),
                    'user_id' => absint($this->user_id),
                    'status' => absint($this->status),
                    'date_created' => $this->date_created,
                    'type' => absint($this->type ?? self::TYPE_CONVERSATION),
                ),
                array('%d', '%d', '%d', '%d', '%s', '%d')
            );
            
            if ($result) {
                $this->id = $wpdb->insert_id;
                $this->exist = true;
            }
            
            return $result;
        }
    }

    /**
     * Delete status
     */
    public function delete()
    {
        if (!$this->exist || !$this->id) {
            return false;
        }

        global $wpdb;
        $table = self::get_table();
        
        return $wpdb->delete($table, array('id' => $this->id), array('%d')) > 0;
    }

    /**
     * Update conversation status for user
     */
    public function status($conversation_id, $new_status, $user_id)
    {
        $model = self::find_one_with_attributes(array(
            'conversation_id' => $conversation_id,
            'user_id' => $user_id
        ));
        
        if (!$model) {
            $model = new self();
            $model->conversation_id = $conversation_id;
            $model->user_id = $user_id;
        }
        
        $model->status = $new_status;
        $model->save();
    }

    /**
     * Export to array
     */
    public function export()
    {
        return array(
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'message_id' => $this->message_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'date_created' => $this->date_created,
            'type' => $this->type,
        );
    }

    /**
     * Get error
     */
    public function get_error($field = null)
    {
        return '';
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