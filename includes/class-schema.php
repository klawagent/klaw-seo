<?php
/**
 * Klaw SEO — Schema / JSON-LD
 *
 * Outputs structured data in JSON-LD format:
 * - LocalBusiness (from settings)
 * - Event (from configurable post type + field mapping)
 * - BreadcrumbList (auto from page hierarchy)
 * - FAQPage (from _klaw_seo_faq post meta)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Schema {

    /**
     * Constructor — hook into wp_head at priority 2.
     */
    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 2 );
    }

    /**
     * Main output handler.
     */
    public function output() {
        $schemas = [];

        $local = $this->local_business();
        if ( $local ) {
            $schemas[] = $local;
        }

        $event = $this->event();
        if ( $event ) {
            $schemas[] = $event;
        }

        $breadcrumb = $this->breadcrumb();
        if ( $breadcrumb ) {
            $schemas[] = $breadcrumb;
        }

        $faq = $this->faq();
        if ( $faq ) {
            $schemas[] = $faq;
        }

        foreach ( $schemas as $schema ) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            echo "\n</script>\n";
        }
    }

    /**
     * Build LocalBusiness schema from plugin settings.
     *
     * @return array|null
     */
    private function local_business() {
        $s = get_option( 'klaw_seo_settings', [] );

        if ( empty( $s['business_name'] ) ) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $s['business_type'] ?? 'LocalBusiness',
            'name'     => $s['business_name'],
        ];

        // Address.
        $address = [];
        if ( ! empty( $s['business_street'] ) ) {
            $address['streetAddress'] = $s['business_street'];
        }
        if ( ! empty( $s['business_city'] ) ) {
            $address['addressLocality'] = $s['business_city'];
        }
        if ( ! empty( $s['business_state'] ) ) {
            $address['addressRegion'] = $s['business_state'];
        }
        if ( ! empty( $s['business_zip'] ) ) {
            $address['postalCode'] = $s['business_zip'];
        }
        if ( ! empty( $s['business_country'] ) ) {
            $address['addressCountry'] = $s['business_country'];
        }
        if ( $address ) {
            $address['@type'] = 'PostalAddress';
            $schema['address'] = $address;
        }

        // Geo.
        if ( ! empty( $s['business_lat'] ) && ! empty( $s['business_lng'] ) ) {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $s['business_lat'],
                'longitude' => (float) $s['business_lng'],
            ];
        }

        // Contact.
        if ( ! empty( $s['business_phone'] ) ) {
            $schema['telephone'] = $s['business_phone'];
        }
        if ( ! empty( $s['business_email'] ) ) {
            $schema['email'] = $s['business_email'];
        }

        // Price range.
        if ( ! empty( $s['business_price_range'] ) ) {
            $schema['priceRange'] = $s['business_price_range'];
        }

        // URL.
        $schema['url'] = home_url( '/' );

        if ( ! empty( $s['business_gbp_url'] ) ) {
            $schema['hasMap'] = $s['business_gbp_url'];
        }

        // Opening hours.
        if ( ! empty( $s['business_hours'] ) ) {
            $hours = $this->parse_hours( $s['business_hours'] );
            if ( $hours ) {
                $schema['openingHoursSpecification'] = $hours;
            }
        }

        // Social profiles.
        $social = [];
        foreach ( [ 'social_facebook', 'social_instagram', 'social_twitter', 'social_linkedin' ] as $key ) {
            if ( ! empty( $s[ $key ] ) ) {
                $social[] = $s[ $key ];
            }
        }
        if ( $social ) {
            $schema['sameAs'] = $social;
        }

        return $schema;
    }

    /**
     * Parse operating hours text into schema format.
     *
     * @param  string $text Hours textarea content.
     * @return array
     */
    private function parse_hours( $text ) {
        $day_map = [
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
            'sunday'    => 'Sunday',
        ];

        $specs = [];
        $lines = array_filter( array_map( 'trim', explode( "\n", $text ) ) );

        foreach ( $lines as $line ) {
            // Expected format: Day HH:MM-HH:MM
            $parts = preg_split( '/\s+/', $line, 2 );
            if ( count( $parts ) < 2 ) {
                continue;
            }

            $day_key  = strtolower( $parts[0] );
            $time_str = $parts[1];

            if ( ! isset( $day_map[ $day_key ] ) ) {
                continue;
            }

            if ( strtolower( $time_str ) === 'closed' ) {
                continue;
            }

            $times = explode( '-', $time_str );
            if ( count( $times ) !== 2 ) {
                continue;
            }

            $specs[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $day_map[ $day_key ],
                'opens'     => trim( $times[0] ),
                'closes'    => trim( $times[1] ),
            ];
        }

        return $specs;
    }

    /**
     * Build Event schema for the configured post type using mapped custom fields.
     *
     * @return array|null
     */
    private function event() {
        if ( ! is_singular() ) {
            return null;
        }

        $s       = get_option( 'klaw_seo_settings', [] );
        $evt_pt  = $s['event_post_type'] ?? '';

        if ( ! $evt_pt || get_post_type() !== $evt_pt ) {
            return null;
        }

        $post_id = get_the_ID();
        $post    = get_post( $post_id );

        // Read mapped field keys.
        $date_key  = $s['event_date_field'] ?? '';
        $time_key  = $s['event_time_field'] ?? '';
        $venue_key = $s['event_venue_field'] ?? '';
        $desc_key  = $s['event_description_field'] ?? '';

        // Get field values from post meta.
        $event_date = $date_key ? get_post_meta( $post_id, $date_key, true ) : '';
        $event_time = $time_key ? get_post_meta( $post_id, $time_key, true ) : '';
        $desc       = $desc_key ? get_post_meta( $post_id, $desc_key, true ) : '';

        // Venue: if the value looks like a meta key, try to pull from post meta.
        // Otherwise treat it as a literal venue name (e.g. "Cherrywood Coffeehouse").
        $venue = '';
        if ( $venue_key ) {
            $meta_venue = get_post_meta( $post_id, $venue_key, true );
            $venue      = $meta_venue ? $meta_venue : $venue_key; // fallback to literal value if meta is empty
        }

        if ( ! $desc ) {
            $desc = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }

        // Build start date.
        $start_date = '';
        if ( $event_date ) {
            $start_date = $event_date;
            if ( $event_time ) {
                // Combine date and time.
                $start_date = rtrim( $event_date, 'T' ) . 'T' . $event_time;
            }
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Event',
            'name'        => get_the_title( $post_id ),
            'url'         => get_permalink( $post_id ),
            'description' => $desc,
        ];

        if ( $start_date ) {
            $schema['startDate'] = $start_date;
        }

        if ( $venue ) {
            $schema['location'] = [
                '@type' => 'Place',
                'name'  => $venue,
            ];
        }

        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }

        // Event status — default to scheduled.
        $schema['eventStatus']    = 'https://schema.org/EventScheduled';
        $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

        // Organizer — default to site.
        $schema['organizer'] = [
            '@type' => 'Organization',
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        ];

        return $schema;
    }

    /**
     * Build BreadcrumbList schema from page/post hierarchy.
     *
     * @return array|null
     */
    private function breadcrumb() {
        if ( is_front_page() || is_admin() ) {
            return null;
        }

        $items = [];
        $pos   = 1;

        // Home.
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => get_bloginfo( 'name' ),
            'item'     => home_url( '/' ),
        ];

        if ( is_singular() ) {
            $post = get_post();

            // Add taxonomy breadcrumb for posts.
            if ( $post->post_type === 'post' ) {
                $categories = get_the_category( $post->ID );
                if ( $categories ) {
                    $cat = $categories[0];
                    // Add parent categories.
                    $ancestors = get_ancestors( $cat->term_id, 'category' );
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $anc_id ) {
                        $anc = get_term( $anc_id, 'category' );
                        if ( $anc && ! is_wp_error( $anc ) ) {
                            $link = get_term_link( $anc );
                            if ( ! is_wp_error( $link ) ) {
                                $items[] = [
                                    '@type'    => 'ListItem',
                                    'position' => $pos++,
                                    'name'     => $anc->name,
                                    'item'     => $link,
                                ];
                            }
                        }
                    }
                    $cat_link = get_term_link( $cat );
                    if ( ! is_wp_error( $cat_link ) ) {
                        $items[] = [
                            '@type'    => 'ListItem',
                            'position' => $pos++,
                            'name'     => $cat->name,
                            'item'     => $cat_link,
                        ];
                    }
                }
            }

            // Add parent pages for hierarchical types.
            if ( is_post_type_hierarchical( $post->post_type ) && $post->post_parent ) {
                $ancestors = get_post_ancestors( $post->ID );
                $ancestors = array_reverse( $ancestors );
                foreach ( $ancestors as $anc_id ) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $pos++,
                        'name'     => get_the_title( $anc_id ),
                        'item'     => get_permalink( $anc_id ),
                    ];
                }
            }

            // Current page.
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            ];
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term ) {
                // Parent terms.
                if ( $term->parent ) {
                    $ancestors = get_ancestors( $term->term_id, $term->taxonomy );
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $anc_id ) {
                        $anc = get_term( $anc_id, $term->taxonomy );
                        if ( $anc && ! is_wp_error( $anc ) ) {
                            $link = get_term_link( $anc );
                            if ( ! is_wp_error( $link ) ) {
                                $items[] = [
                                    '@type'    => 'ListItem',
                                    'position' => $pos++,
                                    'name'     => $anc->name,
                                    'item'     => $link,
                                ];
                            }
                        }
                    }
                }
                $term_link = get_term_link( $term );
                if ( ! is_wp_error( $term_link ) ) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $pos,
                        'name'     => $term->name,
                        'item'     => $term_link,
                    ];
                }
            }
        } elseif ( is_post_type_archive() ) {
            $pt = get_post_type_object( get_query_var( 'post_type' ) );
            if ( $pt ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos,
                    'name'     => $pt->labels->name,
                    'item'     => get_post_type_archive_link( $pt->name ),
                ];
            }
        }

        if ( count( $items ) < 2 ) {
            return null;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Build FAQPage schema from post meta.
     *
     * @return array|null
     */
    private function faq() {
        if ( ! is_singular() ) {
            return null;
        }

        $faq = get_post_meta( get_the_ID(), '_klaw_seo_faq', true );
        if ( ! is_array( $faq ) || empty( $faq ) ) {
            return null;
        }

        $entities = [];
        foreach ( $faq as $item ) {
            $q = $item['question'] ?? '';
            $a = $item['answer'] ?? '';
            if ( ! $q || ! $a ) {
                continue;
            }
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $a,
                ],
            ];
        }

        if ( empty( $entities ) ) {
            return null;
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }
}
