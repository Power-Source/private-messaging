<?php
if (!function_exists('mm_register_privacy_content')) {
    function mm_register_privacy_content()
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = 'Dieses Plugin speichert private Nachrichten zwischen angemeldeten Nutzern '
            . 'einschliesslich Zeitstempeln, Absender- und Empfaenger-IDs sowie optionaler '
            . 'Lesebestaetigungen. Optional angehaengte Dateien werden im Upload-Verzeichnis '
            . 'des Servers gespeichert. Bei aktivierten E-Mail-Benachrichtigungen werden '
            . 'Nachrichtentitel und Empfaenger per E-Mail versendet. Es findet kein '
            . 'Datentransfer an Drittanbieter statt.';

        wp_add_privacy_policy_content('PS PM-System', wp_kses_post(wpautop($content)));
    }

    // Register early on admin_init to ensure the guide picks it up
    add_action('admin_init', 'mm_register_privacy_content', 5);
    // Fallback: ensure it runs when the privacy page head loads
    add_action('admin_head-options-privacy.php', 'mm_register_privacy_content');
}
