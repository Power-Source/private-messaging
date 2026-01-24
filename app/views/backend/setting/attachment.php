<h4><?php _e("Welche Rollen Anhänge hochladen können", mmg()->domain) ?></h4>
<form method="post" class="form-horizontal">
<input type="hidden" name="MM_Setting_Model[allow_attachment][]" value="">
<table class="table table-condensed table-hover">
    <thead>
    <tr>
        <th><?php _e("Rolle", mmg()->domain) ?></th>
        <th><?php _e("Kann hochladen", mmg()->domain) ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $roles = get_editable_roles();
    foreach ($roles as $key => $role): ?>
        <?php if (isset($role['capabilities']['upload_files']) && $role['capabilities']['upload_files'] == false || !isset($role['capabilities']['upload_files'])): ?>
            <?php $is = in_array($key, $model->allow_attachment); ?>
            <tr>
                <td><?php echo esc_html($role['name']); ?></td>
                <td>
                    <input type="checkbox" 
                           name="MM_Setting_Model[allow_attachment][]" 
                           id="mm_setting_model-allow_attachment-<?php echo esc_attr($key); ?>" 
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked($is); ?>>
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<?php wp_nonce_field('mm_settings', '_mmnonce') ?>
<button type="submit" class="btn btn-primary"><?php _e("Änderungen speichern", mmg()->domain) ?></button>
</form>