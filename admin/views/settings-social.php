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
$pt       = $options['social_pinterest'] ?? '';
$yt       = $options['social_youtube'] ?? '';
?>

<h2><?php esc_html_e( 'Social Profiles', 'klaw-seo' ); ?></h2>
<p><?php esc_html_e( 'These URLs are added to your LocalBusiness schema as sameAs, which helps Google associate your website with your official social accounts.', 'klaw-seo' ); ?></p>

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

    <tr>
        <th scope="row">
            <label for="klaw-social-pinterest"><?php esc_html_e( 'Pinterest URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-social-pinterest" name="<?php echo esc_attr( $opt_name ); ?>[social_pinterest]"
                   value="<?php echo esc_url( $pt ); ?>" class="regular-text" placeholder="https://pinterest.com/yourprofile" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-social-youtube"><?php esc_html_e( 'YouTube URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-social-youtube" name="<?php echo esc_attr( $opt_name ); ?>[social_youtube]"
                   value="<?php echo esc_url( $yt ); ?>" class="regular-text" placeholder="https://youtube.com/@yourchannel" />
        </td>
    </tr>
</table>
