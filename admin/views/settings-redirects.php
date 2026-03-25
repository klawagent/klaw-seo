<?php
/**
 * Klaw SEO — Redirects Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'klaw_seo_redirects';

// Check table existence before querying.
$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

// Pagination.
$per_page    = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset      = ( $current_page - 1 ) * $per_page;

$total     = 0;
$redirects = [];

if ( $table_exists ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
    $redirects = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );
}

$total_pages = ceil( $total / $per_page );
$edit_id     = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$editing     = null;

if ( $edit_id ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $edit_id ) );
}
?>

</form><!-- Close the outer settings form — redirects uses its own forms. -->

<h2><?php esc_html_e( 'Redirects', 'klaw-seo' ); ?></h2>

<!-- Add / Edit Form -->
<div class="klaw-seo-redirect-form" style="background:#fff;padding:15px 20px;border:1px solid #c3c4c7;margin-bottom:20px;">
    <h3><?php echo $editing ? esc_html__( 'Edit Redirect', 'klaw-seo' ) : esc_html__( 'Add Redirect', 'klaw-seo' ); ?></h3>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'klaw_seo_redirect_save', 'klaw_seo_redirect_nonce' ); ?>
        <input type="hidden" name="action" value="klaw_seo_save_redirect" />
        <?php if ( $editing ) : ?>
            <input type="hidden" name="redirect_id" value="<?php echo esc_attr( $editing->id ); ?>" />
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="klaw-redirect-source"><?php esc_html_e( 'Source URL', 'klaw-seo' ); ?></label></th>
                <td>
                    <input type="text" id="klaw-redirect-source" name="redirect_source"
                           value="<?php echo esc_attr( $editing->source_url ?? '' ); ?>" class="regular-text"
                           placeholder="/old-page/" required />
                    <p class="description"><?php esc_html_e( 'Relative path from site root (e.g. /old-page/).', 'klaw-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="klaw-redirect-target"><?php esc_html_e( 'Target URL', 'klaw-seo' ); ?></label></th>
                <td>
                    <input type="text" id="klaw-redirect-target" name="redirect_target"
                           value="<?php echo esc_attr( $editing->target_url ?? '' ); ?>" class="regular-text"
                           placeholder="/new-page/ or https://example.com/page" required />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="klaw-redirect-type"><?php esc_html_e( 'Type', 'klaw-seo' ); ?></label></th>
                <td>
                    <select id="klaw-redirect-type" name="redirect_type">
                        <option value="301" <?php selected( ( $editing->redirect_type ?? '301' ), '301' ); ?>>301 — Permanent</option>
                        <option value="302" <?php selected( ( $editing->redirect_type ?? '' ), '302' ); ?>>302 — Temporary</option>
                        <option value="307" <?php selected( ( $editing->redirect_type ?? '' ), '307' ); ?>>307 — Temporary (strict)</option>
                        <option value="410" <?php selected( ( $editing->redirect_type ?? '' ), '410' ); ?>>410 — Gone</option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button( $editing ? __( 'Update Redirect', 'klaw-seo' ) : __( 'Add Redirect', 'klaw-seo' ) ); ?>
    </form>
</div>

<!-- Existing Redirects Table -->
<?php if ( $redirects ) : ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Source', 'klaw-seo' ); ?></th>
            <th><?php esc_html_e( 'Target', 'klaw-seo' ); ?></th>
            <th><?php esc_html_e( 'Type', 'klaw-seo' ); ?></th>
            <th><?php esc_html_e( 'Hits', 'klaw-seo' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'klaw-seo' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $redirects as $r ) :
            $page_url  = admin_url( 'admin.php?page=klaw-seo-redirects' );
            $edit_url  = add_query_arg( 'edit', $r->id, $page_url );
            $del_url   = wp_nonce_url(
                admin_url( 'admin-post.php?action=klaw_seo_delete_redirect&redirect_id=' . $r->id ),
                'klaw_seo_delete_redirect_' . $r->id
            );
            ?>
            <tr>
                <td><code><?php echo esc_html( $r->source_url ); ?></code></td>
                <td><?php echo esc_html( $r->target_url ); ?></td>
                <td><?php echo esc_html( $r->redirect_type ); ?></td>
                <td><?php echo esc_html( $r->hits ); ?></td>
                <td>
                    <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'klaw-seo' ); ?></a> |
                    <a href="<?php echo esc_url( $del_url ); ?>" class="klaw-seo-delete"
                       onclick="return confirm('<?php esc_attr_e( 'Delete this redirect?', 'klaw-seo' ); ?>');">
                        <?php esc_html_e( 'Delete', 'klaw-seo' ); ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ( $total_pages > 1 ) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo wp_kses_post( paginate_links( [
                'base'    => add_query_arg( 'paged', '%#%' ),
                'format'  => '',
                'current' => $current_page,
                'total'   => $total_pages,
            ] ) );
            ?>
        </div>
    </div>
<?php endif; ?>

<?php else : ?>
    <p><?php esc_html_e( 'No redirects configured yet.', 'klaw-seo' ); ?></p>
<?php endif; ?>

<!-- CSV Import / Export -->
<div style="margin-top:20px;">
    <h3><?php esc_html_e( 'Import / Export', 'klaw-seo' ); ?></h3>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="display:inline;">
        <?php wp_nonce_field( 'klaw_seo_import_redirects', 'klaw_seo_import_nonce' ); ?>
        <input type="hidden" name="action" value="klaw_seo_import_redirects" />
        <input type="file" name="klaw_seo_csv" accept=".csv" required />
        <?php submit_button( __( 'Import CSV', 'klaw-seo' ), 'secondary', 'submit', false ); ?>
    </form>

    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=klaw_seo_export_redirects' ), 'klaw_seo_export_redirects' ) ); ?>"
       class="button" style="margin-left:10px;">
        <?php esc_html_e( 'Export CSV', 'klaw-seo' ); ?>
    </a>
</div>

<form><!-- Reopen a form tag to keep the page structure valid. -->
