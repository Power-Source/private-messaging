<?php

/**
 * Author: PSOURCE
 * Name: Group Conversation (Beta)
 * Description: Enable group conversations and invitations to join a group conversation.
 */
class MM_Group_Conversation
{
    public function __construct()
    {
        add_action('mm_before_reply_form', array(&$this, 'include_textbox'));
        add_action('wp_ajax_mm_suggest_include_users', array(&$this, 'mm_suggest_include_users'));
        add_action('mm_before_subject_field', array(&$this, 'append_cc_textbox'), 10, 3);
        add_action('wp_footer', array(&$this, 'drop_user_out'));
        add_action('message_content_meta', array(&$this, 'show_user_list'));
        add_action('wp_ajax_mm_drop_user', array(&$this, 'drop_user'));
        add_action('wp_ajax_mm_add_cc_user', array(&$this, 'add_cc_user'));
    }

    function drop_user()
    {
        if (!wp_verify_nonce(mmg()->post('_nonce'), 'drop_user')) {
            wp_send_json(array('status' => 'fail', 'message' => __('Sicherheitsüberprüfung fehlgeschlagen', mmg()->domain)));
            return;
        }

        $user_name = mmg()->post('user');
        $user = get_user_by('login', $user_name);
        if (!$user) {
            wp_send_json(array('status' => 'fail', 'message' => __('Benutzer nicht gefunden', mmg()->domain)));
            return;
        }
        
        if ($user->ID == get_current_user_id()) {
            wp_send_json(array(
                'status' => 'fail',
                'message' => __("Du kannst dich nicht selbst entfernen!", mmg()->domain)
            ));
        } else {
            $model = MM_Conversation_Model::model()->find(mmg()->post('conversation_id'));
            if (is_object($model)) {
                $index = array_unique(array_filter(explode(',', $model->user_index)));
                $key = array_search($user->ID, $index);
                if ($key !== false) {
                    unset($index[$key]);
                }

                $model->user_index = implode(',', $index);
                $model->save();
                //update status
                MM_Message_Status_Model::model()->status($model->id, -3, $user->ID);
                wp_send_json(array(
                    'status' => 'success',
                    'message' => sprintf(__("%s wurde aus der Konversation entfernt.", mmg()->domain), $user->user_login)
                ));
            } else {
                wp_send_json(array('status' => 'fail', 'message' => __('Konversation nicht gefunden', mmg()->domain)));
            }
        }
    }

    function add_cc_user()
    {
        if (!wp_verify_nonce(mmg()->post('_wpnonce'), 'mm_add_cc_user')) {
            wp_send_json(array('status' => 'fail', 'message' => __('Sicherheitsüberprüfung fehlgeschlagen', mmg()->domain)));
            return;
        }

        $user_id = absint(mmg()->post('user_id'));
        $conversation_id = absint(mmg()->post('conversation_id'));
        
        if (!$user_id || !$conversation_id) {
            wp_send_json(array('status' => 'fail', 'message' => __('Ungültige Parameter', mmg()->domain)));
            return;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json(array('status' => 'fail', 'message' => __('Benutzer nicht gefunden', mmg()->domain)));
            return;
        }
        
        $conversation = MM_Conversation_Model::model()->find($conversation_id);
        if (!is_object($conversation) || !$conversation->exist) {
            wp_send_json(array('status' => 'fail', 'message' => __('Konversation nicht gefunden', mmg()->domain)));
            return;
        }
        
        // Only conversation owner can add participants
        if ($conversation->send_from != get_current_user_id()) {
            wp_send_json(array('status' => 'fail', 'message' => __('Keine Berechtigung', mmg()->domain)));
            return;
        }
        
        // Add user to conversation index
        $index = array_unique(array_filter(explode(',', $conversation->user_index)));
        if (!in_array($user_id, $index)) {
            $index[] = $user_id;
            $conversation->user_index = implode(',', $index);
            $conversation->save();
            
            // Set status for new participant
            MM_Message_Status_Model::model()->status($conversation->id, MM_Message_Status_Model::STATUS_UNREAD, $user_id);
            
            wp_send_json(array(
                'status' => 'success',
                'message' => sprintf(__('%s wurde zur Konversation hinzugefügt.', mmg()->domain), $user->user_login)
            ));
        } else {
            wp_send_json(array('status' => 'fail', 'message' => __('Benutzer ist bereits Teilnehmer', mmg()->domain)));
        }
    }

