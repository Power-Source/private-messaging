<?php

if (!class_exists('MM_Community_Media')) {
    class MM_Community_Media
    {
        public function __construct()
        {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 30);
            add_action('wp_ajax_mm_add_attachment_to_community_gallery', array($this, 'add_attachment_to_gallery'));
        }

        public static function is_available()
        {
            return is_user_logged_in()
                && function_exists('cpc_media_is_enabled')
                && cpc_media_is_enabled()
                && function_exists('cpc_media_get_galleries')
                && function_exists('cpc_media_upload_file_to_gallery')
                && function_exists('cpc_media_create_item');
        }

        public function enqueue_assets()
        {
            if (!self::is_available()) {
                return;
            }

            if (function_exists('cpc_media_enqueue_assets') && !wp_script_is('cpc-media-js', 'enqueued')) {
                cpc_media_enqueue_assets();
            }

            wp_enqueue_script(
                'mm-community-media',
                mmg()->plugin_url . 'assets/community-media.js',
                array('jquery', 'cpc-media-js'),
                filemtime(mmg()->plugin_path . 'assets/community-media.js'),
                true
            );
            wp_localize_script('mm-community-media', 'mm_community_media', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'uploadError' => __('Upload fehlgeschlagen.', mmg()->domain),
            ));
        }

        public static function user_can_access_conversation($conversation_id, $user_id = 0)
        {
            $user_id = $user_id ? (int)$user_id : get_current_user_id();
            if (!$user_id || !class_exists('MM_Conversation_Model')) {
                return false;
            }

            $conversation = MM_Conversation_Model::model()->find((int)$conversation_id);
            if (!$conversation) {
                return false;
            }

            $user_ids = is_array($conversation->user_index)
                ? $conversation->user_index
                : array_map('trim', explode(',', (string)$conversation->user_index));

            return in_array($user_id, array_map('intval', $user_ids), true);
        }

        public static function get_attachment_media_type($filename, $path = '')
        {
            $filetype = wp_check_filetype((string)$filename);
            $mime_type = !empty($filetype['type']) ? $filetype['type'] : '';
            if (!$mime_type && $path && function_exists('mime_content_type')) {
                $mime_type = (string)@mime_content_type($path);
            }

            return array(
                'mime_type' => $mime_type,
                'media_type' => cpc_media_map_mime_to_type($mime_type, 'doc'),
            );
        }

        public static function render_attachment_actions($conversation_id, $filename, $file_info)
        {
            if (!self::is_available() || !is_array($file_info)) {
                return '';
            }

            $filename = basename((string)$filename);
            if ($filename === '' || !self::user_can_access_conversation($conversation_id)) {
                return '';
            }

            $type_info = self::get_attachment_media_type($filename, isset($file_info['path']) ? $file_info['path'] : '');
            $media_type = $type_info['media_type'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $preview_type = $extension === 'pdf' ? 'pdf' : $media_type;
            $preview_url = PM_Attachment_Handler::get_preview_url((int)$conversation_id, $filename);
            $galleries = cpc_media_get_galleries(array(
                'author' => get_current_user_id(),
                'component' => 'members',
                'component_id' => get_current_user_id(),
                'gallery_type' => $media_type,
            ));
            $galleries = array_values(array_filter($galleries, function ($gallery) {
                return !cpc_media_is_system_gallery($gallery->ID);
            }));

            $html = '<div class="mm-community-media-actions">';
            if ($preview_url && !in_array($media_type, array('photo', 'video'), true)) {
                $html .= '<a class="btn btn-default btn-xs cpc_media_external_lightbox_trigger" href="'.esc_url($preview_url).'" data-media-type="'.esc_attr($preview_type).'" data-title="'.esc_attr($file_info['display_name']).'">'.esc_html__('Vorschau', mmg()->domain).'</a>';
            }

            if ($galleries) {
                $html .= '<button type="button" class="btn btn-default btn-xs mm-community-gallery-toggle">'.esc_html__('Zu einer deiner Galerien hinzufügen', mmg()->domain).'</button>';
                $html .= '<span class="mm-community-gallery-picker" hidden>';
                $html .= '<select class="mm-community-gallery-select" aria-label="'.esc_attr__('Galerie auswählen', mmg()->domain).'">';
                foreach ($galleries as $gallery) {
                    $html .= '<option value="'.(int)$gallery->ID.'">'.esc_html(get_the_title($gallery)).'</option>';
                }
                $html .= '</select>';
                $html .= '<button type="button" class="btn btn-primary btn-xs mm-community-gallery-add" data-conversation-id="'.(int)$conversation_id.'" data-filename="'.esc_attr($filename).'" data-nonce="'.esc_attr(wp_create_nonce('mm_community_gallery_attachment')).'">'.esc_html__('Hinzufügen', mmg()->domain).'</button>';
                $html .= '</span>';
            }
            $html .= '<span class="mm-community-gallery-status" aria-live="polite"></span>';
            $html .= '</div>';

            return $html;
        }

        public function add_attachment_to_gallery()
        {
            $user_id = get_current_user_id();
            $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
            if (!$user_id || !wp_verify_nonce($nonce, 'mm_community_gallery_attachment') || !self::is_available()) {
                wp_send_json_error(array('message' => __('Ungültige Anfrage.', mmg()->domain)), 403);
            }

            $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
            $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
            $filename = isset($_POST['filename']) ? basename(sanitize_file_name(wp_unslash($_POST['filename']))) : '';
            $gallery = get_post($gallery_id);

            if (!$conversation_id || !$gallery || $gallery->post_type !== 'cpc_gallery' || !self::user_can_access_conversation($conversation_id, $user_id)) {
                wp_send_json_error(array('message' => __('Keine Berechtigung.', mmg()->domain)), 403);
            }
            if ((int)$gallery->post_author !== $user_id || cpc_media_get_gallery_component($gallery_id) !== 'members' || cpc_media_get_gallery_component_id($gallery_id) !== $user_id || cpc_media_is_system_gallery($gallery_id)) {
                wp_send_json_error(array('message' => __('Bitte wähle eine eigene Galerie.', mmg()->domain)), 403);
            }

            $file_info = PM_Attachment_Handler::get_file_info($conversation_id, $filename);
            if (!$file_info || empty($file_info['path'])) {
                wp_send_json_error(array('message' => __('Anhang nicht gefunden.', mmg()->domain)), 404);
            }

            $source_path = realpath($file_info['path']);
            $conversation_dir = PM_Attachment_Handler::get_conversation_upload_dir($conversation_id);
            $source_dir = realpath($conversation_dir['path']);
            if (!$source_path || !$source_dir || strpos($source_path, trailingslashit($source_dir)) !== 0 || !is_file($source_path)) {
                wp_send_json_error(array('message' => __('Ungültiger Dateipfad.', mmg()->domain)), 403);
            }

            $type_info = self::get_attachment_media_type($filename, $source_path);
            if ($type_info['media_type'] !== cpc_media_get_gallery_type($gallery_id)) {
                wp_send_json_error(array('message' => __('Der Dateityp passt nicht zu dieser Galerie.', mmg()->domain)), 400);
            }

            $temp_path = wp_tempnam($filename);
            if (!$temp_path || !@copy($source_path, $temp_path)) {
                wp_send_json_error(array('message' => __('Datei konnte nicht vorbereitet werden.', mmg()->domain)), 500);
            }

            $uploaded = cpc_media_upload_file_to_gallery(array(
                'name' => isset($file_info['display_name']) ? $file_info['display_name'] : $filename,
                'type' => $type_info['mime_type'],
                'tmp_name' => $temp_path,
                'error' => 0,
                'size' => filesize($source_path),
            ), $gallery_id);
            if (empty($uploaded['ok'])) {
                @unlink($temp_path);
                wp_send_json_error(array('message' => __('Datei konnte nicht zur Galerie hinzugefügt werden.', mmg()->domain)), 400);
            }

            $media_id = cpc_media_create_item(array(
                'gallery_id' => $gallery_id,
                'user_id' => $user_id,
                'title' => pathinfo((string)$file_info['display_name'], PATHINFO_FILENAME),
                'mime_type' => $uploaded['mime_type'],
                'media_type' => $uploaded['media_type'],
                'source' => 'pm-attachment',
                'source_id' => $conversation_id,
                'source_url' => $uploaded['url'],
                'source_file' => $uploaded['path'],
                'file_url' => $uploaded['url'],
                'file_path' => $uploaded['path'],
                'metadata' => $uploaded['metadata'],
                'migrated_files' => array(array('role' => 'original', 'path' => $uploaded['path'], 'url' => $uploaded['url'])),
            ));
            if (!$media_id) {
                @unlink($uploaded['path']);
                wp_send_json_error(array('message' => __('Galerieeintrag konnte nicht erstellt werden.', mmg()->domain)), 500);
            }

            cpc_media_update_gallery_media_count($gallery_id, cpc_media_get_gallery_media_count($gallery_id) + 1);
            wp_send_json_success(array('message' => __('Zur Galerie hinzugefügt.', mmg()->domain), 'media_id' => $media_id));
        }
    }
}

function mm_community_media_available()
{
    return MM_Community_Media::is_available();
}

function mm_render_community_media_actions($conversation_id, $filename, $file_info)
{
    return MM_Community_Media::render_attachment_actions($conversation_id, $filename, $file_info);
}

function mm_community_media_lightbox_attributes($media_type, $title)
{
    if (!MM_Community_Media::is_available()) {
        return '';
    }

    return ' data-media-type="'.esc_attr($media_type).'" data-title="'.esc_attr($title).'"';
}

new MM_Community_Media();
