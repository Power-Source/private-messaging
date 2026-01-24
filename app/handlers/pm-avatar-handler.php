<?php
/**
 * PM_Avatar_Handler
 * 
 * Handles avatar rendering with fallback to initials.
 * Supports WordPress user data (first_name, last_name, display_name).
 */
class PM_Avatar_Handler {

    /**
     * Color palette for initials backgrounds
     * Deterministic colors based on user ID hash
     */
    private static $avatar_colors = [
        '#667eea', // Indigo
        '#764ba2', // Purple
        '#f093fb', // Pink
        '#4facfe', // Blue
        '#00f2fe', // Cyan
        '#43e97b', // Green
        '#fa709a', // Rose
        '#30cfd0', // Turquoise
        '#a8edea', // Mint
        '#fed6e3', // Light pink
    ];

    /**
     * Get HTML avatar element (image or initials)
     * 
     * @param int $user_id WordPress user ID
     * @param int $size Avatar size in pixels (default: 36)
     * @param string $classes Additional CSS classes
     * 
     * @return string HTML avatar element
     */
    public static function get_avatar_html($user_id, $size = 36, $classes = '') {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return self::_render_initials_avatar('?', $size, $classes);
        }

        // Try to get WordPress avatar URL
        $avatar_url = self::get_avatar_url($user_id);
        
        // Check if it's a real avatar (not default gravatar)
        if ($avatar_url && !self::is_default_avatar($avatar_url)) {
            return sprintf(
                '<img src="%s" alt="%s" style="width:%dpx;height:%dpx;object-fit:cover;border-radius:50%;" class="%s">',
                esc_url($avatar_url),
                esc_attr($user->display_name),
                $size,
                $size,
                esc_attr($classes)
            );
        }

        // Fallback: generate initials
        $initials = self::get_user_initials($user);
        
        return self::_render_initials_avatar($initials, $size, $classes, $user_id);
    }

    /**
     * Get user initials from first/last name or display_name
     * 
     * @param WP_User $user WordPress user object
     * 
     * @return string 1-2 character initials
     */
    public static function get_user_initials($user) {
        $first_name = trim(get_user_meta($user->ID, 'first_name', true));
        $last_name = trim(get_user_meta($user->ID, 'last_name', true));

        // Prefer first + last name initials
        if (!empty($first_name) && !empty($last_name)) {
            return strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
        }

        // Use first name alone
        if (!empty($first_name)) {
            return strtoupper(substr($first_name, 0, 2));
        }

        // Fall back to first 2 chars of display name
        $display_name = trim($user->display_name);
        if (!empty($display_name)) {
            return strtoupper(substr($display_name, 0, 2));
        }

        // Fallback
        return strtoupper(substr($user->user_login, 0, 2));
    }

    /**
     * Get deterministic color for user based on ID
     * 
     * @param int $user_id WordPress user ID
     * 
     * @return string Hex color code
     */
    public static function get_avatar_color($user_id) {
        $index = $user_id % count(self::$avatar_colors);
        return self::$avatar_colors[$index];
    }

    /**
     * Get WordPress avatar URL
     * 
     * @param int $user_id WordPress user ID
     * 
     * @return string|false Avatar URL or false
     */
    public static function get_avatar_url($user_id) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return false;
        }

        // Get Gravatar URL (or default)
        $avatar_html = get_avatar($user_id, 96, 'blank');
        
        // Extract src from HTML
        if (preg_match('/src=["\']([^"\']+)["\']/', $avatar_html, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Check if avatar is default/placeholder
     * 
     * @param string $avatar_url Avatar URL
     * 
     * @return bool True if default avatar
     */
    private static function is_default_avatar($avatar_url) {
        // Check for common default/placeholder indicators
        if (empty($avatar_url)) {
            return true;
        }

        // Gravatar blank/mystery person
        if (strpos($avatar_url, 'gravatar.com') !== false && strpos($avatar_url, 'f=blank') !== false) {
            return true;
        }

        // Gravatar default with 'f=identicon', 'f=monsterid', etc.
        if (strpos($avatar_url, 'gravatar.com') !== false && preg_match('/[?&]f=(identicon|monsterid|wavatar|retro|robohash|blank)/', $avatar_url)) {
            return true;
        }

        return false;
    }

    /**
     * Render initials avatar HTML
     * 
     * @param string $initials User initials (1-2 chars)
     * @param int $size Avatar size in pixels
     * @param string $classes Additional CSS classes
     * @param int|null $user_id Optional user ID for color
     * 
     * @return string HTML avatar div
     */
    private static function _render_initials_avatar($initials, $size, $classes = '', $user_id = null) {
        $bg_color = $user_id ? self::get_avatar_color($user_id) : '#ccc';
        
        return sprintf(
            '<div style="width:%dpx;height:%dpx;border-radius:50%%;background:%s;display:flex;align-items:center;justify-content:center;font-weight:600;color:#fff;font-size:%dpx;text-transform:uppercase;box-shadow:0 2px 8px rgba(0,0,0,0.1);" class="%s" title="Avatar">%s</div>',
            $size,
            $size,
            esc_attr($bg_color),
            max(10, intval($size / 2.5)), // Font size scales with avatar
            esc_attr($classes),
            esc_html($initials)
        );
    }

    /**
     * Get user display name with fallback
     * 
     * @param int $user_id WordPress user ID
     * 
     * @return string User display name
     */
    public static function get_display_name($user_id) {
        $user = get_user_by('ID', $user_id);
        return $user ? $user->display_name : __('Unknown', 'private-messaging');
    }
}
