<?php
/**
 * Order actions meta box template.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$statuses = mitzies_jerk_get_order_statuses();
$current_status = $order->get( 'status' );
?>

<div class="mj-order-actions">
    <p>
        <label for="mj_order_status"><strong><?php esc_html_e( 'Update Status', 'mitzies-jerk' ); ?></strong></label>
        <select name="mj_order_status" id="mj_order_status" class="widefat">
            <?php foreach ( $statuses as $status_key => $status_label ) : ?>
                <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $current_status, $status_key ); ?>>
                    <?php echo esc_html( $status_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <div class="mj-action-buttons">
        <button type="button" class="button button-secondary" id="mj-resend-email">
            <?php esc_html_e( 'Resend Email', 'mitzies-jerk' ); ?>
        </button>
        <button type="button" class="button button-link-delete" id="mj-cancel-order">
            <?php esc_html_e( 'Cancel Order', 'mitzies-jerk' ); ?>
        </button>
    </div>
</div>

<style>
.mj-order-actions p { margin-bottom: 15px; }
.mj-order-actions label { display: block; margin-bottom: 5px; }
.mj-action-buttons { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
.mj-action-buttons .button { width: 100%; text-align: center; }
</style>

<script>
jQuery(document).ready(function($) {
    $('#mj-cancel-order').on('click', function() {
        if (confirm('<?php esc_html_e( 'Are you sure you want to cancel this order?', 'mitzies-jerk' ); ?>')) {
            $('#mj_order_status').val('cancelled');
            $('form#post').submit();
        }
    });

    $('#mj-resend-email').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e( 'Sending...', 'mitzies-jerk' ); ?>');

        $.post(ajaxurl, {
            action: 'mj_resend_order_email',
            order_id: <?php echo intval( $post->ID ); ?>,
            nonce: mitziesJerkAdmin.nonce
        }, function(response) {
            if (response.success) {
                $btn.text('<?php esc_html_e( 'Email Sent!', 'mitzies-jerk' ); ?>');
                setTimeout(function() {
                    $btn.prop('disabled', false).text('<?php esc_html_e( 'Resend Email', 'mitzies-jerk' ); ?>');
                }, 2000);
            } else {
                alert(response.data || '<?php esc_html_e( 'Failed to send email.', 'mitzies-jerk' ); ?>');
                $btn.prop('disabled', false).text('<?php esc_html_e( 'Resend Email', 'mitzies-jerk' ); ?>');
            }
        });
    });
});
</script>
