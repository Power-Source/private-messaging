<?php
$primary_message = array_shift($messages);
if (!$primary_message) {
    return;
}

if (!isset($render_reply)) {
    $render_reply = true;
}

$conversation = MM_Conversation_Model::model()->find($primary_message->conversation_id);
$conversation_messages = array_merge(array($primary_message), array_filter($messages, 'is_object'));
$seen_messages = array();
$unique_messages = array();

foreach ($conversation_messages as $conversation_message) {
    $fingerprint = !empty($conversation_message->id)
        ? 'id:' . $conversation_message->id
        : md5(($conversation_message->content ?? '') . ($conversation_message->date ?? ''));
    if (isset($seen_messages[$fingerprint])) {
        continue;
    }
    $seen_messages[$fingerprint] = true;
    $unique_messages[] = $conversation_message;
}

$render_message_body = static function ($message) {
    $content = apply_filters('mm_message_content', $message->content);
    if (isset($GLOBALS['wp_embed']) && $GLOBALS['wp_embed'] instanceof \WP_Embed) {
        $content = $GLOBALS['wp_embed']->autoembed($content);
        $content = $GLOBALS['wp_embed']->run_shortcode($content);
    }

    $allowed = wp_kses_allowed_html('post');
    $allowed['iframe'] = array(
        'src' => true,
        'width' => true,
        'height' => true,
        'frameborder' => true,
        'allow' => true,
        'allowfullscreen' => true,
        'title' => true,
    );

    return wp_kses(wpautop(make_clickable($content)), $allowed);
};

$render_attachments = static function ($message) {
    $filenames = array_filter(array_map('trim', explode(',', (string)$message->attachment)));
    if (!$filenames) {
        return;
    }
    ?>
    <div class="mm-message-attachments">
        <?php foreach ($filenames as $filename):
            $file_info = PM_Attachment_Handler::get_file_info($message->conversation_id, $filename);
            if (!$file_info) {
                continue;
            }

            $extension = strtolower($file_info['extension']);
            $is_image = in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true);
            $is_video = in_array($extension, array('mp4', 'webm', 'ogg', 'mov'), true);
            $download_url = PM_Attachment_Handler::get_download_url($message->conversation_id, $filename);
            $preview_url = PM_Attachment_Handler::get_preview_url($message->conversation_id, $filename);
            ?>
            <article class="mm-attachment <?php echo $is_image || $is_video ? 'mm-attachment-media' : 'mm-attachment-file'; ?>">
                <?php if ($is_image): ?>
                    <a class="mm-attachment-preview<?php echo function_exists('mm_community_media_available') && mm_community_media_available() ? ' cpc_media_external_lightbox_trigger' : ''; ?>" href="<?php echo esc_url($preview_url); ?>"<?php echo function_exists('mm_community_media_lightbox_attributes') ? mm_community_media_lightbox_attributes('photo', $file_info['display_name']) : ''; ?>>
                        <img src="<?php echo esc_url($download_url); ?>" alt="<?php echo esc_attr($file_info['display_name']); ?>" loading="lazy">
                    </a>
                <?php elseif ($is_video): ?>
                    <a class="mm-attachment-preview<?php echo function_exists('mm_community_media_available') && mm_community_media_available() ? ' cpc_media_external_lightbox_trigger' : ''; ?>" href="<?php echo esc_url($preview_url); ?>"<?php echo function_exists('mm_community_media_lightbox_attributes') ? mm_community_media_lightbox_attributes('video', $file_info['display_name']) : ''; ?>>
                        <video muted preload="metadata">
                            <source src="<?php echo esc_url($download_url); ?>">
                        </video>
                        <span class="mm-attachment-play" aria-hidden="true"><i class="fa fa-play"></i></span>
                    </a>
                <?php else: ?>
                    <span class="mm-attachment-file-icon" aria-hidden="true"><i class="fa <?php echo $extension === 'pdf' ? 'fa-file-pdf-o' : ($extension === 'zip' ? 'fa-file-archive-o' : 'fa-file-o'); ?>"></i></span>
                <?php endif; ?>

                <div class="mm-attachment-details">
                    <strong title="<?php echo esc_attr($file_info['display_name']); ?>"><?php echo esc_html($file_info['display_name']); ?></strong>
                    <span><?php echo esc_html($file_info['size_formatted']); ?> · <?php echo esc_html(strtoupper($extension)); ?></span>
                </div>

                <?php if (!$is_image && !$is_video): ?>
                    <a href="<?php echo esc_url($download_url); ?>" class="btn btn-default btn-xs" download>
                        <i class="fa fa-download" aria-hidden="true"></i>
                        <span class="screen-reader-text"><?php esc_html_e('Herunterladen', mmg()->domain); ?></span>
                    </a>
                <?php endif; ?>
            </article>
            <?php if (function_exists('mm_render_community_media_actions')): ?>
                <?php echo mm_render_community_media_actions($message->conversation_id, $filename, $file_info); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
};