    function show_user_list($message)
    {
        $conversation = MM_Conversation_Model::model()->find($message->conversation_id);
        if (!is_object($conversation)) {
            return;
        }

        $current_user_id = get_current_user_id();
        
        // Deduplicate and exclude current user
        $user_ids = array_filter(array_unique(explode(',', $conversation->user_index)), function($id) use ($current_user_id) {
            return intval($id) !== $current_user_id;
        });
        
        if (empty($user_ids)) {
            return;
        }

        $users = get_users(array(
            'include' => array_values($user_ids)
        ));

        $is_conversation_owner = ($conversation->send_from == $current_user_id);

        foreach ($users as $user) {
            $name = $user->first_name . ' ' . $user->last_name;
            if (strlen(trim($name)) == 0) {
                $name = $user->user_login;
            }
            ?>
            <span class="label label-default" style="display:inline-flex;align-items:center;gap:4px;">
                <?php echo esc_html($name) ?>
                <?php if ($is_conversation_owner): ?>
                    <a data-id="<?php echo $conversation->id ?>" data-user="<?php echo esc_attr($user->user_login) ?>"
                       class="mm-drop-user" href="#" style="margin-left:4px;color:inherit;opacity:0.7;text-decoration:none;"
                       title="<?php esc_attr_e('Teilnehmer entfernen', mmg()->domain) ?>"><span
                            aria-hidden="true">&times;</span></a>
                <?php endif; ?>
            </span>&nbsp;
        <?php
        }
        
        // Show "Add CC" button if conversation owner
        if ($is_conversation_owner && !$conversation->is_lock()) {
            ?>
            <button type="button" class="btn btn-default btn-xs mm-add-cc-participant" 
                    data-conversation-id="<?php echo esc_attr($conversation->id) ?>"
                    style="border-radius:4px;font-size:11px;">
                <i class="fa fa-plus"></i> <?php _e('CC hinzufügen', mmg()->domain) ?>
            </button>
        <?php
        }
    }

    function append_cc_textbox($model, $form = null, $scenario = null)
    {
        // Inline scenario maps to compose_form
        if ($scenario === 'inline') {
            $scenario = 'compose_form';
        }
        
        match ($scenario) {
            'compose_form' => $this->_compose_form_cc(),
            'admin-bar' => $this->_admin_bar_form_cc(),
            default => null,
        };
    }

