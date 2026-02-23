<?php
// Legacy model for validation errors
$model = new MM_Message_Model();
$form_id = 'compose-form-admin-bar';
?>

<div class="ig-container">
    <div class="mmessage-container">
        <div>
            <div class="modal" id="compose-form-container-admin-bar">
                <div class="modal-dialog">
                    <div class="modal-content" id="compose-modal-admin-bar">
                        <div class="modal-header">
                            <h4 class="modal-title"><?php _e("Nachricht verfassen", mmg()->domain) ?></h4>
                        </div>
                        <form method="post" class="form-horizontal" id="<?php echo $form_id ?>">
                        <div class="modal-body">

                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("send_to") ? "has-error" : null ?>">
                                <label for="admin-bar-mm-send-to" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Empfänger", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <input type="text"
                                           name="MM_Message_Model[send_to]"
                                           id="admin-bar-mm-send-to"
                                           class="form-control"
                                           placeholder="<?php echo esc_attr__('Empfänger', mmg()->domain); ?>"
                                           value="<?php echo esc_attr($model->send_to); ?>">
                                    <span
                                        class="help-block m-b-none error-send_to"><?php echo esc_html($model->get_error("send_to")); ?></span>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <?php do_action('mm_before_subject_field', $model, 'admin-bar') ?>
                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("subject") ? "has-error" : null ?>">
                                <label for="mm_message_model-subject" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Betreff", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <input type="text"
                                           name="MM_Message_Model[subject]"
                                           id="mm_message_model-subject"
                                           class="form-control"
                                           placeholder="<?php echo esc_attr__('Betreff', mmg()->domain); ?>"
                                           value="<?php echo esc_attr($model->subject); ?>">
                                    <span
                                        class="help-block m-b-none error-subject"><?php echo esc_html($model->get_error("subject")); ?></span>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
                                <label for="mm_compose_content" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Inhalt", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <textarea
                                        name="MM_Message_Model[content]"
                                        id="mm_compose_content"
                                        class="form-control mm_wsysiwyg"
                                        style="min-height:160px"
                                        rows="8"
                                        placeholder="<?php echo esc_attr__('Inhalt', mmg()->domain); ?>"
                                    ><?php echo esc_textarea(isset($model->content) ? $model->content : ''); ?></textarea>
                                    <span
                                        class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <?php wp_nonce_field('compose_message'); ?>
                            <input type="hidden" name="action" value="mm_send_message">

                            <?php if (mmg()->can_upload() == true) { ?>
                            <div class="form-group">
                                <label class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Anhänge", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <div class="mm-attachments-control">
                                        <input type="file" id="mm-attachment-input-<?php echo $form_id ?>" class="mm-attachment-input" multiple style="display:none;">
                                        <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('mm-attachment-input-<?php echo $form_id ?>').click(); return false;"><?php _e("Dateien auswählen", mmg()->domain) ?></button>
                                        <span class="mm-attachment-status-<?php echo $form_id ?>" style="margin-left:10px;color:#666;font-size:12px;"></span>
                                        <div id="mm-attachments-list-<?php echo $form_id ?>" class="mm-attachments-list" style="margin-top:8px;"></div>
                                        <input type="hidden" name="MM_Message_Model[attachment]" id="mm-message-model-attachment-<?php echo $form_id ?>" value="">
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default compose-close"
                                    data-dismiss="modal"><?php _e("Schließen", mmg()->domain) ?></button>
                            <button type="submit"
                                    class="btn btn-primary compose-submit"><?php _e("Senden", mmg()->domain) ?></button>
                        </div>
                        </form>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        </div>
    </div>
</div>
<!-- /.modal -->

<script type="text/javascript">
    (function() {
        'use strict';
        var formId = '<?php echo $form_id ?>';
        var attachmentIds = [];
        var modalEl = document.getElementById('compose-form-container-admin-bar');
        var overlayEl = null;

        function initAdminBarCompose() {
            console.log('Initializing Admin Bar Compose Modal');
            console.log('Modal element:', modalEl);
            
            // Use event delegation for admin bar link (loaded dynamically)
            document.addEventListener('click', function(e) {
                // Check if clicked element or parent has the compose classes
                var targetEl = e.target;
                
                // Walk up the tree to find a link with our class
                while (targetEl && targetEl !== document) {
                    if (targetEl.matches && targetEl.matches('a')) {
                        var parentLi = targetEl.closest('#wp-admin-bar-mm-compose-button');
                        if (parentLi) {
                            console.log('Compose link clicked via parent!', targetEl);
                            e.preventDefault();
                            e.stopPropagation();
                            showModal();
                            return;
                        }
                    }
                    targetEl = targetEl.parentElement;
                }
            }, true); // Use capture phase to catch event earlier

            // Close button handlers
            if (modalEl) {
                var closeButtons = modalEl.querySelectorAll('.compose-close, [data-dismiss="modal"]');
                closeButtons.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        hideModal();
                    });
                });
            }
        }

        function showModal() {
            console.log('showModal called');
            console.log('modalEl exists:', !!modalEl);
            
            if (!modalEl) {
                console.error('Modal element not found!');
                return;
            }
            
            // Create overlay if needed
            if (!overlayEl) {
                console.log('Creating overlay');
                overlayEl = document.createElement('div');
                overlayEl.id = 'mm_modal_overlay';
                document.body.appendChild(overlayEl);
                overlayEl.addEventListener('click', hideModal);
            }
            
            console.log('Showing modal and overlay');
            overlayEl.style.display = 'block';
            modalEl.style.display = 'block';
            document.body.style.overflow = 'hidden';
            console.log('Modal display:', modalEl.style.display);
            console.log('Overlay display:', overlayEl.style.display);
        }

        function hideModal() {
            if (overlayEl) overlayEl.style.display = 'none';
            if (modalEl) modalEl.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Attachment upload functionality
        <?php if (mmg()->can_upload()): ?>
        (function() {
            // Attachment upload handler
            var attachmentInput = document.getElementById('mm-attachment-input-' + formId);
            if (attachmentInput) {
                attachmentInput.addEventListener('change', function() {
                    var files = this.files;
                    for (var i = 0; i < files.length; i++) {
                        uploadAttachment(files[i]);
                    }
                    this.value = '';
                });
            }

        function uploadAttachment(file) {
            var formData = new FormData();
            formData.append('action', 'mm_upload_attachment');
            formData.append('file', file);
            formData.append('conversation_id', 0);
            formData.append('_wpnonce', '<?php echo wp_create_nonce("mm_upload_attachment") ?>');

            var statusEl = document.querySelector('.mm-attachment-status-' + formId);
            if (statusEl) {
                statusEl.textContent = '<?php echo esc_js(__("Uploading", mmg()->domain)) ?> ' + file.name + '...';
                statusEl.style.color = '#333';
            }

            fetch('<?php echo admin_url("admin-ajax.php") ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var fileData = data.data;
                    attachmentIds.push(fileData.filename);
                    updateAttachmentList(fileData);
                    updateAttachmentField();
                    if (statusEl) statusEl.textContent = '';
                } else {
                    if (statusEl) {
                        statusEl.textContent = '<?php echo esc_js(__("Fehler", mmg()->domain)) ?>: ' + (data.data || '<?php echo esc_js(__("Unbekannter Fehler", mmg()->domain)) ?>');
                        statusEl.style.color = '#d9534f';
                    }
                }
            })
            .catch(function() {
                if (statusEl) {
                    statusEl.textContent = '<?php echo esc_js(__("Upload fehlgeschlagen", mmg()->domain)) ?>';
                    statusEl.style.color = '#d9534f';
                }
            });
        }

        function updateAttachmentList(fileData) {
            var listEl = document.getElementById('mm-attachments-list-' + formId);
            if (!listEl) return;
            
            var fileSizeKB = (fileData.size / 1024).toFixed(1);
            var itemDiv = document.createElement('div');
            itemDiv.className = 'mm-attachment-item';
            itemDiv.setAttribute('data-filename', fileData.filename);
            itemDiv.style.cssText = 'padding:8px;background:#f5f5f5;border-radius:4px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;';
            
            itemDiv.innerHTML = '<span style="font-size:12px;">' + fileData.display_name + ' (' + fileSizeKB + ' KB)</span>' +
                '<button type="button" class="btn btn-xs btn-danger mm-remove-attachment" data-filename="' + fileData.filename + '">×</button>';
            
            listEl.appendChild(itemDiv);
        }

        function updateAttachmentField() {
            var field = document.getElementById('mm-message-model-attachment-' + formId);
            if (field) {
                field.value = attachmentIds.join(',');
            }
        }

            // Remove attachment handler (event delegation)
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('mm-remove-attachment')) {
                    e.preventDefault();
                    var filename = e.target.getAttribute('data-filename');
                    var index = attachmentIds.indexOf(filename);
                    if (index > -1) {
                        attachmentIds.splice(index, 1);
                    }
                    var item = e.target.closest('.mm-attachment-item');
                    if (item) item.remove();
                    updateAttachmentField();

                    // Delete on server
                    fetch('<?php echo admin_url("admin-ajax.php") ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'mm_delete_attachment',
                            conversation_id: 0,
                            filename: filename,
                            _wpnonce: '<?php echo wp_create_nonce("mm_delete_attachment") ?>'
                        }),
                        credentials: 'same-origin'
                    });
                }
            });
        })();
        <?php endif; ?>

        // Form submit handler
        var form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var buttons = modalEl.querySelectorAll('button');
                
                // Disable buttons
                buttons.forEach(function(btn) { btn.disabled = true; });

                fetch('<?php echo admin_url("admin-ajax.php") ?>', {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    credentials: 'same-origin'
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    // Reset error states
                    form.querySelectorAll('.form-group').forEach(function(group) {
                        group.classList.remove('has-error', 'has-success');
                    });
                    
                    // Re-enable buttons
                    buttons.forEach(function(btn) { btn.disabled = false; });

                    if (data.status == 'success') {
                        form.querySelectorAll('.form-control').forEach(function(input) {
                            if (input.tagName !== 'SELECT') input.value = '';
                        });
                        location.reload();
                    } else if (data.errors) {
                        Object.keys(data.errors).forEach(function(key) {
                            var errorEl = form.querySelector('.error-' + key);
                            if (errorEl) {
                                var formGroup = errorEl.closest('.form-group');
                                if (formGroup) formGroup.classList.add('has-error');
                                errorEl.innerHTML = data.errors[key];
                            }
                        });
                        // Mark success for fields without errors
                        form.querySelectorAll('.form-group').forEach(function(group) {
                            if (!group.classList.contains('has-error')) {
                                var errorEl = group.querySelector('.m-b-none');
                                if (errorEl) errorEl.textContent = '';
                                group.classList.add('has-success');
                            }
                        });
                    }
                })
                .catch(function() {
                    buttons.forEach(function(btn) { btn.disabled = false; });
                });
            });
        }

        // Initialize Tom-Select for recipient field
        var recipientField = document.getElementById('admin-bar-mm-send-to');
        if (recipientField && typeof TomSelect !== 'undefined') {
            new TomSelect(recipientField, {
                valueField: 'id',
                labelField: 'name',
                searchField: 'name',
                create: false,
                load: function(query, callback) {
                    if (!query.length) return callback();
                    
                    fetch('<?php echo admin_url("admin-ajax.php") ?>?action=mm_suggest_users&_wpnonce=<?php echo wp_create_nonce("mm_suggest_users") ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ query: query, page: 1 }),
                        credentials: 'same-origin'
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) { callback(data); })
                    .catch(function() { callback(); });
                }
            });
        }

        // Initialize everything when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAdminBarCompose);
        } else {
            initAdminBarCompose();
        }
    })();
</script>