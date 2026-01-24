<?php
/**
 * Author: Hoang Ngo
 * Modern replacement using WordPress Post API
 */

if (!class_exists('IG_Uploader_Model')) {
    class IG_Uploader_Model
    {
        const MODE_LITE = 1, MODE_EXTEND = 2;

        /**
         * @var int
         */
        public $id;
        /**
         * @var string
         */
        public $name;
        /**
         * @var string
         */
        public $content;
        /**
         * @var string
         */
        public $post_status;
        /**
         * @var string
         */
        public $url;
        /**
         * @var int - ID of the wordpress attachment post type
         */
        public $file;
        /**
         * @var int
         * ID of post which link to this
         */
        public $attach_to;

        /**
         * @var int
         * Lite - only need to upload a file
         * Extend - url/file and content will be filled
         */
        public $mode;

        /**
         * @var mix
         * Temp data, just use to hold the $_FILE if exist
         */
        public $file_upload;

        protected $table = 'iup_media';
        
        /**
         * Error messages storage
         * @var array
         */
        protected $errors = array();
        
        /**
         * Whether the record exists in database
         * @var bool
         */
        protected $exist = false;

        /**
         * Check if this model represents an existing record
         *
         * @return bool
         */
        public function exists(): bool
        {
            return (bool) $this->exist;
        }

        protected static $_instance;

        public function __construct()
        {
            $this->mode = self::MODE_EXTEND;
        }

        /**
         * Find by ID - load from WordPress post
         * @param int $id
         * @return IG_Uploader_Model|false
         */
        public static function find($id)
        {
            $post = get_post($id);
            if (!$post) {
                return false;
            }

            $model = new self();
            return $model->from_post($post);
        }

        /**
         * Convert WordPress post to model
         * @param WP_Post $post
         * @return IG_Uploader_Model
         */
        public function from_post($post)
        {
            $this->id = $post->ID;
            $this->name = $post->post_title;
            $this->content = $post->post_content;
            $this->post_status = $post->post_status;
            $this->attach_to = $post->post_parent;
            
            // Load meta values
            $this->url = get_post_meta($post->ID, '_url', true);
            $this->file = get_post_meta($post->ID, '_file', true);
            
            $this->exist = true;
            return $this;
        }

        public function before_validate(): void
        {
            if ($this->mode == self::MODE_LITE) {
                //todo
            } else {
                // Validation handled in validate()
            }
        }

        public function after_save(): void
        {
            if (is_array($this->file_upload)) {
                //do the upload
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $media_id = media_handle_sideload($this->file_upload['file'], $this->id, $this->content, array(
                    'post_status' => 'inherit'
                ));
                if (is_wp_error($media_id)) {
                    //todo log
                    //return $media_id;
                } else {
                    //all good, store the value now
                    update_post_meta($this->id, '_file', $media_id);
                }
            }
        }

        public function before_save()
        {
            $this->post_status = 'publish';

            if (is_array($this->file_upload) && isset($this->file_upload['file']['name']) && !empty($this->file_upload['file']['name'])) {
                $this->name = $this->file_upload['file']['name'];
            } elseif (filter_var($this->file, FILTER_VALIDATE_INT)) {
                //id passed
                $this->name = pathinfo(wp_get_attachment_url($this->file), PATHINFO_BASENAME);
            } else {
                $this->name = __('Link', ig_uploader()->domain);
            }
        }

        /**
         * Validate model
         * @return bool
         */
        public function validate()
        {
            $this->clear_errors();
            $this->before_validate();
            
            if ($this->after_validate()) {
                return true;
            }
            return false;
        }

        protected function after_validate(): bool
        {
            if ($this->mode == self::MODE_EXTEND) {
                if (($this->exist && empty($this->file)) || !$this->exist) {
                    //we require neither url or file
                    if (empty($this->url) && empty($this->file)) {
                        $this->add_error('file', __("Url or File required", ig_uploader()->domain));
                        $this->add_error('url', __("Url or File required", ig_uploader()->domain));
                    }
                }
            }

            if (!empty($this->errors)) {
                return false;
            }
            return true;
        }

        /**
         * Save model to database
         * @return bool|int
         */
        public function save()
        {
            if (!$this->validate()) {
                return false;
            }

            $this->before_save();

            $post_data = array(
                'post_title'   => $this->name,
                'post_content' => $this->content,
                'post_status'  => $this->post_status,
                'post_parent'  => $this->attach_to,
            );

            if ($this->id && $this->exist) {
                // Update existing post
                $post_data['ID'] = $this->id;
                $result = wp_update_post($post_data, true);
            } else {
                // Insert new post
                $result = wp_insert_post($post_data, true);
            }

            if (is_wp_error($result)) {
                $this->add_error('save', $result->get_error_message());
                return false;
            }

            $this->id = $result;
            
            // Save meta values
            if (!empty($this->url)) {
                update_post_meta($this->id, '_url', sanitize_url($this->url));
            }
            if (!empty($this->file)) {
                update_post_meta($this->id, '_file', absint($this->file));
            }

            $this->after_save();
            return $this->id;
        }

        /**
         * Delete model
         * @param bool $force_delete
         * @return bool
         */
        public function delete($force_delete = true)
        {
            if (!$this->id) {
                return false;
            }

            $result = wp_delete_post($this->id, $force_delete);
            return !is_wp_error($result) && $result !== false;
        }

        function get_max_file_upload()
        {
            $max_upload = (int)(ini_get('upload_max_filesize'));
            $max_post = (int)(ini_get('post_max_size'));
            $memory_limit = (int)(ini_get('memory_limit'));
            $upload_mb = min($max_upload, $max_post, $memory_limit);

            return $upload_mb;
        }

        public function mime_to_icon($mime = '')
        {
            if (empty($mime)) {
                $mime = get_post_mime_type($this->file);
            }
            $type = explode('/', $mime);
            $type = array_shift($type);
            $image = match ($type) {
                'image' => '<i class="glyphicon glyphicon-picture"></i>',
                'video' => '<i class="glyphicon glyphicon-film"></i>',
                'text' => '<i class="glyphicon glyphicon-font"></i>',
                'audio' => '<i class="glyphicon glyphicon-volume-up"></i>',
                'application' => '<i class="glyphicon glyphicon-hdd"></i>',
                default => '',
            };

            if (empty($image)) {
                if (!empty($this->url)) {
                    $image = '<i class="glyphicon glyphicon-globe"></i>';
                } else {
                    $image = '<i class="glyphicon glyphicon-file"></i>';
                }
            }

            return $image;
        }

        /**
         * Add error message
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
         * Check if field has errors
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
         * Get error message for field
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
         * Clear all errors
         * @return void
         */
        public function clear_errors()
        {
            $this->errors = array();
        }

        /**
         * Get singleton instance
         * @param string $class_name
         * @return IG_Uploader_Model
         */
        public static function model($class_name = __CLASS__)
        {
            if (null === self::$_instance) {
                self::$_instance = new $class_name();
            }
            return self::$_instance;
        }
    }
}
