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
        // Remove WordPress default canonical to avoid duplicates.
        remove_action( 'wp_head', 'rel_canonical' );
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
        $this->output_robots();
        $this->output_open_graph();
        $this->output_twitter();

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
        } elseif ( is_front_page() ) {
            $desc = get_bloginfo( 'description' );
        }

        if ( $desc ) {
            printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
        }
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
     * Robots meta tag.
     */
    private function output_robots() {
        if ( is_singular() ) {
            $noindex = get_post_meta( get_the_ID(), '_klaw_seo_noindex', true );
            if ( $noindex === '1' ) {
                echo '<meta name="robots" content="noindex, follow" />' . "\n";
            }
        }
    }

    /**
     * Open Graph tags.
     */
    private function output_open_graph() {
        $tags = [];

        $tags['og:site_name'] = get_bloginfo( 'name' );
        $tags['og:locale']    = get_locale();

        if ( is_singular() ) {
            $id = get_the_ID();

            $tags['og:type']  = ( get_post_type() === 'page' ) ? 'website' : 'article';
            $tags['og:url']   = get_permalink( $id );
            $tags['og:title'] = get_post_meta( $id, '_klaw_seo_og_title', true )
                                ?: get_post_meta( $id, '_klaw_seo_title', true )
                                ?: get_the_title( $id );

            $tags['og:description'] = get_post_meta( $id, '_klaw_seo_og_description', true )
                                      ?: get_post_meta( $id, '_klaw_seo_description', true )
                                      ?: $this->auto_description();

            // Image: custom OG > featured image > site default.
            $og_image = get_post_meta( $id, '_klaw_seo_og_image', true );
            if ( ! $og_image && has_post_thumbnail( $id ) ) {
                $og_image = get_the_post_thumbnail_url( $id, 'large' );
            }
            if ( ! $og_image ) {
                $og_image = klaw_seo_get( 'default_og_image' );
            }
            if ( $og_image ) {
                $tags['og:image'] = $og_image;
            }
        } elseif ( is_front_page() ) {
            $tags['og:type']        = 'website';
            $tags['og:url']         = home_url( '/' );
            $tags['og:title']       = get_bloginfo( 'name' );
            $tags['og:description'] = get_bloginfo( 'description' );

            $default_img = klaw_seo_get( 'default_og_image' );
            if ( $default_img ) {
                $tags['og:image'] = $default_img;
            }
        }

        foreach ( $tags as $prop => $content ) {
            if ( $content ) {
                printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $prop ), esc_attr( $content ) );
            }
        }
    }

    /**
     * Twitter Card tags.
     */
    private function output_twitter() {
        if ( ! is_singular() && ! is_front_page() ) {
            return;
        }

        $id = is_singular() ? get_the_ID() : 0;

        $title = $id ? ( get_post_meta( $id, '_klaw_seo_og_title', true )
                        ?: get_post_meta( $id, '_klaw_seo_title', true )
                        ?: get_the_title( $id ) )
                     : get_bloginfo( 'name' );

        $desc = $id ? ( get_post_meta( $id, '_klaw_seo_og_description', true )
                        ?: get_post_meta( $id, '_klaw_seo_description', true )
                        ?: $this->auto_description() )
                    : get_bloginfo( 'description' );

        $image = '';
        if ( $id ) {
            $image = get_post_meta( $id, '_klaw_seo_og_image', true );
            if ( ! $image && has_post_thumbnail( $id ) ) {
                $image = get_the_post_thumbnail_url( $id, 'large' );
            }
        }
        if ( ! $image ) {
            $image = klaw_seo_get( 'default_og_image' );
        }

        echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
        printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );

        if ( $desc ) {
            printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $desc ) );
        }
        if ( $image ) {
            printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
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

        if ( is_front_page() && ! is_home() ) {
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
