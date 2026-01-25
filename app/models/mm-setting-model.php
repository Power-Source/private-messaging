<?php

/**
 * Author: Hoang Ngo
 * Modern replacement using ClassicPress Options API
 */
class MM_Setting_Model
{
    protected $option_key = 'mm_settings';

    public $noti_subject = '';
    public $noti_content = '';

    public $receipt_subject = "";
    public $receipt_content = "";

    public $enable_receipt = 1;
    public $user_receipt = 1;

    public $per_page = 10;

    public $signup_text = "Sign up to become a registered member of the site";

    public $plugins;

    public $inbox_page;

    public $allow_attachment = false;

    public $storage_limit = 52428800; // 50MB in bytes (default)
    public $storage_unit = 'MB'; // MB or GB
    public $storage_unlimited = false;

    private static $_instance;
    
    /**
     * Error messages storage
     * @var array
     */
    protected $errors = array();

    public function __construct()
    {
        $this->noti_subject = "Du hast eine neue Nachricht von FROM_NAME auf SITE_NAME erhalten.";
        $this->noti_content = "FROM_NAME hat dir eine Nachricht auf SITE_NAME gesendet<br/><br/>

        FROM_MESSAGE
        <br/><br/>
        Überprüfe deine Nachrichten hier <a href='POST_LINK'>POST_LINK</a>
        ";

        $this->receipt_content = "Lieber FROM_NAME <br/><br/>
        Die Nachricht, die du an TO_NAME auf SITE_NAME gesendet hast, wurde gelesen.";
        $this->receipt_subject = "Die Nachricht, die du an TO_NAME auf SITE_NAME gesendet hast, wurde gelesen.";
        
        // Load settings from ClassicPress options
        $this->load_settings();
    }

    /**
     * Load settings from ClassicPress options API
     * @return void
     */
    public function load_settings()
    {
        $settings = get_option($this->option_key, array());
        
        if (!empty($settings) && is_array($settings)) {
            foreach ($settings as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * Alias for load_settings() to maintain backward compatibility
     * @return void
     */
    public function load()
    {
        $this->load_settings();
    }

    /**
     * Save settings to ClassicPress options API
     * @return bool
     */
    public function save()
    {
        $settings = array(
            'noti_subject'     => $this->noti_subject,
            'noti_content'     => $this->noti_content,
            'receipt_subject'  => $this->receipt_subject,
            'receipt_content'  => $this->receipt_content,
            'enable_receipt'   => $this->enable_receipt,
            'user_receipt'     => $this->user_receipt,
            'per_page'         => $this->per_page,
            'signup_text'      => $this->signup_text,
            'plugins'          => $this->plugins,
            'inbox_page'       => $this->inbox_page,
            'allow_attachment' => $this->allow_attachment,
            'storage_limit'    => $this->storage_limit,
            'storage_unit'     => $this->storage_unit,
            'storage_unlimited' => $this->storage_unlimited,
        );
        
        return update_option($this->option_key, $settings);
    }

    /**
     * Import data from array (e.g., from POST/form submission)
     * @param array $data
     * @return void
     */
    public function import($data = array())
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // Sanitize different field types
                if ($key === 'per_page' || $key === 'storage_limit') {
                    $this->{$key} = absint($value);
                } elseif (in_array($key, array('enable_receipt', 'user_receipt', 'allow_attachment', 'storage_unlimited'))) {
                    $this->{$key} = (bool) $value;
                } elseif (in_array($key, array('noti_subject', 'noti_content', 'receipt_subject', 'receipt_content'))) {
                    $this->{$key} = sanitize_textarea_field($value);
                } else {
                    $this->{$key} = sanitize_text_field($value);
                }
            }
        }
    }

    /**
     * Export settings as array
     * @return array
     */
    public function export()
    {
        return array(
            'noti_subject'     => $this->noti_subject,
            'noti_content'     => $this->noti_content,
            'receipt_subject'  => $this->receipt_subject,
            'receipt_content'  => $this->receipt_content,
            'enable_receipt'   => $this->enable_receipt,
            'user_receipt'     => $this->user_receipt,
            'per_page'         => $this->per_page,
            'signup_text'      => $this->signup_text,
            'plugins'          => $this->plugins,
            'inbox_page'       => $this->inbox_page,
            'allow_attachment' => $this->allow_attachment,
            'storage_limit'    => $this->storage_limit,
            'storage_unit'     => $this->storage_unit,
            'storage_unlimited' => $this->storage_unlimited,
        );
    }

    /**
     * Get singleton instance
     * @return MM_Setting_Model
     */
    public static function get_instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Add an error message for a field
     * @param string $field
     * @param string $message
     * @return void
     */
    public function add_error($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = array();
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Check if a field has errors
     * @param string $field
     * @return bool
     */
    public function has_error($field = null)
    {
        if (null === $field) {
            return !empty($this->errors);
        }
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Get error message for a field
     * @param string $field
     * @return string
     */
    public function get_error($field)
    {
        if (isset($this->errors[$field]) && !empty($this->errors[$field])) {
            return implode(', ', $this->errors[$field]);
        }
        return '';
    }

    /**
     * Get all errors
     * @return array
     */
    public function get_errors()
    {
        return $this->errors;
    }

    /**
     * Clear all errors
     * @return void
     */
    public function clear_errors()
    {
        $this->errors = array();
    }
}