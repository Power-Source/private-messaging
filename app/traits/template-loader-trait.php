<?php
/**
 * Template Loader Trait
 * 
 * Modern replacement for IG_Request render functionality.
 * Uses ClassicPress native template loading instead of reflection-based paths.
 * 
 * @package PrivateMessaging
 * @since 2.0.0
 */

trait Template_Loader_Trait {
    
    /**
     * Base path for template files
     * @var string|null
     */
    protected ?string $template_base_path = null;
    
    /**
     * Render a template file with layout support
     * 
     * @param string $template Template path relative to views/
     * @param array $args Variables to extract into template scope
     * @param bool $output Whether to echo or return
     * @return string|void
     */
    protected function render_template(string $template, array $args = [], bool $output = true) {
        $content = $this->load_template_part($template, $args, false);
        
        if ($output) {
            echo $content;
        } else {
            return $content;
        }
    }
    
    /**
     * Load a template partial
     * 
     * @param string $template Template path relative to views/
     * @param array $args Variables to extract into template scope
     * @param bool $output Whether to echo or return
     * @return string|void
     */
    protected function load_template_part(string $template, array $args = [], bool $output = true) {
        $template_path = $this->get_template_path($template);
        
        if (!file_exists($template_path)) {
            $error_msg = sprintf(
                __('Template not found: %s', 'private-messaging'),
                esc_html($template)
            );
            
            if (WP_DEBUG) {
                trigger_error($error_msg, E_USER_WARNING);
            }
            
            if ($output) {
                echo '<!-- ' . esc_html($error_msg) . ' -->';
                return;
            }
            return '';
        }
        
        // Extract args into local scope
        if (!empty($args)) {
            extract($args, EXTR_SKIP);
        }
        
        ob_start();
        include $template_path;
        $content = ob_get_clean();
        
        if ($output) {
            echo $content;
        } else {
            return $content;
        }
    }

    /**
     * Render a template inside the main layout wrapper
     *
     * @param string $template Template path relative to views/
     * @param array $args Variables to extract into template scope
     * @param bool $show_nav Whether to show navigation controls
     * @param bool $output Whether to echo or return
     * @return string|void
     */
    protected function render_with_layout(string $template, array $args = [], bool $show_nav = true, bool $output = true) {
        $content = $this->load_template_part($template, $args, false);

        $layout_args = array_merge($args, [
            'content' => $content,
            'show_nav' => $show_nav,
        ]);

        return $this->load_template_part('layout/main', $layout_args, $output);
    }
    
    /**
     * Get full path to template file
     * 
     * @param string $template Template slug
     * @return string Full filesystem path
     */
    protected function get_template_path(string $template): string {
        // Allow override via filter
        $custom_path = apply_filters('mm_template_path', null, $template);
        if ($custom_path && file_exists($custom_path)) {
            return $custom_path;
        }
        
        // Auto-detect base path if not set
        if (is_null($this->template_base_path)) {
            $this->template_base_path = dirname(__DIR__) . '/views/';
        }
        
        $file_path = $this->template_base_path . ltrim($template, '/') . '.php';
        
        return apply_filters('mm_template_file_path', $file_path, $template);
    }
    
    /**
     * Set flash message using ClassicPress Transients
     * 
     * Modern replacement for IG_Request flash messages.
     * Uses transients with user-specific keys for better performance.
     * 
     * @param string $key Message identifier
     * @param mixed $message Message content
     * @param int $expiration Expiration in seconds (default: 60)
     */
    protected function set_flash(string $key, $message, int $expiration = 60): void {
        $transient_key = $this->get_flash_transient_key($key);
        set_transient($transient_key, $message, $expiration);
    }
    
    /**
     * Get and delete flash message
     * 
     * @param string $key Message identifier
     * @return mixed|null Message content or null
     */
    protected function get_flash(string $key) {
        $transient_key = $this->get_flash_transient_key($key);
        $message = get_transient($transient_key);
        
        if ($message !== false) {
            delete_transient($transient_key);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Check if flash message exists
     * 
     * @param string $key Message identifier
     * @return bool
     */
    protected function has_flash(string $key): bool {
        $transient_key = $this->get_flash_transient_key($key);
        return get_transient($transient_key) !== false;
    }
    
    /**
     * Get user-specific transient key for flash messages
     * 
     * @param string $key Original key
     * @return string Transient key
     */
    private function get_flash_transient_key(string $key): string {
        $user_id = get_current_user_id();
        return sprintf('mm_flash_%d_%s', $user_id, sanitize_key($key));
    }
    
    /**
     * Redirect to URL
     * 
     * @param string $url Target URL
     * @param int $status HTTP status code
     */
    protected function redirect(string $url, int $status = 302): void {
        wp_redirect($url, $status);
        exit;
    }
    
    /**
     * Refresh current page
     */
    protected function refresh(): void {
        $this->redirect($_SERVER['REQUEST_URI']);
    }
}
