<?php

/**
 * Author: PSOURCE
 * Name: WYISWYG
 * Description: Fügt dem Nachrichteneditor einen WYSIWYG-Editor (TinyMCE) hinzu.
 */
if (!class_exists('MM_WYSIWYG')) {
    class MM_WYSIWYG
    {
        private static $editor_counter = 0;

        public function __construct()
        {
            add_action('wp_enqueue_scripts', array(&$this, 'scripts'));
            add_action('admin_enqueue_scripts', array(&$this, 'scripts'));
            add_action('wp_footer', array(&$this, 'footer_scripts'));
            add_action('admin_footer', array(&$this, 'footer_scripts'));
            add_filter('wp_headers', array(&$this, 'allow_tinymce_unload'));
            add_action('admin_init', array(&$this, 'send_admin_tinymce_policy'), 20);
        }

        public function allow_tinymce_unload($headers)
        {
            if (!$this->request_uses_editor()) {
                return $headers;
            }

            $header_name = 'Permissions-Policy';
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, $header_name) === 0) {
                    $header_name = $name;
                    break;
                }
            }

            $policy = isset($headers[$header_name]) ? $headers[$header_name] : '';
            $headers[$header_name] = $this->merge_unload_policy($policy);

            return $headers;
        }

        public function send_admin_tinymce_policy()
        {
            if (!$this->request_uses_editor() || headers_sent()) {
                return;
            }

            $policy = '';
            foreach (headers_list() as $header) {
                if (stripos($header, 'Permissions-Policy:') === 0) {
                    $policy = trim(substr($header, strlen('Permissions-Policy:')));
                    break;
                }
            }

            header('Permissions-Policy: ' . $this->merge_unload_policy($policy));
        }

        private function merge_unload_policy($policy)
        {
            $directives = array_filter(array_map('trim', explode(',', $policy)));
            $directives = array_filter($directives, function ($directive) {
                return !preg_match('/^unload\s*=/i', $directive);
            });
            $directives[] = 'unload=(self)';

            return implode(', ', $directives);
        }

        private function request_uses_editor()
        {
            if (defined('DOING_AJAX') && DOING_AJAX) {
                return false;
            }

            if (is_admin()) {
                $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
                return strpos($page, 'mm_') === 0;
            }

            return true;
        }

        /**
         * Get TinyMCE settings based on device type
         */
        private function get_editor_settings($is_mobile = false)
        {
            if ($is_mobile) {
                // Minimierte Toolbar für Mobile
                return array(
                    'tinymce' => array(
                        'toolbar1' => 'bold,italic,underline,strikethrough,alignleft,aligncenter,alignright,alignjustify',
                        'toolbar2' => '',
                        'toolbar3' => '',
                        'toolbar4' => '',
                        'height' => 200,
                        'resize' => true,
                        'menubar' => false,
                        'iframe_attrs' => array(
                            'allow' => 'unload',
                        ),
                    ),
                    'quicktags' => true,
                    'media_buttons' => false,
                );
            } else {
                // Volle Toolbar für Desktop
                return array(
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,forecolor,removeformat',
                        'toolbar2' => 'alignleft,aligncenter,alignright,alignjustify,bullist,numlist,outdent,indent',
                        'toolbar3' => 'link,unlink,image,blockquote,hr,pastetext,undo,redo',
                        'toolbar4' => '',
                        'height' => 250,
                        'resize' => true,
                        'menubar' => false,
                        'iframe_attrs' => array(
                            'allow' => 'unload',
                        ),
                    ),
                    'quicktags' => true,
                    'media_buttons' => true,
                );
            }
        }

        function footer_scripts()
        {
            if (!class_exists('Mobile_Detect')) {
                include_once dirname(__FILE__) . '/wysiwyg/Mobile_Detect.php';
            }
            $detect = new Mobile_Detect();
            $is_mobile = $detect->isMobile();
            $settings = $this->get_editor_settings($is_mobile);
            $settings_json = json_encode($settings);
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var editorSettings = <?php echo $settings_json; ?>;

                    if (!$('.mm_wsysiwyg').length) {
                        return;
                    }

                    window.mmWysiwyg = window.mmWysiwyg || {
                        initializedEditors: {}
                    };

                    function load_editor() {
                        var $editors = $('.mm_wsysiwyg').filter(function () {
                            return this.getAttribute('data-mm-wysiwyg-lazy') !== 'true' ||
                                this.getAttribute('data-mm-wysiwyg-active') === 'true';
                        });

                        if (!$editors.length) {
                            return;
                        }

                        if (typeof wp === 'undefined' || typeof wp.editor === 'undefined') {
                            console.warn('ClassicPress Editor API nicht verfügbar');
                            return;
                        }

                        $editors.each(function () {
                            var $textarea = $(this);
                            var editorId = $textarea.attr('id');

                            // TinyMCE benötigt pro Textarea eine eindeutige DOM-ID.
                            var duplicateId = editorId && $('[id]').filter(function () {
                                return this.id === editorId;
                            }).length > 1;

                            if (!editorId || duplicateId) {
                                editorId = 'mm_editor_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                                $textarea.attr('id', editorId);
                            }

                            if (window.mmWysiwyg.initializedEditors[editorId]) {
                                return;
                            }

                            if (typeof tinymce !== 'undefined') {
                                var existingEditor = tinymce.get(editorId);
                                if (existingEditor) {
                                    window.mmWysiwyg.initializedEditors[editorId] = true;
                                    return;
                                }
                            }

                            try {
                                wp.editor.initialize(editorId, editorSettings);
                                window.mmWysiwyg.initializedEditors[editorId] = true;
                            } catch (e) {
                                console.error('Fehler beim Initialisieren von TinyMCE:', e);
                            }
                        });
                    }

                    // Initial laden
                    load_editor();

                    // Bei dynamischem Inhalt neu laden
                    $('body').off('abc.mmWysiwyg').on('abc.mmWysiwyg', function () {
                        setTimeout(load_editor, 100);
                    });
                    $('body').off('mm:wysiwyg:load.mmWysiwyg').on('mm:wysiwyg:load.mmWysiwyg', load_editor);
                });
            </script>
            <?php
        }

        function scripts()
        {
            // ClassicPress TinyMCE Editor laden
            wp_enqueue_editor();
            if (is_multisite() && !function_exists('multisite_over_quota_message')) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }
            wp_enqueue_media();
        }
    }
}
new MM_WYSIWYG();