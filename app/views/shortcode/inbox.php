<?php if ($this->has_flash("mm_sent_" . get_current_user_id())): ?>
    <div class="row">
        <br/>

        <div class="col-md-12 no-padding">
            <div class="alert alert-success">
                <?php echo $this->get_flash("mm_sent_" . get_current_user_id()) ?>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($compose_html)) { echo $compose_html; } ?>

<?php if (isset($models) && count($models) > 0): ?>
<div id="mm-inbox-view">
<?php echo $this->load_template_part('shortcode/inbox_inner', array(
    'models' => $models,
    'total_pages' => $total_pages,
    'paged' => $paged,
    'compose_html' => $compose_html
), false); ?>
</div>
<?php else: ?>
    <div class="well well-sm no-margin"><?php _e("No message found!", mmg()->domain) ?></div>
<?php endif; ?>