    function _admin_bar_form_cc()
    {
        ?>
        <div class="clearfix"></div>
        <div class="form-group">
            <label class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Cc (weitere Empfänger)", mmg()->domain) ?></label>

            <div class="col-md-10 col-sm-12 col-xs-12">
                <input type="text" name="cc" id="mmg-cc-bar-input" class="form-control cc-input"
                       placeholder="<?php esc_attr_e("Weitere Empfänger hinzufügen", mmg()->domain) ?>">
            </div>
            <div class="clearfix"></div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                window.mm_cc_bar_input = $('#mmg-cc-bar-input').selectize({
                    valueField: 'id',
                    labelField: 'name',
                    searchField: 'name',
                    options: [],
                    create: false,
                    load: function (query, callback) {
                        if (!query.length) return callback();
                        var instance = window.mm_cc_bar_input[0].selectize;
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php?action=mm_suggest_users&_wpnonce='.wp_create_nonce('mm_suggest_users')) ?>',
                            data: {
                                'query': query
                            },
                            beforeSend: function () {
                                instance.$control.append('<i style="position: absolute;right: 10px;" class="fa fa-circle-o-notch fa-spin"></i>');
                            },
                            success: function (data) {
                                instance.$control.find('i').remove();
                                callback(data);
                            }
                        });
                    }
                });
            })
        </script>
    <?php
    }

    function _compose_form_cc()
    {
        ?>
        <div class="clearfix"></div>
        <div class="form-group">
            <label class="control-label hidden-xs hidden-sm"><?php _e("Cc (weitere Empfänger)", mmg()->domain) ?></label>
            <div>
                <input type="text" name="cc" id="mmg-cc-input" class="form-control cc-input"
                       placeholder="<?php esc_attr_e("Weitere Empfänger hinzufügen", mmg()->domain) ?>">
            </div>
            <div class="clearfix"></div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                window.mm_cc_input = $('#mmg-cc-input').selectize({
                    valueField: 'id',
                    labelField: 'name',
                    searchField: 'name',
                    options: [],
                    create: false,
                    load: function (query, callback) {
                        if (!query.length) return callback();
                        var instance = window.mm_cc_input[0].selectize;
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php?action=mm_suggest_users&_wpnonce='.wp_create_nonce('mm_suggest_users')) ?>',
                            data: {
                                'query': query
                            },
                            beforeSend: function () {
                                instance.$control.append('<i style="position: absolute;right: 10px;" class="fa fa-circle-o-notch fa-spin"></i>');
                            },
                            success: function (data) {
                                instance.$control.find('i').remove();
                                callback(data);
                            }
                        });
                    }
                });
            })
        </script>
    <?php
    }

    function mm_suggest_include_users()
    {
        if (!wp_verify_nonce(mmg()->get('_wpnonce'), 'mm_suggest_include_users')) {
            return;
        }
        $model = MM_Conversation_Model::model()->find(mmg()->post('parent_id'));
        if (!is_object($model)) {
            return;
        }
        $excludes = explode(',', $model->user_index);
        $query_string = mmg()->post('query');
        if (!empty($query_string)) {
            $query = new WP_User_Query(array(
                'search' => '*' . mmg()->post('query') . '*',
                'search_columns' => array('user_login'),
                'exclude' => $excludes,
                'number' => 10,
                'orderby' => 'user_login',
                'order' => 'ASC'
            ));
            $name_query = new WP_User_Query(array(
                'exclude' => $excludes,
                'number' => 10,
                'orderby' => 'user_login',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'first_name',
                        'value' => $query_string,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'last_name',
                        'value' => $query_string,
                        'compare' => 'LIKE'
                    )
                )
            ));
            $results = array_merge($query->get_results(), $name_query->get_results());

            $data = array();
            foreach ($results as $user) {
                $userdata = get_userdata($user->ID);
                $name = $user->user_login;
                $full_name = trim($userdata->first_name . ' ' . $userdata->last_name);
                if (strlen($full_name)) {
                    $name = $user->user_login . ' - ' . $full_name;
                }
                $obj = new stdClass();
                $obj->id = $user->ID;
                $obj->name = $name;
                $data[] = $obj;
            }
            wp_send_json($data);
        }

        die;
    }

    function include_textbox($model)
    {
        ?>
        <div class="form-group">
            <label class="col-md-12 hidden-xs hidden-sm">
                <?php _e("Weitere Empfänger:", mmg()->domain) ?>
            </label>

            <div class="col-md-12 col-xs-12 col-sm-12">
                <input type="text" name="user_include" id="user_include" class="form-control">
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                window.mm_reply_select = $('#user_include').selectize({
                    valueField: 'id',
                    labelField: 'name',
                    searchField: 'name',
                    options: [],
                    create: false,
                    load: function (query, callback) {
                        if (!query.length) return callback();

                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php?action=mm_suggest_include_users&_wpnonce='.wp_create_nonce('mm_suggest_include_users')) ?>',
                            data: {
                                'query': query,
                                'parent_id': '<?php echo $model->conversation_id ?>'
                            },
                            beforeSend: function () {
                                $('.selectize-input').append('<i style="position: absolute;right: 10px;" class="fa fa-circle-o-notch fa-spin"></i>');
                            },
                            success: function (data) {
                                $('.selectize-input').find('i').remove();
                                callback(data);
                            }
                        });
                    }
                });
            })
        </script>
    <?php
    }

    function drop_user_out()
    {
        $confirm_drop = esc_js(__("Möchtest du diesen Teilnehmer wirklich entfernen?", mmg()->domain));
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                // Drop user from conversation
                $('body').on('click', '.mm-drop-user', function (e) {
                    e.preventDefault();
                    var that = $(this);
                    if (confirm('<?php echo $confirm_drop ?>')) {
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php') ?>',
                            data: {
                                _nonce: '<?php echo wp_create_nonce('drop_user') ?>',
                                user: $(this).data('user'),
                                action: 'mm_drop_user',
                                conversation_id: $(this).data('id')
                            },
                            success: function (data) {
                                if (data.status == 'fail') {
                                    alert(data.message);
                                } else if (data.status == 'success') {
                                    that.closest('.label').fadeOut(function() { $(this).remove(); });
                                    if (typeof data.message !== 'undefined' && data.message) {
                                        // Optional: show success message
                                    }
                                }
                            }
                        });
                    }
                });

                // Add CC participant button
                $('body').on('click', '.mm-add-cc-participant', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var conversationId = btn.data('conversation-id');
                    
                    // Check if picker already exists
                    if ($('#mm-cc-picker-' + conversationId).length) {
                        return;
                    }
                    
                    // Insert selectize input
                    var pickerHtml = '<span id="mm-cc-picker-' + conversationId + '" style="display:inline-block;margin-left:8px;">' +
                        '<input type="text" id="mm-cc-input-' + conversationId + '" style="width:200px;" placeholder="<?php esc_attr_e('Name eingeben...', mmg()->domain) ?>">' +
                        '</span>';
                    btn.before(pickerHtml);
                    btn.hide();
                    
                    // Initialize selectize
                    var $input = $('#mm-cc-input-' + conversationId).selectize({
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        options: [],
                        create: false,
                        placeholder: '<?php esc_attr_e('Benutzer suchen...', mmg()->domain) ?>',
                        load: function (query, callback) {
                            if (!query.length) return callback();
                            $.ajax({
                                type: 'POST',
                                url: '<?php echo admin_url('admin-ajax.php?action=mm_suggest_include_users&_wpnonce='.wp_create_nonce('mm_suggest_include_users')) ?>',
                                data: {
                                    query: query,
                                    parent_id: conversationId
                                },
                                success: function (data) {
                                    callback(data);
                                },
                                error: function() {
                                    callback();
                                }
                            });
                        },
                        onChange: function(value) {
                            if (!value) return;
                            
                            // Add user to conversation via AJAX
                            $.ajax({
                                type: 'POST',
                                url: '<?php echo admin_url('admin-ajax.php') ?>',
                                data: {
                                    action: 'mm_add_cc_user',
                                    _wpnonce: '<?php echo wp_create_nonce('mm_add_cc_user') ?>',
                                    user_id: value,
                                    conversation_id: conversationId
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        // Reload page to show updated participant list
                                        location.reload();
                                    } else {
                                        alert(response.message || '<?php echo esc_js(__('Fehler beim Hinzufügen', mmg()->domain)); ?>');
                                    }
                                }
                            });
                        }
                    });
                });
            })
        </script>
    <?php
    }
}

new MM_Group_Conversation();