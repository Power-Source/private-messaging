<?php

/**
 * Author: PSOURCE
 * Name: Notifications
 * Description: Display a visual notification for users when a new message is received.
 * Modern rewrite with native Browser Notifications API and Toast system.
 */
if (!class_exists('MM_Push_Notification')) {
    class MM_Push_Notification
    {
        private $poll_interval = 30000; // 30 seconds - optimized from 10 seconds
        
        public function __construct()
        {
            if (is_user_logged_in()) {
                add_action('wp_enqueue_scripts', array(&$this, 'enqueue_assets'));
                add_action('admin_enqueue_scripts', array(&$this, 'enqueue_assets'));
                add_action('mm_message_sent', array(&$this, 'index_new_message'));
                add_action('wp_ajax_mm_push_notification', array(&$this, 'check_new_messages'));
                add_action('wp_footer', array(&$this, 'render_toast_container'));
                add_action('admin_footer', array(&$this, 'render_toast_container'));
            }
        }

        /**
         * Enqueue notification assets (CSS + JS)
         */
        public function enqueue_assets()
        {
            // Inline CSS for toasts (better performance than external file)
            wp_enqueue_style('mm-notifications', false);
            wp_add_inline_style('mm-notifications', $this->get_toast_css());
            
            // Modern notification handler
            wp_enqueue_script('mm-notifications', false);
            wp_add_inline_script('mm-notifications', $this->get_notification_js());
        }

        /**
         * Check for new messages via AJAX
         */
        public function check_new_messages()
        {
            // Verify nonce
            if (!wp_verify_nonce(mmg()->post('_wpnonce'), get_current_user_id())) {
                wp_send_json_error('Security check failed', 403);
            }

            $user_id = get_current_user_id();
            $cache_key = 'mm_notification_' . $user_id;
            $notification_data = get_user_meta($user_id, $cache_key, true);

            if (empty($notification_data) || !is_array($notification_data) || $notification_data['status'] != 1) {
                wp_send_json_success([]);
            }

            // Get unread count for badge
            $unreads = MM_Conversation_Model::get_unread($user_id);
            
            $response = [
                'messages' => $notification_data['messages'] ?? [],
                'count' => count($unreads),
            ];

            // Clear notification after sending
            delete_user_meta($user_id, $cache_key);
            
            wp_send_json_success($response);
        }

        /**
         * Store notification when message is sent
         */
        public function index_new_message(MM_Message_Model $model)
        {
            $cache_key = 'mm_notification_' . $model->send_to;
            
            $cache = [
                'status' => 1,
                'messages' => [
                    [
                        'id' => $model->id,
                        'from' => $model->get_name($model->send_from),
                        'from_id' => $model->send_from,
                        'subject' => $model->subject,
                        'content' => mmg()->trim_text($model->content, 150),
                        'timestamp' => current_time('mysql'),
                        'conversation_id' => $model->conversation_id,
                    ]
                ]
            ];

            add_user_meta($model->send_to, $cache_key, $cache, true);
        }

        /**
         * Render toast container HTML
         */
        public function render_toast_container()
        {
            echo '<div id="mm-toast-container" class="mm-toast-container"></div>';
        }

        /**
         * Toast CSS (modern, minimal, no dependencies)
         */
        private function get_toast_css()
        {
            return <<<'CSS'
.mm-toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    pointer-events: none;
    max-width: 400px;
}

.mm-toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    margin-bottom: 12px;
    padding: 16px;
    pointer-events: auto;
    animation: mm-toast-slide-in 0.3s ease-out;
    border-left: 4px solid #0073aa;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 14px;
    line-height: 1.5;
    color: #333;
}

.mm-toast.success { border-left-color: #28a745; }
.mm-toast.error { border-left-color: #dc3545; }
.mm-toast.warning { border-left-color: #ffc107; }
.mm-toast.info { border-left-color: #17a2b8; }

.mm-toast-header {
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mm-toast-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    padding: 0;
    margin-left: 12px;
    transition: color 0.2s;
}

.mm-toast-close:hover {
    color: #333;
}

.mm-toast-content {
    margin-bottom: 12px;
    color: #666;
}

.mm-toast-actions {
    display: flex;
    gap: 8px;
}

.mm-toast-button {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}

.mm-toast-button.primary {
    background: #0073aa;
    color: white;
}

.mm-toast-button.primary:hover {
    background: #005a87;
}

.mm-toast-button.secondary {
    background: #f0f0f0;
    color: #333;
}

.mm-toast-button.secondary:hover {
    background: #e0e0e0;
}

@keyframes mm-toast-slide-in {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes mm-toast-slide-out {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

.mm-toast.closing {
    animation: mm-toast-slide-out 0.3s ease-in forwards;
}

@media (max-width: 768px) {
    .mm-toast-container {
        left: 10px;
        right: 10px;
        top: 10px;
        max-width: none;
    }
}
CSS;
        }

        /**
         * Main notification JavaScript
         */
        private function get_notification_js()
        {
            $inbox_url = esc_js(get_permalink(mmg()->setting()->inbox_page) . '?box=unread');
            $nonce = wp_create_nonce(get_current_user_id());
            $ajax_url = esc_js(admin_url('admin-ajax.php'));
            
            return <<<JS
(function() {
    'use strict';
    
    class MMNotificationManager {
        constructor() {
            this.pollInterval = {$this->poll_interval};
            this.container = document.getElementById('mm-toast-container');
            this.activeToasts = [];
            this.requestBrowserPermission();
            this.startPolling();
        }
        
        requestBrowserPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
        
        startPolling() {
            this.poll();
        }
        
        poll() {
            const self = this;
            fetch('{$ajax_url}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mm_push_notification',
                    _wpnonce: '{$nonce}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.messages && data.data.messages.length > 0) {
                    data.data.messages.forEach(msg => self.showNotification(msg, data.data.count));
                }
                if (data.data && data.data.count !== undefined) {
                    self.updateBadge(data.data.count);
                }
            })
            .catch(err => console.error('Notification poll error:', err))
            .finally(() => {
                setTimeout(() => self.poll(), self.pollInterval);
            });
        }
        
        showNotification(message, count) {
            // Show toast
            this.showToast(message);
            
            // Show browser notification if permitted
            if ('Notification' in window && Notification.permission === 'granted') {
                this.showBrowserNotification(message, count);
            }
        }
        
        showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'mm-toast info';
            
            const fromDisplay = message.from ? message.from : 'Neuer Benutzer';
            const closeBtnId = 'close-' + Math.random().toString(36).substr(2, 9);
            
            toast.innerHTML = \`
                <div class="mm-toast-header">
                    <span>📨 Neue Nachricht</span>
                    <button class="mm-toast-close" id="\${closeBtnId}">×</button>
                </div>
                <div class="mm-toast-content">
                    <strong>\${this.escapeHtml(fromDisplay)}</strong><br>
                    <em>\${this.escapeHtml(message.subject || '(Kein Betreff)')}</em><br>
                    \${this.escapeHtml(message.content.substring(0, 100))}...
                </div>
                <div class="mm-toast-actions">
                    <a href="{$inbox_url}" class="mm-toast-button primary">Anzeigen</a>
                    <button type="button" class="mm-toast-button secondary close-btn">Schließen</button>
                </div>
            \`;
            
            this.container.appendChild(toast);
            this.activeToasts.push(toast);
            
            // Close button handler
            document.getElementById(closeBtnId).addEventListener('click', () => this.removeToast(toast));
            toast.querySelector('.close-btn').addEventListener('click', () => this.removeToast(toast));
            
            // Auto-remove after 8 seconds
            setTimeout(() => {
                if (this.activeToasts.includes(toast)) {
                    this.removeToast(toast);
                }
            }, 8000);
        }
        
        showBrowserNotification(message, count) {
            const title = 'Neue Nachricht von ' + message.from;
            const options = {
                body: message.subject || message.content,
                icon: '/wp-content/plugins/private-messaging/assets/icon.png',
                badge: '/wp-content/plugins/private-messaging/assets/badge.png',
                tag: 'mm-notification-' + message.id,
                requireInteraction: false,
            };
            
            if (message.conversation_id) {
                options.data = { conversation_id: message.conversation_id };
            }
            
            const notification = new Notification(title, options);
            
            // Click handler
            notification.addEventListener('click', () => {
                window.focus();
                window.open('{$inbox_url}', '_self');
                notification.close();
            });
        }
        
        updateBadge(count) {
            const adminBar = document.querySelector('.mm-admin-bar');
            if (adminBar) {
                const badge = adminBar.querySelector('span');
                if (badge) {
                    badge.textContent = count;
                }
            }
            
            const unreadCount = document.querySelector('.unread-count');
            if (unreadCount) {
                unreadCount.textContent = count;
                unreadCount.setAttribute('title', count + ' ' + (unreadCount.dataset.text || 'unread'));
            }
        }
        
        removeToast(toast) {
            toast.classList.add('closing');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                const idx = this.activeToasts.indexOf(toast);
                if (idx > -1) {
                    this.activeToasts.splice(idx, 1);
                }
            }, 300);
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new MMNotificationManager());
    } else {
        new MMNotificationManager();
    }
})();
JS;
        }
    }
}

new MM_Push_Notification();