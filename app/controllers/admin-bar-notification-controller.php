<?php

/**
 * Admin Bar Notification Controller
 * Adds messaging notifications and compose button to ClassicPress admin bar
 * 
 * @package PrivateMessaging
 * @since 1.0.0
 */
class Admin_Bar_Notification_Controller
{
    use Template_Loader_Trait;
    
    public function __construct()
    {
        if (is_user_logged_in()) {
            $this->template_base_path = dirname(__DIR__) . '/views/';
            add_action('admin_bar_menu', array(&$this, 'notification_buttons'), 80);
            add_action('wp_footer', array(&$this, 'compose_form_footer'));
            add_action('admin_footer', array(&$this, 'compose_form_footer'));
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
        }
    }

    function enqueue_scripts()
    {
        // Enqueue Tom-Select for recipient field
        wp_enqueue_script('tom-select');
        wp_enqueue_style('tom-select');
        wp_enqueue_script('tom-select-compat');
        // Enqueue main styles for modal
        wp_enqueue_style('mm_style');
    }

    function compose_form_footer()
    {
        $model = new MM_Message_Model();
        $this->load_template_part('bar/_compose_form', array(
            'model' => $model
        ));
    }

    function notification_buttons($wp_admin_bar)
    {
        //create new menu
        $unread = MM_Conversation_Model::count_unread();
        $args = array(
            'id' => 'mm-button',
            'title' => '<span class="ab-icon dashicons dashicons-email-alt"></span><span class="ab-label">' . $unread . '</span>',
            'href' => '#',
            'meta' => array(
                'class' => 'mm-admin-bar-menu'
            )
        );
        $wp_admin_bar->add_menu($args);

        //create group
        $args = array(
            'id'     => 'mm-buttons-group',
            'parent' => 'mm-button',
        );
        $wp_admin_bar->add_group( $args );
        //add node send new message
        $args = array(
            'id' => 'mm-compose-button',
            'title' => __("Sende neue Nachricht", mmg()->domain),
            'href' => '#compose-form-container-admin-bar',
            'parent' => 'mm-buttons-group',
            'meta' => array(
                'class' => 'mm-compose-admin-bar',
            )
        );
        $wp_admin_bar->add_node($args);
        //add node inbox page
        $args = array(
            'id' => 'mm-inbox-button',
            'title' => __("Posteingang anzeigen", mmg()->domain),
            'href' => get_permalink(mmg()->setting()->inbox_page),
            'parent' => 'mm-buttons-group',
            'meta' => array(
                'class' => 'mm-view-inbox-admin-bar',
            )
        );
        $wp_admin_bar->add_node($args);
    }
}