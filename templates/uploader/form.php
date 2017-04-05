<?php
/**
 * Stores target entity record logical name and ID.
 *
 * @var $target array
 */

?><form action="<?php echo esc_attr( admin_url( 'admin-ajax.php?action=wpcrm_upload' ) ); ?>" class="dropzone form-control" id="wpcrm-uploader">
    <input type="hidden" name="target_entity" value="<?php echo esc_attr( $target[0] ); ?>">
    <input type="hidden" name="target_record" value="<?php echo esc_attr( $target[1] ); ?>">
    <?php wp_nonce_field( 'wpcrm_upload' ); ?>
</form>
