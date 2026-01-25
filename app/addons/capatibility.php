<?php

/**
 * Author: PSOURCE
 * Name: Sendeberechtigungen
 * Description: Beschränke die Sendeberechtigungen auf bestimmte ClassicPress-Rollen.
 */
if (!class_exists('MM_User_Capability')) {
    class MM_User_Capability
    {
        use Template_Loader_Trait;
        
        public function __construct()
        {
            add_action('mm_setting_menu', array(&$this, 'setting_menu'));
            add_action('mm_setting_cap', array(&$this, 'setting_content'));
            add_action('wp_loaded', array(&$this, 'process_request'));
            if (is_user_logged_in()) {
                add_filter('mm_suggest_users_args', array(&$this, 'filter_user_return'));
                add_filter('mm_send_to_this_users', array(&$this, 'filter_user_reply'));
            }
        }

        function filter_user_reply($ids)
        {
            $data = get_option('mm_user_cap');
            $user = new WP_User(get_current_user_id());
            $roles = array();
            //not init, use default
            if (!$data) {
                return $ids;
            }

            foreach ($user->roles as $role) {
                if (isset($data[$role])) {
                    $roles = array_merge($roles, $data[$role]);
                    //we will need to add this roles
                    $roles[] = $role;
                }
            }
            $roles = array_unique($roles);
            foreach ($ids as $id) {
                if ($id != get_current_user_id()) {
                    $send_to = new WP_User($id);
                    //check if this user role in the list can send
                    if (count(array_intersect($send_to->roles, $roles)) == 0) {
                        unset($ids[array_search($id, $ids)]);
                    }
                }
            }
            return $ids;
        }

        function filter_user_return($args)
        {
            $data = get_option('mm_user_cap');
            //not init, use default
            if (!$data) {
                return $args;
            }

            $params = array(
                'relation' => 'OR'

            );
            global $wpdb;
            //getting current user role
            foreach ($data as $key => $val) {
                if ($this->check_user_role($key)) {
                    //this user role has data,
                    foreach ($val as $r) {
                        $params[] = array(
                            'key' => $wpdb->get_blog_prefix() . 'capabilities',
                            'value' => $r,
                            'compare' => 'like'
                        );
                    };
                    //include self role
                    $params[] = array(
                        'key' => $wpdb->get_blog_prefix() . 'capabilities',
                        'value' => $key,
                        'compare' => 'like'
                    );
                }
            }
            $args['meta_query'] = $params;

            return $args;

        }

        function check_user_role($role, $user_id = null)
        {

            if (is_numeric($user_id))
                $user = get_userdata($user_id);
            else
                $user = wp_get_current_user();

            if (empty($user))
                return false;

            return in_array($role, (array)$user->roles);
        }

        function process_request()
        {
            if (isset($_POST['mm_user_cap'])) {
                $data = mmg()->post('mm_role');
                update_option('mm_user_cap', $data);
                $this->set_flash('mm_user_cap', __("Einstellungen gespeichert!", mmg()->domain));
                $this->redirect($_SERVER['REQUEST_URI']);
            }
        }

        function setting_content()
        {
            $roles = get_editable_roles();
            $index = array_keys($roles);
            $data = get_option('mm_user_cap');
            if (!$data) {
                $data = array();
            }
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="page-header">
                        <h3><?php _e("Capability Einstellungen", mmg()->domain) ?></h3>
                        <p class="text-muted"><?php _e("Steuere, welche Benutzerrollen Nachrichten an andere Rollen senden können.", mmg()->domain) ?></p>
                    </div>
                    
                    <?php if ($this->has_flash('mm_user_cap')): ?>
                        <div class="alert alert-success">
                            <?php echo $this->get_flash('mm_user_cap') ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="row">
                            <!-- Vertical tabs on left -->
                            <div class="col-md-3">
                                <ul class="nav nav-pills nav-stacked" role="tablist" style="margin-bottom:20px;">
                                    <?php 
                                    $first = true;
                                    foreach ($roles as $key => $role): 
                                    ?>
                                        <li role="presentation" class="<?php echo $first ? 'active' : ''; ?>">
                                            <a href="#tab_<?php echo esc_attr($key); ?>" role="tab" data-toggle="tab">
                                                <?php echo esc_html($role['name']); ?>
                                            </a>
                                        </li>
                                    <?php 
                                        $first = false;
                                    endforeach; 
                                    ?>
                                </ul>
                            </div>
                            
                            <!-- Tab content on right -->
                            <div class="col-md-9">
                                <div class="tab-content" style="min-height:300px;border:1px solid #ddd;padding:20px;background:#f9f9f9;">
                                    <?php 
                                    $first = true;
                                    foreach ($roles as $key => $role): 
                                    ?>
                                        <div role="tabpanel" class="tab-pane <?php echo $first ? 'active' : ''; ?>" id="tab_<?php echo esc_attr($key); ?>">
                                            <h4 style="margin-top:0;"><?php echo esc_html($role['name']); ?> <?php _e("kann senden an:", mmg()->domain); ?></h4>
                                            
                                            <?php foreach ($roles as $k => $r): ?>
                                                <?php if ($k != $key): ?>
                                                    <div class="checkbox">
                                                        <label style="font-weight:normal;">
                                                            <input name="mm_role[<?php echo esc_attr($key); ?>][]"
                                                                   type="checkbox"
                                                                   <?php 
                                                                   if (!isset($data[$key])) {
                                                                       echo 'checked="checked"';
                                                                   } else {
                                                                       checked(in_array($k, $data[$key]), true);
                                                                   }
                                                                   ?>
                                                                   value="<?php echo esc_attr($k); ?>">
                                                            <strong><?php echo esc_html($r['name']); ?></strong>
                                                        </label>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php 
                                        $first = false;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-top:20px;">
                            <div class="col-md-9 col-md-offset-3">
                                <button name="mm_user_cap" type="submit" class="btn btn-primary">
                                    <?php _e("Änderungen speichern", mmg()->domain) ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php
        }

        function setting_menu()
        {
            ?>
            <li class="<?php echo mmg()->get('tab') == 'cap' ? 'active' : null ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'cap')) ?>">
                    <i class="fa fa-binoculars"></i> <?php _e("Capability Einstellungen", mmg()->domain) ?></a>
            </li>
        <?php
        }
    }
}
new MM_User_Capability();