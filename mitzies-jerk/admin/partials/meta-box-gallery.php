<?php
/**
 * Gallery meta box template.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$gallery = is_array( $gallery ) ? $gallery : array();
?>

<div id="mj-gallery-container">
    <ul class="mj-gallery-images">
        <?php foreach ( $gallery as $image_id ) :
            $image = wp_get_attachment_image_src( $image_id, 'thumbnail' );
            if ( $image ) :
        ?>
            <li class="mj-gallery-image" data-id="<?php echo esc_attr( $image_id ); ?>">
                <img src="<?php echo esc_url( $image[0] ); ?>" alt="">
                <input type="hidden" name="mj_gallery[]" value="<?php echo esc_attr( $image_id ); ?>">
                <button type="button" class="mj-remove-image">&times;</button>
            </li>
        <?php endif; endforeach; ?>
    </ul>
    <p>
        <button type="button" class="button" id="mj-add-gallery-images">
            <?php esc_html_e( 'Add Gallery Images', 'mitzies-jerk' ); ?>
        </button>
    </p>
</div>

<style>
.mj-gallery-images { display: flex; flex-wrap: wrap; gap: 10px; margin: 0; padding: 0; list-style: none; }
.mj-gallery-image { position: relative; width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
.mj-gallery-image img { width: 100%; height: 100%; object-fit: cover; }
.mj-gallery-image .mj-remove-image { position: absolute; top: 2px; right: 2px; background: #dc3545; color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 12px; cursor: pointer; line-height: 1; padding: 0; }
</style>

<script>
jQuery(document).ready(function($) {
    var frame;

    $('#mj-add-gallery-images').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: '<?php esc_html_e( 'Select Gallery Images', 'mitzies-jerk' ); ?>',
            button: { text: '<?php esc_html_e( 'Add to Gallery', 'mitzies-jerk' ); ?>' },
            multiple: true
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            attachments.forEach(function(attachment) {
                var html = '<li class="mj-gallery-image" data-id="' + attachment.id + '">' +
                    '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="">' +
                    '<input type="hidden" name="mj_gallery[]" value="' + attachment.id + '">' +
                    '<button type="button" class="mj-remove-image">&times;</button>' +
                    '</li>';
                $('.mj-gallery-images').append(html);
            });
        });

        frame.open();
    });

    $(document).on('click', '.mj-remove-image', function() {
        $(this).closest('.mj-gallery-image').remove();
    });
});
</script>
