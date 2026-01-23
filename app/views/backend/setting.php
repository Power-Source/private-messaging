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
                            <a href="#general" role="tab" data-toggle="tab" class="mm-tab-link" data-tab="general">
                                <i class="glyphicon glyphicon-wrench"></i> <?php _e("General Settings", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'email' ? 'active' : null ?>">
                            <a href="#email" role="tab" data-toggle="tab" class="mm-tab-link" data-tab="email">
                                <i class="glyphicon glyphicon-envelope"></i> <?php _e("Email Settings", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'shortcode' ? 'active' : null ?>">
                            <a href="#shortcode" role="tab" data-toggle="tab" class="mm-tab-link" data-tab="shortcode">
                                <i class="glyphicon glyphicon-cog"></i> <?php _e("Shortcodes", mmg()->domain) ?>
                            </a>
                        </li>
                        <li role="presentation" class="<?php echo mmg()->get('tab') == 'attachment' ? 'active' : null ?>">
                            <a href="#attachment" role="tab" data-toggle="tab" class="mm-tab-link" data-tab="attachment">
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
                    
                    <!-- AJAX Tab Loading Script -->
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            // Smooth tab switching with fade effect
                            $('[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                                var tabPane = $($(this).attr('href'));
                                tabPane.fadeIn(300);
                            });
                            
                            // Optional: Add loading animation
                            $('[data-toggle="tab"]').on('hide.bs.tab', function(e) {
                                var tabPane = $($(this).attr('href'));
                                tabPane.fadeOut(200);
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>