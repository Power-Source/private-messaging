<div class="wrap">
    <div class="ig-container">
        <div class="mmessage-container">
            <h2><?php _e('Settings', mmg()->domain) ?></h2>

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
                                <i class="glyphicon glyphicon-wrench"></i> <?php _e("General Settings", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'email' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'email')) ?>" role="tab" class="mm-tab-link" data-tab="email" data-target="#email">
                                <i class="glyphicon glyphicon-envelope"></i> <?php _e("Email Settings", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'shortcode' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'shortcode')) ?>" role="tab" class="mm-tab-link" data-tab="shortcode" data-target="#shortcode">
                                <i class="glyphicon glyphicon-cog"></i> <?php _e("Shortcodes", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'attachment' ? 'active' : null ?>">
                            <a href="<?php echo esc_url(add_query_arg('tab', 'attachment')) ?>" role="tab" class="mm-tab-link" data-tab="attachment" data-target="#attachment">
                                <i class="glyphicon glyphicon-paperclip"></i> <?php _e("Attachments", mmg()->domain) ?>
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
                    </div>
                    
                    <!-- URL-aware click-based tab switching -->
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            $('.mm-tab-link').on('click', function(e) {
                                e.preventDefault();
                                var target = $(this).data('target') || ('#' + $(this).data('tab'));
                                var url = $(this).attr('href');
                                
                                // Update URL query (?tab=...)
                                if (window.history && window.history.replaceState) {
                                    window.history.replaceState({}, '', url);
                                }
                                
                                // Toggle active class on tabs
                                $('.nav-tabs li').removeClass('active');
                                $(this).closest('li').addClass('active');
                                
                                // Reset inline styles to avoid lingering display:none
                                $('.tab-pane').removeAttr('style');
                                
                                // Switch panes by class only (Bootstrap CSS handles display)
                                $('.tab-pane').removeClass('active');
                                $(target).addClass('active');
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>