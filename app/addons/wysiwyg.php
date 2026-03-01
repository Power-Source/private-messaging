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
                    var initializedEditors = [];

                    function load_editor() {
                        if (typeof wp === 'undefined' || typeof wp.editor === 'undefined') {
                            console.warn('ClassicPress Editor API nicht verfügbar');
                            return;
                        }

                        $('.mm_wsysiwyg').each(function () {
                            var $textarea = $(this);
                            var editorId = $textarea.attr('id');

                            // Wenn textarea keine ID hat, generiere eine
                            if (!editorId) {
                                editorId = 'mm_editor_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                                $textarea.attr('id', editorId);
                            }

                            // Prüfe ob Editor bereits initialisiert wurde
                            if (initializedEditors.indexOf(editorId) !== -1) {
                                return;
                            }

                            // Entferne existierenden Editor falls vorhanden
                            if (typeof tinymce !== 'undefined') {
                                var existingEditor = tinymce.get(editorId);
                                if (existingEditor) {
                                    existingEditor.remove();
                                }
                            }

                            // Initialisiere TinyMCE
                            try {
                                wp.editor.initialize(editorId, editorSettings);
                                initializedEditors.push(editorId);
                            } catch (e) {
                                console.error('Fehler beim Initialisieren von TinyMCE:', e);
                            }
                        });
                    }

                    // Initial laden
                    load_editor();

                    // Bei dynamischem Inhalt neu laden
                    $('body').on('abc', function () {
                        setTimeout(load_editor, 100);
                    });
                });
            </script>
            <?php
        }

        function scripts()
        {
            // ClassicPress TinyMCE Editor laden
            wp_enqueue_editor();
            wp_enqueue_media();
        }
    }
}
new MM_WYSIWYG();