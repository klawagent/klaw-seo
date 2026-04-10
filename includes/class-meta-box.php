<?php
/**
 * Klaw SEO — Meta Box
 *
 * Adds SEO meta box to all public post types with tabs:
 * General (title, description, preview, noindex), Social (OG overrides), Advanced (canonical, FAQ).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Meta_Box {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register' ] );
        add_action( 'save_post', [ $this, 'save' ], 10, 2 );
    }

    /**
     * Register meta box on all public post types.
     */
    public function register() {
        $types = get_post_types( [ 'public' => true ], 'names' );
        unset( $types['attachment'] );

        foreach ( $types as $type ) {
            add_meta_box(
                'klaw_seo_meta',
                'Klaw SEO',
                [ $this, 'render' ],
                $type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the meta box.
     */
    public function render( $post ) {
        wp_nonce_field( 'klaw_seo_save_meta', 'klaw_seo_meta_nonce' );

        $title       = get_post_meta( $post->ID, '_klaw_seo_title', true );
        $description = get_post_meta( $post->ID, '_klaw_seo_description', true );
        $noindex     = get_post_meta( $post->ID, '_klaw_seo_noindex', true );
        $og_title    = get_post_meta( $post->ID, '_klaw_seo_og_title', true );
        $og_desc     = get_post_meta( $post->ID, '_klaw_seo_og_description', true );
        $og_image    = get_post_meta( $post->ID, '_klaw_seo_og_image', true );
        $canonical   = get_post_meta( $post->ID, '_klaw_seo_canonical', true );
        $faq         = get_post_meta( $post->ID, '_klaw_seo_faq', true );

        if ( ! is_array( $faq ) ) {
            $faq = [];
        }

        $settings    = get_option( 'klaw_seo_settings', [] );
        $sep         = $settings['title_separator'] ?? '|';
        $site_title  = get_bloginfo( 'name' );
        $placeholder = get_the_title( $post ) . " $sep $site_title";
        $auto_desc   = $this->generate_description( $post );
        $permalink   = get_permalink( $post );
        ?>
        <div class="klaw-seo-meta-box">
            <div class="klaw-seo-tabs">
                <button type="button" class="klaw-seo-tab active" data-tab="general">General</button>
                <button type="button" class="klaw-seo-tab" data-tab="social">Social</button>
                <button type="button" class="klaw-seo-tab" data-tab="advanced">Advanced</button>
            </div>

            <!-- General Tab -->
            <div class="klaw-seo-tab-content active" data-tab="general">
                <div class="klaw-seo-field">
                    <label for="klaw-seo-title">SEO Title</label>
                    <input type="text" id="klaw-seo-title" name="klaw_seo_title"
                           value="<?php echo esc_attr( $title ); ?>"
                           placeholder="<?php echo esc_attr( $placeholder ); ?>"
                           data-default="<?php echo esc_attr( $placeholder ); ?>" />
                    <div class="klaw-seo-counter" data-target="klaw-seo-title" data-limit="60">
                        <span class="klaw-seo-counter-num">0</span> / 60
                    </div>
                </div>

                <div class="klaw-seo-field">
                    <label for="klaw-seo-description">Meta Description</label>
                    <textarea id="klaw-seo-description" name="klaw_seo_description" rows="3"
                              placeholder="<?php echo esc_attr( $auto_desc ); ?>"
                              data-default="<?php echo esc_attr( $auto_desc ); ?>"
                    ><?php echo esc_textarea( $description ); ?></textarea>
                    <div class="klaw-seo-counter" data-target="klaw-seo-description" data-limit="160">
                        <span class="klaw-seo-counter-num">0</span> / 160
                    </div>
                </div>

                <div class="klaw-seo-preview" id="klaw-seo-preview">
                    <div class="klaw-seo-preview-title"><?php echo esc_html( $title ?: $placeholder ); ?></div>
                    <div class="klaw-seo-preview-url"><?php echo esc_url( $permalink ); ?></div>
                    <div class="klaw-seo-preview-desc"><?php echo esc_html( $description ?: $auto_desc ); ?></div>
                </div>

                <div class="klaw-seo-field klaw-seo-checkbox-field">
                    <label>
                        <input type="checkbox" name="klaw_seo_noindex" value="1"
                            <?php checked( $noindex, '1' ); ?> />
                        Hide from search engines (noindex)
                    </label>
                </div>
            </div>

            <!-- Social Tab -->
            <div class="klaw-seo-tab-content" data-tab="social">
                <div class="klaw-seo-field">
                    <label for="klaw-seo-og-title">Open Graph Title</label>
                    <input type="text" id="klaw-seo-og-title" name="klaw_seo_og_title"
                           value="<?php echo esc_attr( $og_title ); ?>"
                           placeholder="Defaults to SEO title" />
                </div>

                <div class="klaw-seo-field">
                    <label for="klaw-seo-og-description">Open Graph Description</label>
                    <textarea id="klaw-seo-og-description" name="klaw_seo_og_description" rows="2"
                              placeholder="Defaults to meta description"
                    ><?php echo esc_textarea( $og_desc ); ?></textarea>
                </div>

                <div class="klaw-seo-field">
                    <label>Open Graph Image</label>
                    <div class="klaw-seo-image-picker">
                        <input type="hidden" id="klaw-seo-og-image" name="klaw_seo_og_image"
                               value="<?php echo esc_url( $og_image ); ?>" />
                        <?php if ( $og_image ) : ?>
                            <img src="<?php echo esc_url( $og_image ); ?>" class="klaw-seo-og-preview-img" />
                        <?php endif; ?>
                        <button type="button" class="button klaw-seo-pick-image">Select Image</button>
                        <button type="button" class="button klaw-seo-remove-image" <?php echo $og_image ? '' : 'style="display:none"'; ?>>Remove</button>
                    </div>
                </div>
            </div>

            <!-- Advanced Tab -->
            <div class="klaw-seo-tab-content" data-tab="advanced">
                <div class="klaw-seo-field">
                    <label for="klaw-seo-canonical">Canonical URL</label>
                    <input type="url" id="klaw-seo-canonical" name="klaw_seo_canonical"
                           value="<?php echo esc_url( $canonical ); ?>"
                           placeholder="<?php echo esc_url( $permalink ); ?>" />
                    <p class="description">Leave blank to use the default permalink.</p>
                </div>

                <div class="klaw-seo-faq-section">
                    <h4>FAQ Schema</h4>
                    <p class="description">Add question/answer pairs to generate FAQ structured data for this page.</p>
                    <div id="klaw-seo-faq-items">
                        <?php foreach ( $faq as $i => $item ) : ?>
                            <div class="klaw-seo-faq-item" data-index="<?php echo (int) $i; ?>">
                                <input type="text" name="klaw_seo_faq[<?php echo (int) $i; ?>][question]"
                                       value="<?php echo esc_attr( $item['question'] ?? '' ); ?>"
                                       placeholder="Question" />
                                <textarea name="klaw_seo_faq[<?php echo (int) $i; ?>][answer]" rows="2"
                                          placeholder="Answer"><?php echo esc_textarea( $item['answer'] ?? '' ); ?></textarea>
                                <button type="button" class="button klaw-seo-faq-remove">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button klaw-seo-faq-add" id="klaw-seo-faq-add">+ Add Question</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta box data.
     */
    public function save( $post_id, $post ) {
        if ( ! isset( $_POST['klaw_seo_meta_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_POST['klaw_seo_meta_nonce'], 'klaw_seo_save_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            'klaw_seo_title'          => '_klaw_seo_title',
            'klaw_seo_description'    => '_klaw_seo_description',
            'klaw_seo_og_title'       => '_klaw_seo_og_title',
            'klaw_seo_og_description' => '_klaw_seo_og_description',
            'klaw_seo_canonical'      => '_klaw_seo_canonical',
        ];

        foreach ( $fields as $post_key => $meta_key ) {
            $value = sanitize_text_field( $_POST[ $post_key ] ?? '' );
            if ( in_array( $post_key, [ 'klaw_seo_description', 'klaw_seo_og_description' ], true ) ) {
                $value = sanitize_textarea_field( $_POST[ $post_key ] ?? '' );
            }
            if ( in_array( $post_key, [ 'klaw_seo_canonical' ], true ) ) {
                $value = esc_url_raw( $_POST[ $post_key ] ?? '' );
            }
            if ( $value ) {
                update_post_meta( $post_id, $meta_key, $value );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }

        // OG Image (URL field)
        $og_image = esc_url_raw( $_POST['klaw_seo_og_image'] ?? '' );
        if ( $og_image ) {
            update_post_meta( $post_id, '_klaw_seo_og_image', $og_image );
        } else {
            delete_post_meta( $post_id, '_klaw_seo_og_image' );
        }

        // Noindex
        if ( ! empty( $_POST['klaw_seo_noindex'] ) ) {
            update_post_meta( $post_id, '_klaw_seo_noindex', '1' );
        } else {
            delete_post_meta( $post_id, '_klaw_seo_noindex' );
        }

        // FAQ
        $faq = [];
        if ( ! empty( $_POST['klaw_seo_faq'] ) && is_array( $_POST['klaw_seo_faq'] ) ) {
            foreach ( $_POST['klaw_seo_faq'] as $item ) {
                $q = sanitize_text_field( $item['question'] ?? '' );
                $a = sanitize_textarea_field( $item['answer'] ?? '' );
                if ( $q && $a ) {
                    $faq[] = [ 'question' => $q, 'answer' => $a ];
                }
            }
        }
        if ( $faq ) {
            update_post_meta( $post_id, '_klaw_seo_faq', $faq );
        } else {
            delete_post_meta( $post_id, '_klaw_seo_faq' );
        }
    }

    /**
     * Auto-generate meta description from post content.
     */
    private function generate_description( $post ) {
        $settings = get_option( 'klaw_seo_settings', [] );
        $source   = $settings['description_source'] ?? 'excerpt_first';

        if ( $source === 'excerpt_first' && $post->post_excerpt ) {
            return wp_trim_words( $post->post_excerpt, 25, '...' );
        }

        $content = wp_strip_all_tags( $post->post_content );
        $content = preg_replace( '/\s+/', ' ', trim( $content ) );
        return mb_substr( $content, 0, 160 );
    }
}
