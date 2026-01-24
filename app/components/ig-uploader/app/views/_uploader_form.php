<div class="ig-container">
    <?php
    //empty modal for fields work only
    if (!isset($model)) {
        $model = new IG_Uploader_Model();
    }
    ?>
    <form method="post" class="igu-upload-form igu-form" style="min-width:60%;max-width:304px">
    <?php if ($model->exists()) { ?>
        <input type="hidden" name="IG_Uploader_Model[id]" id="ig_uploader_model-id" value="<?php echo esc_attr($model->id); ?>">
    <?php } ?>
    <div style="margin-bottom: 0" class="form-group <?php echo $model->has_error("file") ? "has-error" : null ?>">
        <button type="button"
                class="btn btn-default upload_image_button btn-xs"><?php _e("Choose File", ig_uploader()->domain) ?></button>
        <span class="file-upload-name"></span>
        <input type="hidden" name="IG_Uploader_Model[file]" id="attachment" value="<?php echo esc_attr($model->file); ?>">
        <?php if ($model->exists() && $model->file) : ?>
            <span
                class="help-block m-b-none"><?php _e("File attached, upload new file will replace the current file.", ig_uploader()->domain) ?></span>
        <?php endif; ?>
        <span class="help-block m-b-none error-file"><?php echo esc_html($model->get_error("file")); ?></span>

        <div class="clearfix"></div>
    </div>
    <div style="margin-bottom: 0" class="form-group <?php echo $model->has_error("url") ? "has-error" : null ?>">
        <label for="ig_uploader_model-url" class="control-label hidden-xs hidden-sm"><?php _e("Url", ig_uploader()->domain); ?></label>
        <input type="text" 
               name="IG_Uploader_Model[url]" 
               id="ig_uploader_model-url" 
               class="form-control input-sm" 
               placeholder="<?php echo esc_attr__('Url', ig_uploader()->domain); ?>"
               value="<?php echo esc_attr($model->url); ?>">
        <span class="help-block m-b-none error-url"><?php echo esc_html($model->get_error("url")); ?></span>

        <div class="clearfix"></div>
    </div>
    <div style="margin-bottom: 0" class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
        <label for="ig_uploader_model-content" class="control-label hidden-xs hidden-sm"><?php _e("Content", ig_uploader()->domain); ?></label>
        <textarea 
            name="IG_Uploader_Model[content]" 
            id="ig_uploader_model-content" 
            class="form-control input-sm" 
            style="height:80px" 
            placeholder="<?php echo esc_attr__('Content', ig_uploader()->domain); ?>"
        ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
        <span class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>

        <div class="clearfix"></div>
    </div>
    <?php wp_nonce_field('igu_uploading'); ?>
    <div class="igu-actions">
        <button class="btn btn-default btn-sm igu-close-uploader"
                type="button"><?php _e("Cancel", ig_uploader()->domain) ?></button>
        <button class="btn btn-primary btn-sm" type="submit"><?php _e("Submit", ig_uploader()->domain) ?></button>
    </div>
    </form>
</div>