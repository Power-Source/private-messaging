<div class="tab-pane active">
    <div class="page-header" style="margin-top: 0">
        <h3> <?php _e("Allgemeine Optionen", mmg()->domain) ?></h3>
    </div>

    <form method="post" class="form-horizontal">
    <div class="form-group <?php echo $model->has_error("enable_receipt") ? "has-error" : null ?>">
        <label for="mm_setting_model-enable_receipt" class="col-lg-2 control-label"><?php _e("Lesebestätigung aktivieren", mmg()->domain); ?></label>
        <div class="col-lg-10">
            <div class="checkbox">
                <label>
                    <input type="hidden" name="MM_Setting_Model[enable_receipt]" value="0">
                    <input type="checkbox" 
                           name="MM_Setting_Model[enable_receipt]" 
                           id="mm_setting_model-enable_receipt" 
                           value="1"
                           <?php checked($model->enable_receipt, 1); ?>>
                    <?php _e("Dieses Kästchen aktivieren, um E-Mail-Benachrichtigungen über gelesene Nachrichten zu erhalten.", mmg()->domain) ?>
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="form-group <?php echo $model->has_error("user_receipt") ? "has-error" : null ?>">
        <label for="mm_setting_model-user_receipt" class="col-lg-2 control-label"><?php _e("Benutzern erlauben, Lesebestätigungen zu deaktivieren?", mmg()->domain); ?></label>
        <div class="col-lg-10">
            <div class="checkbox">
                <label>
                    <input type="hidden" name="MM_Setting_Model[user_receipt]" value="0">
                    <input type="checkbox" 
                           name="MM_Setting_Model[user_receipt]" 
                           id="mm_setting_model-user_receipt" 
                           value="1"
                           <?php checked($model->user_receipt, 1); ?>>
                    <?php _e("Dies ermöglicht dem Benutzer, Lesebestätigungen zu aktivieren oder zu deaktivieren.", mmg()->domain) ?>
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="page-header" style="margin-top: 0">
        <h4><?php _e('Seite erstellen', mmg()->domain) ?></h4>
    </div>
    <div class="form-group">
        <label class="col-md-3 control-label"><?php _e('PS System Seite', mmg()->domain) ?></label>

        <div class="col-md-9">
            <div class="row">
                <div class="col-md-6">
                    <select name="MM_Setting_Model[inbox_page]" id="mm_setting_model-inbox_page" class="form-control">
                        <option value=""><?php _e('--Auswählen--', mmg()->domain); ?></option>
                        <?php
                        $pages = get_pages();
                        foreach ($pages as $page) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($page->ID),
                                selected($model->inbox_page, $page->ID, false),
                                esc_html($page->post_title)
                            );
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="button" data-id="inbox"
                            class="button button-primary mm-create-page"><?php _e('Seite erstellen', mmg()->domain) ?></button>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <?php wp_nonce_field('mm_settings','_mmnonce') ?>
    <div class="page-header" style="margin-top: 0">
        <h4><?php _e('Speicher pro Benutzer', mmg()->domain) ?></h4>
    </div>
    <div class="form-group <?php echo $model->has_error("storage_unlimited") ? "has-error" : null ?>">
        <label for="mm_setting_model-storage_unlimited" class="col-lg-2 control-label"><?php _e("Unbegrenzter Speicher", mmg()->domain); ?></label>
        <div class="col-lg-10">
            <div class="checkbox">
                <label>
                    <input type="hidden" name="MM_Setting_Model[storage_unlimited]" value="0">
                    <input type="checkbox" 
                           name="MM_Setting_Model[storage_unlimited]" 
                           id="mm_setting_model-storage_unlimited" 
                           value="1"
                           <?php checked($model->storage_unlimited, 1); ?>
                           onchange="document.getElementById('storage_limit_group').style.display = this.checked ? 'none' : 'block';">
                    <?php _e("Benutzern erlauben, unbegrenzten Speicherplatz für Nachrichten und Anhänge zu haben.", mmg()->domain) ?>
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div id="storage_limit_group" class="form-group <?php echo $model->has_error("storage_limit") ? "has-error" : null ?>" style="<?php echo $model->storage_unlimited ? 'display:none;' : ''; ?>">
        <label for="mm_setting_model-storage_limit" class="col-lg-2 control-label"><?php _e("Speicherlimit", mmg()->domain); ?></label>
        <div class="col-lg-10">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="number" 
                               min="1" 
                               max="1000"
                               name="MM_Setting_Model[storage_limit_display]" 
                               id="mm_setting_model-storage_limit" 
                               class="form-control"
                               value="<?php echo $model->storage_unit === 'GB' ? intval($model->storage_limit / (1024*1024*1024)) : intval($model->storage_limit / (1024*1024)); ?>"
                               placeholder="50">
                        <span class="input-group-btn">
                            <select name="MM_Setting_Model[storage_unit]" class="form-control" onchange="updateStorageLimit()">
                                <option value="MB" <?php selected($model->storage_unit, 'MB'); ?>>MB</option>
                                <option value="GB" <?php selected($model->storage_unit, 'GB'); ?>>GB</option>
                            </select>
                        </span>
                    </div>
                    <small class="form-text text-muted"><?php _e("Standard: 50 MB", mmg()->domain) ?></small>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <!-- Hidden field to store actual byte value -->
    <input type="hidden" name="MM_Setting_Model[storage_limit]" id="storage_limit_bytes" value="<?php echo esc_attr($model->storage_limit); ?>">
    <div class="page-header" style="margin-top: 0">
        <h4><?php _e('Add-ons', mmg()->domain) ?></h4>
    </div>
    <div class="alert alert-success plugin-status hide">

    </div>
    <?php $tbl = new MM_AddOn_Table();
    $tbl->prepare_items();
    $tbl->display();
    ?>
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary"><?php _e("Änderungen speichern", mmg()->domain) ?></button>
        </div>
    </div>
    </form>
