<div class="ig-container">
    <?php do_action('mm_before_layout') ?>
    <div class="mmessage-container">
        <?php if ($show_nav): ?>
            <?php
            $mm_current_box = mmg()->get('box', 'inbox');

            // Cache inbox counts to avoid repeated queries on every view render
            $cache_key = 'mm_counts_' . get_current_user_id() . '_' . get_current_blog_id();
            $mm_counts = get_transient($cache_key);

            if ($mm_counts === false || !is_array($mm_counts)) {
                $mm_counts = array(
                    'inbox'   => MM_Conversation_Model::count_all(),
                    'unread'  => MM_Conversation_Model::count_unread(),
                    'read'    => MM_Conversation_Model::count_read(),
                    'sent'    => MM_Conversation_Model::count_sent(),
                    'archive' => MM_Conversation_Model::count_archive(),
                );

                // Short TTL keeps counts fresh while trimming duplicate queries
                set_transient($cache_key, $mm_counts, 5 * MINUTE_IN_SECONDS);
            }
            ?>
            <div class="mm-nav-shell">
                <div class="mm-nav-top">
                    <div class="mm-nav-title"><?php _e("Nachrichten", mmg()->domain) ?></div>
                    <div class="mm-nav-actions">
                        <a class="btn btn-default btn-sm hidden-xs hidden-sm" href="<?php echo esc_url(add_query_arg('box', 'setting')) ?>">
                            <i class="fa fa-gear"></i> <?php _e("Einstellungen", mmg()->domain) ?>
                        </a>
                        <?php if (is_user_logged_in()): ?>
                            <a class="btn btn-primary btn-sm mm-compose" href="#compose-form-container">
                                <span class="mm-compose-icon">+</span> <?php _e("Verfassen", mmg()->domain) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Top-bar action buttons removed (actions remain in conversation view) -->
                <div class="mm-nav-tabs mm-toolbar-btn">
                    <a data-box="inbox" href="<?php echo esc_url(add_query_arg('box', 'inbox', get_permalink(mmg()->setting()->inbox_page))) ?>" class="mm-nav-pill <?php echo $mm_current_box == 'inbox' ? 'active' : null ?>">
                        <span class="mm-nav-icon"><i class="fa fa-inbox"></i></span> <?php _e("Eingang", mmg()->domain) ?>
                        <span class="mm-pill-count"><?php echo intval($mm_counts['inbox']); ?></span>
                    </a>
                    <a data-box="unread" href="<?php echo esc_url(add_query_arg('box', 'unread', get_permalink(mmg()->setting()->inbox_page))) ?>" class="mm-nav-pill unread-count <?php echo $mm_current_box == 'unread' ? 'active' : null ?>">
                        <span class="mm-nav-icon"><i class="fa fa-envelope"></i></span> <?php _e("Ungelesen", mmg()->domain) ?>
                        <span class="mm-pill-count"><?php echo intval($mm_counts['unread']); ?></span>
                    </a>
                    <a data-box="read" href="<?php echo esc_url(add_query_arg('box', 'read', get_permalink(mmg()->setting()->inbox_page))) ?>" class="mm-nav-pill read-count <?php echo $mm_current_box == 'read' ? 'active' : null ?>">
                        <span class="mm-nav-icon"><i class="glyphicon glyphicon-eye-open"></i></span> <?php _e("Gelesen", mmg()->domain) ?>
                        <span class="mm-pill-count"><?php echo intval($mm_counts['read']); ?></span>
                    </a>
                    <a data-box="sent" href="<?php echo esc_url(add_query_arg('box', 'sent', get_permalink(mmg()->setting()->inbox_page))) ?>" class="mm-nav-pill <?php echo $mm_current_box == 'sent' ? 'active' : null ?>">
                        <span class="mm-nav-icon"><i class="glyphicon glyphicon-send"></i></span> <?php _e("Gesendet", mmg()->domain) ?>
                        <span class="mm-pill-count"><?php echo intval($mm_counts['sent']); ?></span>
                    </a>
                    <a data-box="archive" href="<?php echo esc_url(add_query_arg('box', 'archive', get_permalink(mmg()->setting()->inbox_page))) ?>" class="mm-nav-pill <?php echo $mm_current_box == 'archive' ? 'active' : null ?>">
                        <span class="mm-nav-icon"><i class="glyphicon glyphicon-briefcase"></i></span> <?php _e("Archiv", mmg()->domain) ?>
                        <span class="mm-pill-count"><?php echo intval($mm_counts['archive']); ?></span>
                    </a>
                </div>
                <?php 
                // Render Storage Widget
                if (!class_exists('MM_Storage_Widget')) {
                    $storage_widget_path = dirname(dirname(__DIR__)) . '/models/mm-storage-widget.php';
                    if (file_exists($storage_widget_path)) {
                        require_once $storage_widget_path;
                    }
                }
                
                if (class_exists('MM_Storage_Widget') && is_user_logged_in()) {
                    echo MM_Storage_Widget::render(get_current_user_id());
                }
                ?>
                <?php if (is_user_logged_in()): ?>
                    <div class="hidden-md hidden-lg mm-mobile-actions">
                        <a class="btn btn-default btn-sm mm-mobile-btn" href="<?php echo esc_url(add_query_arg('box', 'setting')) ?>">
                            <i class="fa fa-gear"></i> <?php _e("Einstellungen", mmg()->domain) ?>
                        </a>
                        <a class="btn btn-primary btn-sm mm-compose mm-mobile-btn" href="#compose-form-container">
                            <span class="mm-compose-icon">+</span> <?php _e("Verfassen", mmg()->domain) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php echo $content; ?>
        <div class="clearfix"></div>
    </div>
    <div class="clearfix"></div>
