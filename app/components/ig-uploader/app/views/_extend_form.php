<?php
$upload_new_id = uniqid();
$f_id = uniqid();
?>
<style>
#<?php echo $c_id ?> .panel { border-radius: 10px; border-color: #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.04); }
#<?php echo $c_id ?> .panel-heading { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
#<?php echo $c_id ?> .panel-heading strong, #<?php echo $c_id ?> .panel-heading small { font-weight: 600; color: #111827; }
#<?php echo $c_id ?> .panel-heading .btn { border-radius: 6px; }
#<?php echo $c_id ?> .file-view-port { padding: 10px 12px; background: #f9fafb; min-height: 60px; }
#<?php echo $c_id ?> .file-view-port .no-file { margin: 0; color: #6b7280; }
#<?php echo $c_id ?> .igu-inline-form { display: none; margin: 12px auto; padding: 14px; max-width: 560px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 28px rgba(0,0,0,0.08); }
#<?php echo $c_id ?> .igu-inline-form form { max-width: 520px; margin: 0 auto; display: grid; grid-template-columns: 1fr; gap: 12px; }
#<?php echo $c_id ?> .igu-inline-form .form-group { margin: 0; }
#<?php echo $c_id ?> .igu-inline-form input, #<?php echo $c_id ?> .igu-inline-form textarea { width: 100%; border-radius: 8px; border-color: #d1d5db; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02); }
#<?php echo $c_id ?> .igu-inline-form textarea { min-height: 90px; }
#<?php echo $c_id ?> .igu-inline-form .igu-actions { display: flex; justify-content: flex-end; gap: 10px; padding-top: 4px; }
#<?php echo $c_id ?> .igu-inline-form .btn { border-radius: 6px; padding-left: 14px; padding-right: 14px; }
#<?php echo $c_id ?> .igu-inline-form--loading { opacity: 0.8; }
#<?php echo $c_id ?> .igu-inline-loading, #<?php echo $c_id ?> .igu-inline-error { color: #374151; font-size: 13px; padding: 4px 0; }
@media (max-width: 640px) {
    #<?php echo $c_id ?> .panel { box-shadow: none; border-radius: 8px; }
    #<?php echo $c_id ?> .panel-heading { padding: 10px; }
    #<?php echo $c_id ?> .igu-inline-form { margin: 8px auto; padding: 12px; max-width: 100%; }
    #<?php echo $c_id ?> .igu-inline-form form { max-width: 100%; }
}
</style>
<div id="<?php echo $c_id ?>">
    <div class="panel panel-default" id="<?php echo $c_id ?>-panel" data-cid="<?php echo $c_id ?>" style="margin-bottom: 5px;border-width: 1px;position:relative;">
        <div class="panel-heading">
            <strong
                class="hidden-xs hidden-sm"><?php echo $attributes['title'] ?></strong>
            <small
                class="hidden-md hidden-lg"><?php echo $attributes['title'] ?></small>
            <button type="button"
                    class="btn btn-primary btn-xs pull-right add-file"><?php _e('Add', ig_uploader()->domain) ?> <i
                    class="glyphicon glyphicon-plus"></i>
            </button>
        </div>
        <section class="panel-body file-view-port">
            <?php if (is_array($models) && count($models)): ?>
                <?php foreach ($models as $model): ?>
                    <?php $this->render_partial(apply_filters('igu_single_file_template', '_single_file'), array(
                        'model' => $model
                    )) ?>
                <?php endforeach; ?>
                <div class="clearfix"></div>
            <?php else: ?>
                <p class="no-file"><?php _e("No sample file.", ig_uploader()->domain) ?></p>
            <?php endif; ?>
            <div class="clearfix"></div>
        </section>
        <div class="igu-inline-form" data-cid="<?php echo esc_attr($c_id) ?>" style="display:none;max-width:560px;margin:12px auto;padding:14px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 10px 28px rgba(0,0,0,.08)"></div>
    </div>
</div>