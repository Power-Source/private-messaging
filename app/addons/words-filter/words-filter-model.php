<?php

/**
 * @author:Hoang Ngo
 * Modern replacement using ClassicPress Options API
 */
class Words_Filter_Model
{
    public $replacer = '*';
    public $block_list = array();

    protected $option_key = 'mm_words_filter';

    private static $_instance;

    public function __construct()
    {
        if (!is_array($this->block_list)) {
            $this->block_list = array();
        }
        
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
     * Save settings to ClassicPress options API
     * @return bool
     */
    public function save()
    {
        $settings = array(
            'replacer'   => $this->replacer,
            'block_list' => $this->block_list,
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
                if ($key === 'block_list') {
                    // Validate block_list is an array
                    $this->{$key} = is_array($value) ? $value : array();
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
            'replacer'   => $this->replacer,
            'block_list' => $this->block_list,
        );
    }

    /**
     * Get singleton instance
     * @return Words_Filter_Model
     */
    public static function get_instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}