</div>
<script type="text/javascript">
    function updateStorageLimit() {
        const displayValue = document.getElementById('mm_setting_model-storage_limit').value;
        const unit = document.querySelector('select[name="MM_Setting_Model[storage_unit]"]').value;
        const bytesField = document.getElementById('storage_limit_bytes');
        
        let bytes = 0;
        if (unit === 'MB') {
            bytes = displayValue * 1024 * 1024;
        } else if (unit === 'GB') {
            bytes = displayValue * 1024 * 1024 * 1024;
        }
        
        bytesField.value = bytes;
    }
    
    jQuery(document).ready(function ($) {
        // Initialize storage limit on form changes
        $('input[name="MM_Setting_Model[storage_limit_display]"], select[name="MM_Setting_Model[storage_unit]"]').on('change', updateStorageLimit);
        
        // Ensure conversion when form is submitted
        $('form').on('submit', function() {
            updateStorageLimit();
        });
        
        $('.mm-plugin').on('click', function (e) {
            var that = $(this);
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                data: {
                    action: 'mm_plugin_action',
                    id: $(this).data('id')
                },
                beforeSend:function(){
                    that.find('.loader-ani').removeClass('hide');
                },
                success: function (data) {
                    that.find('.loader-ani').addClass('hide');
                    $('.plugin-status').html(data.noty);
                    $('.plugin-status').removeClass('hide');
                    that.text(data.text);
                }
            })
        });
        $('.mm-create-page').on('click', function (e) {
            var that = $(this);
            $.ajax({
                type: 'POST',
                data: {
                    m_type: $(this).data('id'),
                    action: 'mm_create_message_page'
                },
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                beforeSend: function () {
                    that.attr('disabled', 'disabled').text('<?php echo esc_js(__('Erstellen...',mmg()->domain)) ?>');
                },
                success: function (data) {
                    var element = that.parent().parent().find('select').first();
                    $.get("<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>", function (html) {
                        html = $(html);
                        var clone = html.find('select[name="' + element.attr('name') + '"]');
                        element.replaceWith(clone);
                        that.removeAttr('disabled').text('<?php echo esc_js(__('Seite erstellen',mmg()->domain)) ?>');
                    })
                }
            })
        })
    })
</script>