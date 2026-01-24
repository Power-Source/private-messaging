<div id="mm-inbox-view">
<?php if (isset($compose_html)) { echo $compose_html; } ?>
<?php
$user_setting = get_user_meta(get_current_user_id(), '_messages_setting', true);

if (!$user_setting) {
    $user_setting = array(
        'enable_receipt' => '1',
        'prevent_receipt' => '0'
    );
}
$setting = new MM_Setting_Model();
$setting->load();
if ($setting->user_receipt == false) {
    ?>
    <br/>

    <div class="well well-sm row">
        <?php _e("Diese Funktion wurde vom Administrator deaktiviert.", mmg()->domain); ?>
    </div>
    <?php
    return;
} else {
    ?>
    <br/>
    <?php if ($this->has_flash('user_setting_' . get_current_user_id())): ?>
        <div class="alert alert-success"><?php echo esc_html($this->get_flash('user_setting_' . get_current_user_id())) ?></div>
    <?php endif; ?>
    <form id="message_setting" method="post" class="form-horizontal" role="form">
        <fieldset>
            <legend><?php _e("E-Mail Einstellungen", mmg()->domain) ?></legend>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="receipt" value="0">
                            <input <?php echo checked('1', $user_setting['enable_receipt']) ?>
                                type="checkbox" name="receipt" value="1"
                                class="enable_receipt"> <?php _e("Sende mir eine E-Mail, wenn meine gesendeten Nachrichten gelesen werden", mmg()->domain) ?>
                            <span
                                class="help-block"><?php _e("Eine E-Mail wird an Dich gesendet, wenn ein Benutzer Deine Nachricht liest. Diese Funktion funktioniert nicht, wenn du das Tracking in deinem Konto deaktiviert hast.", mmg()->domain) ?></span>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="prevent" value="0">
                            <input <?php echo checked('1', $user_setting['prevent_receipt']) ?>
                                type="checkbox" name="prevent" value="1"
                                class="prevent_receipt"> <?php _e("Verhindere, dass andere meine Nachricht verfolgen", mmg()->domain) ?>
                            <span
                                class="help-block"><?php _e("Wenn du eine Nachricht öffnest, wird keine E-Mail an den Absender gesendet, um ihn darüber zu informieren, dass du die Nachricht gelesen hast.", mmg()->domain) ?></span>
                        </label>
                    </div>
                </div>
            </div>
            <?php do_action('mm_after_user_setting_form') ?>
            <div class="row">
                <?php echo wp_nonce_field('mm_user_setting_' . get_current_user_id()) ?>
                <div class="col-md-10 col-md-offset-2">
                    <button name="mm_user_setting" value="1" class="btn btn-primary"
                            type="submit"><?php _e("Änderungen speichern", mmg()->domain) ?></button>
                </div>
            </div>
        </fieldset>
    </form>
    <script type="text/javascript">
        jQuery(function ($) {
            // Compose opens inline now; ensure any legacy overlay is hidden
            $('#lean_overlay').hide();
        })
    </script>
<?php } ?>
</div>