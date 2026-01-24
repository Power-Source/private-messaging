<?php
// Inline Compose Form - supports both new messages and replies
$model = new MM_Message_Model();
$reply_mode = isset($reply_mode) ? $reply_mode : false;
$conversation_id = isset($conversation_id) ? $conversation_id : null;
?>
<div class="ig-container" id="compose-form-container" style="margin-bottom:16px; display:none;" data-reply-mode="<?php echo $reply_mode ? '1' : '0'; ?>">
    <div class="panel panel-default" style="border-radius:10px;border-color:#e5e7eb;box-shadow:0 4px 16px rgba(0,0,0,0.04);">
        <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #e5e7eb;">
            <strong><?php echo $reply_mode ? __('Antworten', mmg()->domain) : __('Nachricht verfassen', mmg()->domain); ?></strong>
            <button type="button" class="close" id="compose-close-btn" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="panel-body">
            <form method="post" class="compose-form" id="compose-form-inline">
                <?php if ($reply_mode && $conversation_id): ?>
                    <input type="hidden" name="MM_Message_Model[conversation_id]" value="<?php echo esc_attr($conversation_id); ?>">
                <?php endif; ?>
                
                <div class="form-group compose-field-sendto <?php echo $model->has_error("send_to") ? "has-error" : null ?>" <?php echo $reply_mode ? 'style="display:none;"' : ''; ?>>
                    <label for="mm_message_model-send_to" class="control-label hidden-xs hidden-sm"><?php _e("Empfänger", mmg()->domain); ?></label>
                    <input type="text" name="MM_Message_Model[send_to]" id="mm_message_model-send_to" class="form-control" placeholder="<?php echo esc_attr__('Empfänger (Benutzer suchen)', mmg()->domain); ?>" value="<?php echo esc_attr($model->send_to); ?>" list="mm-user-list" autocomplete="off">
                    <datalist id="mm-user-list"></datalist>
                    <?php do_action('mm_compose_form_after_send_to', $model) ?>
                    <span class="help-block m-b-none error-send_to"><?php echo esc_html($model->get_error("send_to")); ?></span>
                </div>

                <?php do_action('mm_before_subject_field', $model, 'compose_form') ?>
                <div class="form-group compose-field-subject <?php echo $model->has_error("subject") ? "has-error" : null ?>" <?php echo $reply_mode ? 'style="display:none;"' : ''; ?>>
                    <label for="mm_message_model-subject" class="control-label hidden-xs hidden-sm"><?php _e("Betreff", mmg()->domain); ?></label>
                    <input type="text" name="MM_Message_Model[subject]" id="mm_message_model-subject" class="form-control" placeholder="<?php echo esc_attr__('Betreff', mmg()->domain); ?>" value="<?php echo esc_attr($model->subject); ?>">
                    <?php do_action('mm_compose_form_after_subject', $model) ?>
                    <span class="help-block m-b-none error-subject"><?php echo esc_html($model->get_error("subject")); ?></span>
                </div>

                <div class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
                    <label for="mm_compose_content" class="control-label hidden-xs hidden-sm"><?php _e("Inhalt", mmg()->domain); ?></label>
                    <textarea name="MM_Message_Model[content]" id="mm_compose_content" class="form-control mm_wsysiwyg" style="min-height:100px" placeholder="<?php echo esc_attr__('Inhalt', mmg()->domain); ?>"><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                    <?php do_action('mm_compose_form_after_content', $model) ?>
                    <span class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>
                </div>

                <?php wp_nonce_field('compose_message'); ?>
                <input type="hidden" name="MM_Message_Model[attachment]" id="mm_message_model-attachment" value="<?php echo esc_attr($model->attachment ?? ''); ?>">
                <input type="hidden" name="action" value="mm_send_message">

                <?php if (mmg()->can_upload() == true) { ?>
                <div class="form-group">
                    <label class="control-label hidden-xs hidden-sm"><?php _e("Anhänge", mmg()->domain); ?></label>
                    <div class="mm-attachments-control">
                        <input type="file" id="mm-attachment-input" class="mm-attachment-input" multiple style="display:none;">
                        <button type="button" class="btn btn-default btn-sm" id="mm-attachment-browse" onclick="document.getElementById('mm-attachment-input').click(); return false;"><?php _e("Dateien auswählen", mmg()->domain) ?></button>
                        <span class="mm-attachment-status" style="margin-left:10px;color:#666;font-size:12px;"></span>
                        <div id="mm-attachments-list" class="mm-attachments-list" style="margin-top:8px;"></div>
                        <input type="hidden" name="MM_Message_Model[attachment]" id="mm-message-model-attachment" value="">
                    </div>
                </div>
                <?php } ?>

                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding-top:6px;">
                    <button type="button" class="btn btn-default" id="compose-close-btn-bottom"><?php _e("Abbrechen", mmg()->domain) ?></button>
                    <button type="submit" class="btn btn-primary compose-submit"><?php _e("Senden", mmg()->domain) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    // Global attachment tracking
    window.mmAttachmentIds = [];
    window.mmAttachmentNames = {};
    var mmCurrentUserId = <?php echo intval(get_current_user_id()); ?>;

    // Initialize on jQuery ready
    jQuery(document).ready(function($) {
        console.log('=== MM Compose jQuery Init ===');
        
        // Remove any previously bound submit handlers to prevent duplicates
        $(document).off('submit', '#compose-form-inline');
        
        // Handle form submit via AJAX
        $(document).on('submit', '#compose-form-inline', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            var form = $(this);

            // Normalize and validate recipients before building FormData
            var sendToField = $('#mm_message_model-send_to');
            var rawRecipients = [];

            if (sendToField.length) {
                if (sendToField[0].selectize) {
                    var selectizeValue = sendToField[0].selectize.getValue();
                    rawRecipients = Array.isArray(selectizeValue) ? selectizeValue : (selectizeValue ? [selectizeValue] : []);
                } else {
                    var rawVal = sendToField.val();
                    rawRecipients = rawVal ? rawVal.split(',') : [];
                }
            }

            // Strip current user and empties
            var cleanedRecipients = rawRecipients
                .map(function (id) { return String(id).trim(); })
                .filter(function (id) { return id !== '' && id !== String(mmCurrentUserId); });

            // Push cleaned list back to field for submission
            sendToField.val(cleanedRecipients.join(','));

            // Inline error when no valid recipients remain
            if (cleanedRecipients.length === 0 && !$('#compose-form-container').data('reply-mode')) {
                var errorEl = form.find('.error-send_to');
                errorEl.text('<?php echo esc_js(__('Du kannst keine Nachricht an dich selbst senden. Bitte wähle einen Empfänger.', mmg()->domain)); ?>').show();
                sendToField.closest('.form-group').addClass('has-error');
                return;
            }

            var formData = new FormData(this);
            
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Submit response:', response);
                    
                    if (response.status === 'success') {
                        // Close and reset form
                        var container = $('#compose-form-container');
                        container.hide();
                        container.attr('data-reply-mode', '0');
                        container.find('.panel-heading strong').text('<?php echo esc_js(__('Nachricht verfassen', mmg()->domain)); ?>');
                        container.find('.compose-field-sendto, .compose-field-subject').show();
                        form.find('input[name="MM_Message_Model[conversation_id]"]').remove();
                        form.reset();
                        window.mmAttachmentIds = [];
                        window.mmAttachmentNames = {};
                        $('#mm-attachments-list').html('');
                        
                        // Refresh tab counts and inbox list WITHOUT reloading compose form
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php') ?>',
                            data: { action: 'mm_load_box', box: '<?php echo mmg()->get('box', 'inbox') ?>', _wpnonce: '<?php echo wp_create_nonce('mm_load_box') ?>' },
                            success: function(data) {
                                // Only update the message list, not the entire container
                                // This prevents the compose form from being reloaded and re-initialized
                                if (data.html) {
                                    var newList = $(data.html).find('#mmessage-list').html();
                                    if (newList) {
                                        $('#mmessage-list').html(newList);
                                    }
                                }
                                // Trigger page title/tab count update by reloading just the current view
                                location.reload();
                            }
                        });
                    } else {
                        console.error('Validation errors:', response.errors);
                        // Display errors
                        if (response.errors) {
                            $.each(response.errors, function(field, message) {
                                var errorEl = form.find('.error-' + field);
                                if (errorEl.length) {
                                    errorEl.text(message).show();
                                    form.find('[name*="[' + field + ']"]').closest('.form-group').addClass('has-error');
                                }
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('Error sending message');
                }
            });
        });
        
        // Initialize Selectize for Send To field
        var sendToField = $('#mm_message_model-send_to');
        if (sendToField.length && $.fn.selectize) {
            console.log('Initializing Selectize');
            try {
                sendToField.selectize({
                    valueField: 'id',
                    labelField: 'name',
                    searchField: 'name',
                    options: [],
                    create: false,
                    load: function (query, callback) {
                        if (!query.length) return callback();
                        var instance = sendToField[0].selectize;
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php?action=mm_suggest_users&_wpnonce='.wp_create_nonce('mm_suggest_users')) ?>',
                            data: { 'query': query },
                            beforeSend: function () {
                                instance.$control.append('<i style="position: absolute;right: 10px;" class="fa fa-circle-o-notch fa-spin"></i>');
                            },
                            success: function (data) {
                                instance.$control.find('i').remove();
                                callback(data);
                            },
                            error: function() {
                                instance.$control.find('i').remove();
                                callback([]);
                            }
                        });
                    }
                });
            } catch(e) {
                console.error('Selectize error:', e);
            }
        } else {
            console.warn('Selectize or field not found');
        }
    });

    // Initialize on DOM ready for attachments
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== MM Compose Init (Attachments) ===');
        
        var fileInput = document.getElementById('mm-attachment-input');
        var statusEl = document.querySelector('.mm-attachment-status');
        var listEl = document.getElementById('mm-attachments-list');
        
        console.log('File input:', fileInput);
        console.log('Status el:', statusEl);
        console.log('List el:', listEl);
        
        // Use jQuery for event delegation to handle dynamic changes
        jQuery(document).on('change', '#mm-attachment-input', function(e) {
            console.log('FILE INPUT CHANGED - Files count:', this.files.length);
            var files = this.files;
            
            for (var i = 0; i < files.length; i++) {
                console.log('Uploading file:', files[i].name, 'Size:', files[i].size);
                uploadAttachment(files[i]);
            }
            
            // Reset input
            this.value = '';
        });
        
        function uploadAttachment(file) {
            console.log('START uploadAttachment:', file.name);
            
            var formData = new FormData();
            formData.append('action', 'mm_upload_attachment');
            formData.append('file', file);
            formData.append('conversation_id', 0);
            formData.append('_wpnonce', '<?php echo wp_create_nonce('mm_upload_attachment') ?>');
            
            var statusEl = document.querySelector('.mm-attachment-status');
            if (statusEl) {
                statusEl.textContent = 'Uploading ' + file.name + '...';
                statusEl.style.color = '#333';
            }
            
            fetch('<?php echo admin_url('admin-ajax.php') ?>', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                console.log('Response received:', response.status);
                return response.json();
            })
            .then(function(data) {
                console.log('Response JSON:', data);
                
                if (data.success) {
                    console.log('Upload SUCCESS');
                    var fileData = data.data;
                    window.mmAttachmentIds.push(fileData.filename);
                    updateAttachmentList(fileData);
                    updateAttachmentField();
                    if (statusEl) {
                        statusEl.textContent = '';
                    }
                } else {
                    console.log('Upload FAILED:', data.data);
                    if (statusEl) {
                        statusEl.textContent = 'Error: ' + (data.data || 'Unknown error');
                        statusEl.style.color = '#d9534f';
                    }
                }
            })
            .catch(function(error) {
                console.error('FETCH ERROR:', error);
                var statusEl = document.querySelector('.mm-attachment-status');
                if (statusEl) {
                    statusEl.textContent = 'Upload failed: ' + error.message;
                    statusEl.style.color = '#d9534f';
                }
            });
        }
        
        function updateAttachmentList(fileData) {
            console.log('updateAttachmentList:', fileData);
            
            var listEl = document.getElementById('mm-attachments-list');
            var fileSizeKB = (fileData.size / 1024).toFixed(1);
            var isImage = /\.(jpg|jpeg|png|gif)$/i.test(fileData.original_name);
            
            var itemDiv = document.createElement('div');
            itemDiv.className = 'mm-attachment-item';
            itemDiv.setAttribute('data-filename', fileData.filename);
            itemDiv.style.cssText = 'padding:12px;background:#f5f5f5;border-radius:4px;margin-bottom:10px;border:1px solid #ddd;';
            
            // Image preview - load via AJAX
            if (isImage) {
                var previewDiv = document.createElement('div');
                previewDiv.style.marginBottom = '8px';
                
                var img = document.createElement('img');
                img.style.cssText = 'max-width:150px;max-height:120px;border-radius:4px;border:1px solid #ccc;';
                previewDiv.appendChild(img);
                itemDiv.appendChild(previewDiv);
                
                // Load preview data via AJAX
                fetch('<?php echo admin_url('admin-ajax.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=mm_preview_attachment&conversation_id=0&filename=' + encodeURIComponent(fileData.filename) + '&_wpnonce=<?php echo wp_create_nonce('mm_preview_attachment') ?>'
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success && data.data.data_url) {
                        img.src = data.data.data_url;
                    }
                })
                .catch(function(err) {
                    console.warn('Preview load error:', err);
                });
            }
            
            // File info row
            var infoRow = document.createElement('div');
            infoRow.className = 'mm-info-row';
            infoRow.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;';
            
            var nameSpan = document.createElement('span');
            nameSpan.className = 'mm-file-name';
            nameSpan.textContent = fileData.original_name + ' (' + fileSizeKB + ' KB)';
            nameSpan.style.cssText = 'flex:1;font-size:12px;word-break:break-all;';
            
            var renameBtn = document.createElement('button');
            renameBtn.type = 'button';
            renameBtn.className = 'btn btn-xs btn-info';
            renameBtn.textContent = 'Rename';
            renameBtn.style.margin = '0 4px';
            
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-xs btn-danger';
            removeBtn.textContent = 'Remove';
            
            infoRow.appendChild(nameSpan);
            infoRow.appendChild(renameBtn);
            infoRow.appendChild(removeBtn);
            itemDiv.appendChild(infoRow);
            
            // Rename form (hidden)
            var renameForm = document.createElement('div');
            renameForm.className = 'mm-rename-form';
            renameForm.style.cssText = 'display:none;margin-top:8px;display:flex;gap:4px;';
            
            var renameInput = document.createElement('input');
            renameInput.type = 'text';
            renameInput.className = 'form-control';
            renameInput.placeholder = 'New name';
            renameInput.value = fileData.original_name;
            renameInput.style.cssText = 'flex:1;font-size:12px;';
            
            var saveBtn = document.createElement('button');
            saveBtn.type = 'button';
            saveBtn.className = 'btn btn-xs btn-success';
            saveBtn.textContent = 'Save';
            
            var cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-xs btn-default';
            cancelBtn.textContent = 'Cancel';
            
            renameForm.appendChild(renameInput);
            renameForm.appendChild(saveBtn);
            renameForm.appendChild(cancelBtn);
            itemDiv.appendChild(renameForm);
            
            // Event listeners
            renameBtn.addEventListener('click', function(e) {
                e.preventDefault();
                infoRow.style.display = 'none';
                renameForm.style.display = 'flex';
                renameInput.focus();
            });
            
            saveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var newName = renameInput.value.trim();
                if (newName && newName !== fileData.original_name) {
                    fileData.original_name = newName;
                    nameSpan.textContent = newName + ' (' + fileSizeKB + ' KB)';
                    window.mmAttachmentNames[fileData.filename] = newName;
                }
                renameForm.style.display = 'none';
                infoRow.style.display = 'flex';
            });
            
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                renameInput.value = fileData.original_name;
                renameForm.style.display = 'none';
                infoRow.style.display = 'flex';
            });
            
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var filename = fileData.filename;
                var idx = window.mmAttachmentIds.indexOf(filename);
                if (idx > -1) {
                    window.mmAttachmentIds.splice(idx, 1);
                }
                if (window.mmAttachmentNames[filename]) {
                    delete window.mmAttachmentNames[filename];
                }
                itemDiv.style.opacity = '0.5';
                setTimeout(function() {
                    itemDiv.remove();
                }, 150);
                updateAttachmentField();
            });
            
            listEl.appendChild(itemDiv);
        }
        
        function updateAttachmentField() {
            var field = document.getElementById('mm-message-model-attachment');
            if (field) {
                field.value = window.mmAttachmentIds.join(',');
            }
        }
    });
    
    // Close button handlers (delegated via jQuery for better reliability)
    jQuery(document).ready(function($) {
        $(document).on('click', '#compose-close-btn, #compose-close-btn-bottom', function(e) {
            e.preventDefault();
            console.log('Close button clicked');
            var container = $('#compose-form-container');
            container.hide();
            // Reset to compose mode
            container.attr('data-reply-mode', '0');
            container.find('.panel-heading strong').text('<?php echo esc_js(__('Nachricht verfassen', mmg()->domain)); ?>');
            container.find('.compose-field-sendto, .compose-field-subject').show();
            // Remove conversation_id if exists
            container.find('input[name="MM_Message_Model[conversation_id]"]').remove();
            // Clear form
            $('#compose-form-inline')[0].reset();
            window.mmAttachmentIds = [];
            window.mmAttachmentNames = {};
            $('#mm-attachments-list').html('');
            var field = document.getElementById('mm-message-model-attachment');
            if (field) {
                field.value = '';
            }
        });
    });
</script>
<?php do_action('mm_compose_form_end') ?>
