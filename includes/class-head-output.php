<?php
/**
 * Klaw SEO — Head Output
 *
 * Outputs all SEO meta tags, Open Graph, Twitter Cards, canonical URLs, and robots directives.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Head_Output {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 1 );
        add_filter( 'document_title_parts', [ $this, 'filter_title' ], 99 );
        add_filter( 'get_the_archive_title', [ $this, 'clean_archive_title' ], 99 );
        add_filter( 'wp_robots', [ $this, 'filter_wp_robots' ], 20 );
        // Remove WordPress default canonical to avoid duplicates.
        remove_action( 'wp_head', 'rel_canonical' );
    }

    /**
     * Explicitly set index/follow (or noindex/nofollow) on the wp_robots tag.
     *
     * WordPress's default behavior is to omit "index, follow" because it's
     * implied. We add it explicitly so users checking the page source get
     * unambiguous confirmation.
     *
     * @param  array $robots Robots directives array.
     * @return array
     */
    public function filter_wp_robots( $robots ) {
        $noindex = false;

        if ( is_singular() ) {
            $meta = get_post_meta( get_the_ID(), '_klaw_seo_noindex', true );
            if ( $meta === '1' ) {
                $noindex = true;
            }
        }

        // Clear any conflicting values before setting ours.
        unset( $robots['index'], $robots['follow'], $robots['noindex'], $robots['nofollow'] );

        if ( $noindex ) {
            $robots['noindex']  = true;
            $robots['nofollow'] = true;
        } else {
            $robots['index']  = true;
            $robots['follow'] = true;
        }

        return $robots;
    }

    /**
     * Filter the document <title> tag.
     */
    public function filter_title( $parts ) {
        // Strip HTML from title (WP 6.1+ wraps archive titles in <span>).
        // Also remove WordPress prefixes like "Archives:", "Category:", etc.
        if ( isset( $parts['title'] ) ) {
            $parts['title'] = wp_strip_all_tags( $parts['title'] );
            $parts['title'] = preg_replace( '/^(Archives|Category|Tag|Author):\s*/i', '', $parts['title'] );
        }

        if ( is_singular() ) {
            $custom = get_post_meta( get_the_ID(), '_klaw_seo_title', true );
            if ( $custom ) {
                return [ 'title' => $custom ];
            }
        }

        // Apply title template — returns full title string, so remove other parts.
        $settings = get_option( 'klaw_seo_settings', [] );
        $sep      = $settings['title_separator'] ?? '|';

        if ( isset( $parts['title'] ) ) {
            $template_result = $this->apply_template( $parts['title'], $settings, $sep );
            if ( $template_result !== $parts['title'] ) {
                // Template was applied — it already contains site name/sep, so remove WP defaults.
                return [ 'title' => $template_result ];
            }
        }

        return $parts;
    }

    /**
     * Strip "Archives:", "Category:", etc. prefixes from archive titles.
     */
    public function clean_archive_title( $title ) {
        return preg_replace( '/^.+?:\s*/u', '', wp_strip_all_tags( $title ) );
    }

    /**
     * Output all SEO tags in <head>.
     */
    public function output() {
        echo "\n<!-- Klaw SEO -->\n";

        $this->output_description();
        $this->output_canonical();

        echo "<!-- /Klaw SEO -->\n\n";
    }

    /**
     * Meta description.
     */
    private function output_description() {
        $desc = '';

        if ( is_singular() ) {
            $desc = get_post_meta( get_the_ID(), '_klaw_seo_description', true );
            if ( ! $desc ) {
                $desc = $this->auto_description();
            }
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $desc = term_description();
            $desc = wp_strip_all_tags( $desc );
            $desc = mb_substr( $desc, 0, 160 );
        } elseif ( is_post_type_archive() ) {
            // Use the post type's registered description if one was set.
            $pt = get_post_type_object( get_query_var( 'post_type' ) );
            if ( $pt && ! empty( $pt->description ) ) {
                $desc = wp_strip_all_tags( $pt->description );
            }
        }

        // Universal fallback chain: Klaw SEO default description -> WP tagline.
        if ( ! $desc ) {
            $desc = $this->site_default_description();
        }

        if ( $desc ) {
            printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
        }
    }

    /**
     * Get the site-wide fallback description.
     *
     * Priority: Klaw SEO "Default Meta Description" setting -> WordPress tagline.
     *
     * @return string
     */
    private function site_default_description() {
        $settings = get_option( 'klaw_seo_settings', [] );
        $default  = trim( $settings['default_meta_description'] ?? '' );
        if ( $default ) {
            return $default;
        }
        return (string) get_bloginfo( 'description' );
    }

    /**
     * Canonical URL.
     */
    private function output_canonical() {
        $url = '';

        if ( is_singular() ) {
            $url = get_post_meta( get_the_ID(), '_klaw_seo_canonical', true );
            if ( ! $url ) {
                $url = get_permalink();
            }
        } elseif ( is_front_page() ) {
            $url = home_url( '/' );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $url = get_term_link( get_queried_object() );
        } elseif ( is_post_type_archive() ) {
            $url = get_post_type_archive_link( get_query_var( 'post_type' ) );
        }

        // Handle pagination.
        $paged = get_query_var( 'paged', 0 );
        if ( $paged > 1 && $url ) {
            $url = get_pagenum_link( $paged );
        }

        if ( $url && ! is_wp_error( $url ) ) {
            printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
        }
    }

    /**
     * Auto-generate description from current post.
     */
    private function auto_description() {
        $post = get_post();
        if ( ! $post ) {
            return '';
        }

        $settings = get_option( 'klaw_seo_settings', [] );
        $source   = $settings['description_source'] ?? 'excerpt_first';

        if ( $source === 'excerpt_first' && $post->post_excerpt ) {
            return wp_trim_words( $post->post_excerpt, 25, '...' );
        }

        $content = wp_strip_all_tags( $post->post_content );
        $content = preg_replace( '/\s+/', ' ', trim( $content ) );
        return mb_substr( $content, 0, 160 );
    }

    /**
     * Apply title template with token replacement.
     */
    private function apply_template( $title, $settings, $sep ) {
        $template = '';

        if ( is_front_page() ) {
            $template = $settings['title_template_home'] ?? '';
        } elseif ( is_home() ) {
            $template = $settings['title_template_page'] ?? '{post_title} {sep} {site_title}';
        } elseif ( is_singular( 'post' ) ) {
            $template = $settings['title_template_post'] ?? '';
        } elseif ( is_singular( 'page' ) ) {
            $template = $settings['title_template_page'] ?? '';
        } elseif ( is_archive() ) {
            $template = $settings['title_template_archive'] ?? '';
        }

        if ( ! $template ) {
            return $title;
        }

        $site_name     = get_bloginfo( 'name' );
        $tagline       = get_bloginfo( 'description' );
        $archive_title = is_archive() ? preg_replace( '/^.+?:\s*/u', '', wp_strip_all_tags( get_the_archive_title() ) ) : '';

        $replacements = [
            '{post_title}'    => $title,
            '{site_title}'    => $site_name,
            '{sep}'           => $sep,
            '{tagline}'       => $tagline,
            '{archive_title}' => $archive_title,
        ];

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }
}
