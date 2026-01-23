<?php

/**
 * AJAX Handler
 * Initializes AJAX-related controllers
 * 
 * @package PrivateMessaging
 * @since 1.0.0
 */
class MAjax
{
    public function __construct()
    {
        new Notify_Controller();
    }
}