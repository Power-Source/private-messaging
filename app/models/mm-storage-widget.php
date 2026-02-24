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
            $remaining = MM_Storage_Model::get_user_storage_remaining($user_id);
            $percentage = MM_Storage_Model::get_storage_percentage($user_id);
            
            $used_formatted = MM_Storage_Model::format_bytes($used);
            $limit_formatted = MM_Storage_Model::format_bytes($limit);
            $remaining_formatted = MM_Storage_Model::format_bytes($remaining);
            
            $warning_class = $percentage >= 90 ? 'storage-warning' : '';
            $bar_color = $percentage >= 90 ? '#ff4757' : 'rgba(255, 255, 255, 0.8)';
            
            ob_start();
            ?>
            <div class="mm-storage-widget-inline <?php echo $warning_class; ?>" style="
                display: inline-flex;
                align-items: center;
                gap: 12px;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 10px;
                padding: 8px 14px;
                color: #dfe6ef;
                font-size: 13px;
                margin-left: auto;
                white-space: nowrap;
                transition: all 0.12s ease;
                position: absolute;
                right: 18px;
                top: 60px;
            " onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">📦</span>
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <strong style="font-size: 11px; color: #dfe6ef; letter-spacing: 0.01em;">
                            <?php echo esc_html($used_formatted); ?> / <?php echo esc_html($limit_formatted); ?>
                        </strong>
                        <div style="width: 50px; height: 3px; background-color: rgba(255, 255, 255, 0.15); border-radius: 2px; overflow: hidden;">
                            <div style="
                                height: 100%;
                                width: <?php echo esc_attr($percentage); ?>%;
                                background-color: <?php echo esc_attr($bar_color); ?>;
                                border-radius: 2px;
                                transition: width 0.3s ease;
                            "></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($percentage >= 90): ?>
                    <span style="
                        background: #ff4757;
                        color: white;
                        padding: 3px 8px;
                        border-radius: 4px;
                        font-size: 11px;
                        font-weight: bold;
                    " title="<?php esc_attr_e('Speicherplatz läuft aus!', mmg()->domain); ?>">
                        ⚠️ <?php echo esc_html($percentage); ?>%
                    </span>
                <?php else: ?>
                    <span style="color: #dfe6ef; font-size: 11px; font-weight: bold; opacity: 0.85;">
                        <?php echo esc_html($percentage); ?>%
                    </span>
                <?php endif; ?>
            </div>
            <?php
            
            return ob_get_clean();
        }
    }
}
