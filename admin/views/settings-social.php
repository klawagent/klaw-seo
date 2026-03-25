<?php
/**
 * Klaw SEO — Social Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name = Klaw_SEO_Settings::OPTION;
$fb       = $options['social_facebook'] ?? '';
$ig       = $options['social_instagram'] ?? '';
$tw       = $options['social_twitter'] ?? '';
$li       = $options['social_linkedin'] ?? '';
$og_img   = $options['default_og_image'] ?? '';
?>

<h2><?php esc_html_e( 'Social Profiles', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-social-facebook"><?php esc_html_e( 'Facebook URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-social-facebook" name="<?php echo esc_attr( $opt_name ); ?>[social_facebook]"
                   value="<?php echo esc_url( $fb ); ?>" class="regular-text" placeholder="https://facebook.com/yourpage" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-social-instagram"><?php esc_html_e( 'Instagram URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-social-instagram" name="<?php echo esc_attr( $opt_name ); ?>[social_instagram]"
                   value="<?php echo esc_url( $ig ); ?>" class="regular-text" placeholder="https://instagram.com/yourprofile" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-social-twitter"><?php esc_html_e( 'X / Twitter URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-social-twitter" name="<?php echo esc_attr( $opt_name ); ?>[social_twitter]"
                   value="<?php echo esc_url( $tw ); ?>" class="regular-text" placeholder="https://x.com/yourhandle" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-social-linkedin"><?php esc_html_e( 'LinkedIn URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-social-linkedin" name="<?php echo esc_attr( $opt_name ); ?>[social_linkedin]"
                   value="<?php echo esc_url( $li ); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany" />
        </td>
    </tr>
</table>

<h2><?php esc_html_e( 'Default Open Graph Image', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label><?php esc_html_e( 'Fallback Image', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <div class="klaw-seo-image-picker" id="klaw-seo-default-og-picker">
                <input type="hidden" id="klaw-default-og-image" name="<?php echo esc_attr( $opt_name ); ?>[default_og_image]"
                       value="<?php echo esc_url( $og_img ); ?>" />
                <?php if ( $og_img ) : ?>
                    <img src="<?php echo esc_url( $og_img ); ?>" class="klaw-seo-og-preview-img" style="max-width:300px;display:block;margin-bottom:10px;" />
                <?php endif; ?>
                <button type="button" class="button klaw-seo-pick-image" data-target="klaw-default-og-image">
                    <?php esc_html_e( 'Select Image', 'klaw-seo' ); ?>
                </button>
                <button type="button" class="button klaw-seo-remove-image" data-target="klaw-default-og-image"
                    <?php echo $og_img ? '' : 'style="display:none"'; ?>>
                    <?php esc_html_e( 'Remove', 'klaw-seo' ); ?>
                </button>
            </div>
            <p class="description"><?php esc_html_e( 'Used when a post has no featured image or custom OG image. Recommended size: 1200x630px.', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var picker = document.getElementById('klaw-seo-default-og-picker');
    if (!picker) return;

    picker.querySelector('.klaw-seo-pick-image').addEventListener('click', function () {
        var frame = wp.media({ title: 'Select Default OG Image', multiple: false, library: { type: 'image' } });
        var target = this.getAttribute('data-target');
        frame.on('select', function () {
            var url = frame.state().get('selection').first().toJSON().url;
            document.getElementById(target).value = url;
            var img = picker.querySelector('.klaw-seo-og-preview-img');
            if (!img) {
                img = document.createElement('img');
                img.className = 'klaw-seo-og-preview-img';
                img.style.maxWidth = '300px';
                img.style.display = 'block';
                img.style.marginBottom = '10px';
                picker.insertBefore(img, picker.firstChild);
            }
            img.src = url;
            picker.querySelector('.klaw-seo-remove-image').style.display = '';
        });
        frame.open();
    });

    picker.querySelector('.klaw-seo-remove-image').addEventListener('click', function () {
        var target = this.getAttribute('data-target');
        document.getElementById(target).value = '';
        var img = picker.querySelector('.klaw-seo-og-preview-img');
        if (img) img.remove();
        this.style.display = 'none';
    });
});
</script>
