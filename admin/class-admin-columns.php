<?php
/**
 * Klaw SEO — Admin Columns
 *
 * Adds "SEO Title" and "Noindex" columns to all public post type list tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Admin_Columns {

    /**
     * Constructor — register hooks for all public post types.
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_columns' ] );
    }

    /**
     * Attach column filters/actions for every public post type.
     */
    public function register_columns() {
        $types = get_post_types( [ 'public' => true ], 'names' );
        unset( $types['attachment'] );

        foreach ( $types as $type ) {
            add_filter( "manage_{$type}_posts_columns", [ $this, 'add_columns' ] );
            add_action( "manage_{$type}_posts_custom_column", [ $this, 'render_column' ], 10, 2 );
        }
    }

    /**
     * Insert the SEO columns after the title column.
     *
     * @param  array $columns Existing columns.
     * @return array          Modified columns.
     */
    public function add_columns( $columns ) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['klaw_seo_title']   = __( 'SEO Title', 'klaw-seo' );
                $new['klaw_seo_noindex'] = __( 'Noindex', 'klaw-seo' );
            }
        }
        return $new;
    }

    /**
     * Render column content.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_column( $column, $post_id ) {
        if ( $column === 'klaw_seo_title' ) {
            $title = get_post_meta( $post_id, '_klaw_seo_title', true );
            if ( $title ) {
                $len   = mb_strlen( $title );
                $class = $len > 60 ? 'klaw-seo-col-over' : 'klaw-seo-col-ok';
                printf(
                    '<span class="%s" title="%s">%s <small>(%d)</small></span>',
                    esc_attr( $class ),
                    esc_attr( $title ),
                    esc_html( mb_strimwidth( $title, 0, 50, '...' ) ),
                    $len
                );
            } else {
                echo '<span class="klaw-seo-col-default">&mdash;</span>';
            }
        }

        if ( $column === 'klaw_seo_noindex' ) {
            $noindex = get_post_meta( $post_id, '_klaw_seo_noindex', true );
            if ( $noindex === '1' ) {
                echo '<span class="klaw-seo-noindex-badge" title="' . esc_attr__( 'Hidden from search engines', 'klaw-seo' ) . '">Noindex</span>';
            } else {
                echo '<span class="klaw-seo-index-badge" title="' . esc_attr__( 'Indexable', 'klaw-seo' ) . '">Index</span>';
            }
        }
    }
}
