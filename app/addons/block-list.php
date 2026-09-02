<?php

/**
 * Author: PSOURCE
 * Name: Blacklist
 * Description: Ermöglicht es Benutzern, Nachrichten von anderen Benutzern zu blockieren.
 */
if (!class_exists('MM_Block_List')) {
    class MM_Block_List
    {
        public function __construct()
        {
            add_action('mm_after_user_setting_form', array(&$this, 'append_form'));
            add_action('mm_user_setting_saved', array(&$this, 'update_database'));
            add_filter('mm_suggest_users_args', array(&$this, 'filter_user_return'));
            add_filter('mm_send_to_this_users', array(&$this, 'filter_user_send'));

            add_action('wp_ajax_mm_all_users', array(&$this, 'all_users'));
        }

        function all_users()
        {
            if (!wp_verify_nonce(mmg()->get('_wpnonce'), 'mm_all_users')) {
                exit;
            }

            $query = new WP_User_Query(array(
                'search' => '*' . mmg()->post('query') . '*',
                'search_columns' => array('user_login'),
                'exclude' => array(get_current_user_id()),
                'number' => 10,
                'orderby' => 'user_login',
                'order' => 'ASC'
            ));

            $data = array();
            foreach ($query->get_results() as $user) {
                $obj = new stdClass();
                $obj->id = $user->ID;
                $obj->name = $user->user_login;
                $data[] = $obj;
            }

            wp_send_json($data);

            exit;
        }

        function filter_user_send($ids)
        {
            //the different is the ids can send
            return array_diff($ids, $this->_filter_user());
            //return $ids;
        }

        function filter_user_return($args)
        {
            //we need to find all the guys block this current user and hide the result
            $ids = $this->_filter_user();
            if (!empty($ids)) {
                $args['exclude'] = array_merge($args, $ids);
            }

            return $args;
        }

        function _filter_user()
        {
            global $wpdb;;
            $current_user = wp_get_current_user();
            $sql = "SELECT * FROM " . $wpdb->prefix . 'usermeta WHERE meta_key=%s AND meta_value LIKE %s';
            $results = $wpdb->get_results($wpdb->prepare($sql, 'mm_block_list', '%' . $current_user->user_login . '%'), ARRAY_A);
            $ids = array();
            foreach ($results as $row) {
                $list = $row['meta_value'];
                $list = array_filter(array_unique(explode(',', $list)));
                if (in_array($current_user->user_login, $list)) {
                    $ids[] = $row['user_id'];
                }
            }

            return $ids;
        }

        function update_database()
        {
            if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_block_list_nonce')) {
                return;
            }
            $block_list = isset($_POST['mm_user_block']) ? sanitize_textarea_field(wp_unslash($_POST['mm_user_block'])) : '';
            $usernames = preg_split('/[\r\n,]+/', $block_list);
            $usernames = array_filter(array_map('sanitize_user', $usernames));
            $block_list = implode(',', array_unique($usernames));
            update_user_meta(get_current_user_id(), 'mm_block_list', $block_list);
        }

        function append_form()
        {
            $block_list = get_user_meta(get_current_user_id(), 'mm_block_list', true);

            if (!$block_list) {
                $block_list = '';
            }
            $block_list = implode("\n", array_filter(array_map('trim', explode(',', $block_list))));
            ?>
            <div class="form-group mm-block-list-setting">
                <div class="col-sm-offset-2 col-sm-10">
                    <label for="mm-block-list-input"><?php _e("Blockierte Benutzer", mmg()->domain) ?></label>
                    <p class="help-block"><?php _e("Ein Benutzername pro Zeile. Blockierte Benutzer können Dir keine Nachrichten senden.", mmg()->domain) ?></p>
                    <textarea id="mm-block-list-input" name="mm_user_block" class="form-control" rows="6"><?php echo esc_textarea($block_list) ?></textarea>
                </div>
                <div class="clearfix"></div>
            </div>
        <?php
        }
    }
}

new MM_Block_List();