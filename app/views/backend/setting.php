<div class="wrap">
    <div class="ig-container">
        <div class="mmessage-container">
            <h2><?php _e('Einstellungen', mmg()->domain) ?></h2>

            <div class="row">
                <div class="col-md-12">
                    <?php if ($this->has_flash('setting_save') == 1): ?>
                        <div class="alert alert-success">
                            <?php echo $this->get_flash('setting_save') ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Horizontal Tabs -->
                    <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
                        <li role="presentation" class="<?php echo mmg()->get('tab', 'general') == 'general' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'general')) ?>" role="tab" class="mm-tab-link" data-tab="general" data-target="#general">
                                <i class="glyphicon glyphicon-wrench"></i> <?php _e("Allgemeine Einstellungen", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'email' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'email')) ?>" role="tab" class="mm-tab-link" data-tab="email" data-target="#email">
                                <i class="glyphicon glyphicon-envelope"></i> <?php _e("E-Mail-Einstellungen", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'shortcode' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'shortcode')) ?>" role="tab" class="mm-tab-link" data-tab="shortcode" data-target="#shortcode">
                                <i class="glyphicon glyphicon-cog"></i> <?php _e("Shortcodes", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'attachment' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'attachment')) ?>" role="tab" class="mm-tab-link" data-tab="attachment" data-target="#attachment">
                                <i class="glyphicon glyphicon-paperclip"></i> <?php _e("Anhänge", mmg()->domain) ?>
                            </a>
                        </li>
                        <?php do_action('mm_setting_menu', $model) ?>
                    </ul>
                    
                    <!-- Tab Content with AJAX Loading -->
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane <?php echo mmg()->get('tab', 'general') == 'general' ? 'active' : null ?>" id="general">
                            <?php do_action('mm_setting_general', $model); ?>
                        </div>
                        <div role="tabpanel" class="tab-pane <?php echo mmg()->get('tab') == 'email' ? 'active' : null ?>" id="email">
                            <?php do_action('mm_setting_email', $model); ?>
                        </div>
                        <div role="tabpanel" class="tab-pane <?php echo mmg()->get('tab') == 'shortcode' ? 'active' : null ?>" id="shortcode">
                            <?php do_action('mm_setting_shortcode', $model); ?>
                        </div>
                        <div role="tabpanel" class="tab-pane <?php echo mmg()->get('tab') == 'attachment' ? 'active' : null ?>" id="attachment">
                            <?php do_action('mm_setting_attachment', $model); ?>
                        </div>
                        <div role="tabpanel" class="tab-pane <?php echo mmg()->get('tab') == 'cap' ? 'active' : null ?>" id="cap">
                            <?php do_action('mm_setting_cap', $model); ?>
                        </div>
                        <div role="tabpanel" class="tab-pane <?php echo mmg()->get('tab') == 'filter' ? 'active' : null ?>" id="filter">
                            <?php do_action('mm_setting_filter', $model); ?>
                        </div>
                    </div>
                    
                    <!-- Tab switching handled by admin-tabs.js -->
                </div>
            </div>
        </div>
    </div>
</div>