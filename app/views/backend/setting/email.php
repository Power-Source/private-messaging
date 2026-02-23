<div class="page-header">
    <h3><?php _e("Email Einstellungen", mmg()->domain) ?></h3>
</div>

    <?php
    $data = stripslashes_deep($model->export());
    $model->import($data);
    ?>
    <form method="post" class="form-horizontal">

    <div class="form-group <?php echo $model->has_error("noti_subject") ? "has-error" : null ?>">
        <label for="mm_setting_model-noti_subject" class="col-lg-2 control-label">Betreff</label>
        <div class="col-lg-10">
            <textarea 
                name="MM_Setting_Model[noti_subject]" 
                id="mm_setting_model-noti_subject" 
                class="form-control" 
                style="height:50px"
            ><?php echo esc_textarea($model->noti_subject ?? ''); ?></textarea>
            <span
                class="help-block m-b-none error-noti_subject"><?php echo esc_html($model->get_error("noti_subject")); ?></span>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="form-group <?php echo $model->has_error("noti_content") ? "has-error" : null ?>">
        <label for="mm_setting_model-noti_content" class="col-lg-2 control-label">Inhalt</label>
        <div class="col-lg-10">
            <textarea 
                name="MM_Setting_Model[noti_content]" 
                id="mm_setting_model-noti_content" 
                class="form-control" 
                style="height:50px"
            ><?php echo esc_textarea($model->noti_content ?? ''); ?></textarea>
            <span
                class="help-block m-b-none error-noti_content"><?php echo esc_html($model->get_error("noti_content")); ?></span>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="form-group <?php echo $model->has_error("receipt_subject") ? "has-error" : null ?>">
        <label for="mm_setting_model-receipt_subject" class="col-lg-2 control-label">Empfangsbetreff</label>
        <div class="col-lg-10">
            <textarea 
                name="MM_Setting_Model[receipt_subject]" 
                id="mm_setting_model-receipt_subject" 
                class="form-control" 
                style="height:50px"
            ><?php echo esc_textarea($model->receipt_subject ?? ''); ?></textarea>
            <span
                class="help-block m-b-none error-receipt_subject"><?php echo esc_html($model->get_error("receipt_subject")); ?></span>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="form-group <?php echo $model->has_error("receipt_content") ? "has-error" : null ?>">
        <label for="mm_setting_model-receipt_content" class="col-lg-2 control-label">Empfangsinhalt</label>
        <div class="col-lg-10">
            <textarea 
                name="MM_Setting_Model[receipt_content]" 
                id="mm_setting_model-receipt_content" 
                class="form-control" 
                style="height:50px"
            ><?php echo esc_textarea($model->receipt_content ?? ''); ?></textarea>
            <span
                class="help-block m-b-none error-receipt_content"><?php echo esc_html($model->get_error("receipt_content")); ?></span>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="form-group <?php echo $model->has_error("per_page") ? "has-error" : null ?>">
        <label for="mm_setting_model-per_page" class="col-lg-2 control-label">Pro Seite</label>
        <div class="col-lg-10">
            <input type="text" 
                   name="MM_Setting_Model[per_page]" 
                   id="mm_setting_model-per_page" 
                   class="form-control"
                   value="<?php echo esc_attr($model->per_page); ?>">
            <span
                class="help-block m-b-none error-per_page"><?php echo esc_html($model->get_error("per_page")); ?></span>
        </div>
        <div class="clearfix"></div>
    </div>
    <?php wp_nonce_field('mm_settings','_mmnonce') ?>
    <div class="row">
        <div class="col-md-10 col-md-offset-2">
            <button type="submit" class="btn btn-primary"><?php _e("Änderungen speichern", mmg()->domain) ?></button>
        </div>
    </div>
    </form>