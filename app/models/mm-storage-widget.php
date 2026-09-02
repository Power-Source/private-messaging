<?php

/**
 * Storage Widget für Inbox
 * Zeigt Speichernutzung des aktuellen Users in der Navigation
 */

if (!class_exists('MM_Storage_Widget')) {
    class MM_Storage_Widget
    {
        public static function render($user_id = null)
        {
            if (!$user_id) {
                $user_id = get_current_user_id();
            }
            
            if (!$user_id) {
                return '';
            }
            
            // Include the Storage Model
            if (!class_exists('MM_Storage_Model')) {
                require_once dirname(__FILE__) . '/mm-storage-model.php';
            }
            
            $limit = MM_Storage_Model::get_user_storage_limit($user_id);
            
            // If unlimited, don't show widget
            if ($limit === false) {
                return '';
            }
            
            $used = MM_Storage_Model::get_user_storage_used($user_id);
            $percentage = MM_Storage_Model::get_storage_percentage($user_id);
            
            $used_formatted = MM_Storage_Model::format_bytes($used);
            $limit_formatted = MM_Storage_Model::format_bytes($limit);
            
            $warning_class = $percentage >= 90 ? 'storage-warning' : '';
            
            ob_start();
            ?>
            <div class="mm-storage-widget-inline <?php echo $warning_class; ?>" title="<?php echo esc_attr(sprintf(__('Speicher: %1$s von %2$s belegt', mmg()->domain), $used_formatted, $limit_formatted)); ?>">
                <i class="fa fa-hdd-o" aria-hidden="true"></i>
                <div class="mm-storage-details">
                    <strong>
                            <?php echo esc_html($used_formatted); ?> / <?php echo esc_html($limit_formatted); ?>
                    </strong>
                    <span class="mm-storage-progress" aria-hidden="true">
                        <span style="width: <?php echo esc_attr($percentage); ?>%;"></span>
                    </span>
                </div>
                <span class="mm-storage-percentage"><?php echo esc_html($percentage); ?>%</span>
            </div>
            <?php
            
            return ob_get_clean();
        }
    }
}