</div>
<?php if ($show_nav): ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            if ($.fn.tooltip != undefined) {
                $('.mm-tooltip').tooltip({
                    position: {
                        my: "center bottom-15",
                        at: "center top",
                        using: function (position, feedback) {
                            $(this).css(position);
                            $("<div>")
                                .addClass("arrow bottom")
                                .addClass(feedback.vertical)
                                .addClass(feedback.horizontal)
                                .appendTo(this);
                        },
                        open: function (event, ui) {
                        }
                    },
                    tooltipClass: 'ig-container'
                });
            }
        })
    </script>
<?php endif; ?>
<script type="text/javascript">
    jQuery(function ($) {
        var layoutNS = '.mmLayout';

        // Cleanup any prior bindings (in case of re-render)
        $('body').off('click' + layoutNS, '.mm-compose');
        $('body').off('click' + layoutNS, 'a[data-box]');

        // Remove any legacy leanModal overlay on load
        $('#lean_overlay').hide();

        // Toggle inline compose form
        $('body').on('click' + layoutNS, '.mm-compose', function (e) {
            e.preventDefault();
            var target = $('#compose-form-container');
            if (!target.length) { return; }
            $('#lean_overlay').hide();
            var isOpen = target.is(':visible');
            if (isOpen) {
                target.slideUp(150);
                $(this).removeClass('mm-compose-open');
                $(this).find('.mm-compose-icon').text('+');
            } else {
                target.slideDown(150, function () {
                    $('html, body').animate({scrollTop: target.offset().top - 60}, 150);
                    target.find('input,textarea').first().focus();
                });
                $(this).addClass('mm-compose-open');
                $(this).find('.mm-compose-icon').text('−');
            }
        });

        // Tab persistence with simple page load (no AJAX switcher)
        var mmBoxStorageKey = 'mm:last-box';
        var mmInitialBox = '<?php echo esc_js(mmg()->get('box', 'inbox')) ?>';

        $('body').on('click' + layoutNS, 'a[data-box]', function () {
            var box = $(this).data('box');
            if (box && box !== 'setting') {
                localStorage.setItem(mmBoxStorageKey, box);
            }
        });

        // On load, if a stored box differs from current, redirect once to it
        // BUT: never redirect FROM setting tab (let user stay there until they click away)
        var mmStoredBox = localStorage.getItem(mmBoxStorageKey);
        if (mmInitialBox !== 'setting' && mmStoredBox && mmStoredBox !== mmInitialBox && mmStoredBox !== 'setting') {
            var href = $('a[data-box="' + mmStoredBox + '"]').attr('href');
            if (href) { window.location.href = href; }
        } else if (!mmStoredBox) {
            localStorage.setItem(mmBoxStorageKey, mmInitialBox || 'inbox');
        }

        // Context actions (Reply/Archive/Delete) proxy the in-view buttons
        function mmUpdateNavContext() {
            var container = $('.mm-nav-context');
            var replyBtn = $('#mmessage-content .mm-reply-inline:visible').first();
            var archiveBtn = $('#mmessage-content .mm-status').first();
            var deleteBtn = $('#mmessage-content .mm-delete-conv').first();
            var hasAny = replyBtn.length || archiveBtn.length || deleteBtn.length;
            container.toggle(hasAny);
            container.find('.mm-nav-reply').toggle(!!replyBtn.length).off('click').on('click', function () { if (replyBtn.length) { replyBtn.trigger('click'); } });
            container.find('.mm-nav-archive').toggle(!!archiveBtn.length).off('click').on('click', function () { if (archiveBtn.length) { archiveBtn.trigger('click'); } });
            container.find('.mm-nav-delete').toggle(!!deleteBtn.length).off('click').on('click', function () { if (deleteBtn.length) { deleteBtn.trigger('click'); } });
        }

        // Initial bind and after conversation loads
        mmUpdateNavContext();
        $('body').on('ajaxComplete', function () { mmUpdateNavContext(); });
        $('body').on('click', '.load-conv', function () { setTimeout(mmUpdateNavContext, 200); });

        var mmCurrentUserId = <?php echo intval(get_current_user_id()); ?>;

        $('body').on('submit', '.compose-form:not(#compose-form-inline)', function (e) {
            e.preventDefault();
            var that = $(this);
            var sendToField = $('#mm_message_model-send_to');
            var rawRecipients = [];
            
            // Sync Selectize values to hidden input before send and strip self
            if (sendToField.length && sendToField[0].selectize) {
                var selectizeValues = sendToField[0].selectize.getValue();
                rawRecipients = Array.isArray(selectizeValues) ? selectizeValues : (selectizeValues ? [selectizeValues] : []);
            } else {
                var rawVal = sendToField.val();
                rawRecipients = rawVal ? rawVal.split(',') : [];
            }

            var cleanedRecipients = rawRecipients
                .map(function (id) { return String(id).trim(); })
                .filter(function (id) { return id !== '' && id !== String(mmCurrentUserId); });

            sendToField.val(cleanedRecipients.join(','));

            // Block submit when no recipients in new compose
            var isReply = that.find('input[name="MM_Message_Model[conversation_id]"]').length > 0;
            if (!isReply && cleanedRecipients.length === 0) {
                var errorEl = that.find('.error-send_to');
                errorEl.text('<?php echo esc_js(__('You cannot send a message to yourself. Please choose a recipient.', mmg()->domain)); ?>').show();
                sendToField.closest('.form-group').addClass('has-error');
                that.find('button').removeAttr('disabled');
                return false;
            }
            
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                data: $(that).find(":input").serialize(),
                beforeSend: function () {
                    that.find('button').attr('disabled', 'disabled');
                },
                success: function (data) {
                    that.find('.form-group').removeClass('has-error has-success');
                    that.find('button').removeAttr('disabled');
                    if (data.status == 'success') {
                        that.find('.form-control').val('');
                        location.reload();
                    } else {
                        //clear errors
                        that.find('.m-b-none').html('');
                        $.each(data.errors, function (i, v) {
                            var element = that.find('.error-' + i);
                            element.parent().parent().addClass('has-error');
                            element.html(v);
                        });
                        that.find('.form-group').each(function () {
                            if (!$(this).hasClass('has-error')) {
                                $(this).addClass('has-success');
                            }
                        })
                    }
                }
            })
            return false;
        });
        $('body').on('modal.hidden', function () { /* noop */ });
        $('body').on('click', '.load-attachment-info', function (e) {
            e.preventDefault();
            $('.attachments-footer').html('');
            //move the html to footer
            var html = $('[data-id="' + $(this).data('target') + '"]').first().html();
            var element = $('<div/>').attr({
                'class': 'modal',
                'id': $(this).data('target')
            });
            element.html(html);
            $('.attachments-footer').append(element);
            var a = $('<a/>').attr('href', '#' + $(this).data('target'));
            $('.attachments-footer').append(a);
            a.modernModal({
                closeButton: '.attachment-close',
                top: '5%',
                width: '90%',
                maxWidth: 659
            });
            a.trigger('click');
        })
    })
</script>