$delete_nonce = wp_create_nonce('mm_delete_conv');
?>
<div class="mm-conversation-view">
    <header class="mm-conversation-header">
        <div class="mm-conversation-title-group">
            <span class="mm-conversation-eyebrow"><?php esc_html_e('Unterhaltung', mmg()->domain); ?></span>
            <h2 class="mm-conversation-title"><?php echo esc_html(wp_strip_all_tags(apply_filters('mm_message_subject', $primary_message->subject))); ?></h2>
        </div>

        <?php if ($render_reply): ?>
            <div class="mm-conversation-actions">
                <?php if ($conversation && $conversation->is_lock()): ?>
                    <button type="button" class="btn btn-default btn-sm" disabled>
                        <i class="fa fa-lock" aria-hidden="true"></i> <?php esc_html_e('Gesperrt', mmg()->domain); ?>
                    </button>
                <?php else: ?>
                    <button type="button" data-conversation-id="<?php echo esc_attr($primary_message->conversation_id); ?>" class="btn btn-primary btn-sm mm-reply-inline">
                        <i class="fa fa-reply" aria-hidden="true"></i> <?php esc_html_e('Antworten', mmg()->domain); ?>
                    </button>
                <?php endif; ?>

                <button type="button"
                        data-id="<?php echo esc_attr(mmg()->encrypt($primary_message->conversation_id)); ?>"
                        data-type="<?php echo esc_attr($conversation && $conversation->is_archive() ? MM_Message_Status_Model::STATUS_READ : MM_Message_Status_Model::STATUS_ARCHIVE); ?>"
                        class="btn btn-default btn-sm mm-status"
                        title="<?php echo esc_attr($conversation && $conversation->is_archive() ? __('Archivierung aufheben', mmg()->domain) : __('Archivieren', mmg()->domain)); ?>">
                    <i class="fa <?php echo $conversation && $conversation->is_archive() ? 'fa-undo' : 'fa-archive'; ?>" aria-hidden="true"></i>
                    <span class="screen-reader-text"><?php echo esc_html($conversation && $conversation->is_archive() ? __('Archivierung aufheben', mmg()->domain) : __('Archivieren', mmg()->domain)); ?></span>
                </button>

                <button type="button"
                        class="btn btn-default btn-sm mm-delete-conv"
                        data-id="<?php echo esc_attr(mmg()->encrypt($primary_message->conversation_id)); ?>"
                        data-nonce="<?php echo esc_attr($delete_nonce); ?>"
                        title="<?php esc_attr_e('Löschen', mmg()->domain); ?>">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                    <span class="screen-reader-text"><?php esc_html_e('Löschen', mmg()->domain); ?></span>
                </button>
            </div>
        <?php endif; ?>
    </header>

    <div class="mm-inline-status" aria-live="polite"></div>

    <?php if ($conversation && $conversation->is_lock()): ?>
        <div class="mm-conversation-notice"><i class="fa fa-lock" aria-hidden="true"></i> <?php esc_html_e('Diese Unterhaltung wurde gesperrt.', mmg()->domain); ?></div>
    <?php endif; ?>

    <?php do_action('message_content_meta', $primary_message); ?>

    <div class="mm-message-thread">
        <?php foreach ($unique_messages as $thread_message): ?>
            <article class="mm-message-card <?php echo (int)$thread_message->send_from === get_current_user_id() ? 'is-own-message' : ''; ?>">
                <header class="mm-message-card-header">
                    <div class="mm-message-author-avatar">
                        <?php echo PM_Avatar_Handler::get_avatar_html($thread_message->send_from, 40, 'mm-detail-avatar'); ?>
                    </div>
                    <div class="mm-message-author">
                        <strong><?php
                            if ((int)$thread_message->send_from === get_current_user_id()) {
                                echo esc_html__('Ich', mmg()->domain);
                            } else {
                                echo esc_html($thread_message->get_name($thread_message->send_from));
                            }
                        ?></strong>
                        <?php if (mmg()->get('box') === 'sent'): ?>
                            <span><?php esc_html_e('An:', mmg()->domain); ?> <?php echo esc_html($thread_message->get_name($thread_message->send_to)); ?></span>
                        <?php endif; ?>
                    </div>
                    <time datetime="<?php echo esc_attr(date('c', strtotime($thread_message->date))); ?>"><?php echo esc_html(date('j. F Y, G:i', strtotime($thread_message->date))); ?></time>
                </header>
                <div class="mm-message-body">
                    <?php echo $render_message_body($thread_message); ?>
                </div>
                <?php $render_attachments($thread_message); ?>
            </article>
        <?php endforeach; ?>
    </div>
</div>
