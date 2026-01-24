<?php

/**
 * Message Me Shortcode Controller
 * Handles [pm_user] shortcode for private message buttons
 * 
 * @package PrivateMessaging
 * @since 1.0.0
 */
class Message_Me_Shortcode_Controller
{
    use Template_Loader_Trait;
    
    public $button_id;
    
    public function __construct()
    {
        $this->template_base_path = dirname(__DIR__) . '/views/';
        add_shortcode('pm_user', array(&$this, 'pm_user'));
    }

    function pm_user($atts)
    {
        $this->button_id = uniqid();
        $a = shortcode_atts(array(
            'user_id' => '',
            'user_name' => '',
            'text' => __('Kontaktiere mich', mmg()->domain),
            'class' => 'btn btn-sm btn-primary',
            'subject' => __('Du hast eine neue Nachricht!', mmg()->domain),
            'in_the_loop' => false
        ), $atts);

        if (!empty($a['user_id'])) {
            $user = get_user_by('id', $a['user_id']);
        } elseif (!empty($a['user_name'])) {
            $user = get_user_by('login', $a['user_name']);
        } elseif ($a['in_the_loop'] == true && in_the_loop()) {
            //this is in the loop, we can get author
            $username = get_the_author_meta('user_login');
            if (!empty($username)) {
                $user = get_user_by('login', $username);
            }
        }

        if (!isset($user) || !is_object($user))
            return '';

        if (!is_user_logged_in()) {
            mmg()->load_script('login');
        }
        //add modal in footer,only if user logged in

        add_action('wp_footer', array(&$this, 'message_me_modal'));
        return $this->load_template_part('message_me/buttons', array(
            'a' => $a,
            'user' => $user
        ), false);
    }

    function message_me_modal()
    {
        $this->load_template_part('message_me/modal');
    }
}