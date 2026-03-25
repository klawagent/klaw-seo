<?php
/**
 * Klaw SEO — Local Business Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name = Klaw_SEO_Settings::OPTION;

$biz_name    = $options['business_name'] ?? '';
$biz_type    = $options['business_type'] ?? 'LocalBusiness';
$biz_street  = $options['business_street'] ?? '';
$biz_city    = $options['business_city'] ?? '';
$biz_state   = $options['business_state'] ?? '';
$biz_zip     = $options['business_zip'] ?? '';
$biz_country = $options['business_country'] ?? '';
$biz_phone   = $options['business_phone'] ?? '';
$biz_email   = $options['business_email'] ?? '';
$biz_lat     = $options['business_lat'] ?? '';
$biz_lng     = $options['business_lng'] ?? '';
$biz_price   = $options['business_price_range'] ?? '';
$biz_gbp     = $options['business_gbp_url'] ?? '';
$biz_hours   = $options['business_hours'] ?? '';

$evt_pt    = $options['event_post_type'] ?? '';
$evt_date  = $options['event_date_field'] ?? '';
$evt_time  = $options['event_time_field'] ?? '';
$evt_venue = $options['event_venue_field'] ?? '';
$evt_desc  = $options['event_description_field'] ?? '';

$business_types = [
    'LocalBusiness'     => 'Local Business (Generic)',
    'Restaurant'        => 'Restaurant',
    'CafeOrCoffeeShop'  => 'Cafe / Coffee Shop',
    'BarOrPub'          => 'Bar / Pub',
    'Store'             => 'Store',
    'AutoDealer'        => 'Auto Dealer',
    'AutoRepair'        => 'Auto Repair',
    'Bakery'            => 'Bakery',
    'BeautySalon'       => 'Beauty Salon',
    'DayCare'           => 'Day Care',
    'Dentist'           => 'Dentist',
    'DryCleaningOrLaundry' => 'Dry Cleaning / Laundry',
    'Florist'           => 'Florist',
    'GasStation'        => 'Gas Station',
    'GolfCourse'        => 'Golf Course',
    'HealthClub'        => 'Health Club',
    'HotelOrMotel'      => 'Hotel / Motel',
    'InsuranceAgency'   => 'Insurance Agency',
    'LegalService'      => 'Legal Service',
    'MedicalClinic'     => 'Medical Clinic',
    'Pharmacy'          => 'Pharmacy',
    'RealEstateAgent'   => 'Real Estate Agent',
    'SportsActivityLocation' => 'Sports / Activity Location',
    'VeterinaryCare'    => 'Veterinary Care',
];
?>

<h2><?php esc_html_e( 'Local Business Schema', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-biz-name"><?php esc_html_e( 'Business Name', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-name" name="<?php echo esc_attr( $opt_name ); ?>[business_name]"
                   value="<?php echo esc_attr( $biz_name ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-type"><?php esc_html_e( 'Business Type', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <select id="klaw-biz-type" name="<?php echo esc_attr( $opt_name ); ?>[business_type]">
                <?php foreach ( $business_types as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $biz_type, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-street"><?php esc_html_e( 'Street Address', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-street" name="<?php echo esc_attr( $opt_name ); ?>[business_street]"
                   value="<?php echo esc_attr( $biz_street ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-city"><?php esc_html_e( 'City', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-city" name="<?php echo esc_attr( $opt_name ); ?>[business_city]"
                   value="<?php echo esc_attr( $biz_city ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-state"><?php esc_html_e( 'State / Province', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-state" name="<?php echo esc_attr( $opt_name ); ?>[business_state]"
                   value="<?php echo esc_attr( $biz_state ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-zip"><?php esc_html_e( 'ZIP / Postal Code', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-zip" name="<?php echo esc_attr( $opt_name ); ?>[business_zip]"
                   value="<?php echo esc_attr( $biz_zip ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-country"><?php esc_html_e( 'Country', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-country" name="<?php echo esc_attr( $opt_name ); ?>[business_country]"
                   value="<?php echo esc_attr( $biz_country ); ?>" class="regular-text"
                   placeholder="US" />
            <p class="description"><?php esc_html_e( 'Two-letter country code (e.g. US, CA, GB).', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-phone"><?php esc_html_e( 'Phone', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-phone" name="<?php echo esc_attr( $opt_name ); ?>[business_phone]"
                   value="<?php echo esc_attr( $biz_phone ); ?>" class="regular-text"
                   placeholder="+1-555-123-4567" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-email"><?php esc_html_e( 'Email', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="email" id="klaw-biz-email" name="<?php echo esc_attr( $opt_name ); ?>[business_email]"
                   value="<?php echo esc_attr( $biz_email ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-lat"><?php esc_html_e( 'Latitude', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-lat" name="<?php echo esc_attr( $opt_name ); ?>[business_lat]"
                   value="<?php echo esc_attr( $biz_lat ); ?>" class="small-text"
                   placeholder="40.7128" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-lng"><?php esc_html_e( 'Longitude', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-lng" name="<?php echo esc_attr( $opt_name ); ?>[business_lng]"
                   value="<?php echo esc_attr( $biz_lng ); ?>" class="small-text"
                   placeholder="-74.0060" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-price"><?php esc_html_e( 'Price Range', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-biz-price" name="<?php echo esc_attr( $opt_name ); ?>[business_price_range]"
                   value="<?php echo esc_attr( $biz_price ); ?>" class="small-text"
                   placeholder="$$" />
            <p class="description"><?php esc_html_e( 'e.g. $, $$, $$$, $$$$', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-gbp"><?php esc_html_e( 'Google Business Profile URL', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="url" id="klaw-biz-gbp" name="<?php echo esc_attr( $opt_name ); ?>[business_gbp_url]"
                   value="<?php echo esc_url( $biz_gbp ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-biz-hours"><?php esc_html_e( 'Operating Hours', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-biz-hours" name="<?php echo esc_attr( $opt_name ); ?>[business_hours]"
                      rows="5" class="large-text"><?php echo esc_textarea( $biz_hours ); ?></textarea>
            <p class="description">
                <?php esc_html_e( 'One line per day. Format: Day HH:MM-HH:MM (e.g. Monday 09:00-17:00). Use "Closed" for closed days.', 'klaw-seo' ); ?>
            </p>
        </td>
    </tr>
</table>

<hr />

<h2><?php esc_html_e( 'Event Schema Mapping', 'klaw-seo' ); ?></h2>
<p class="description">
    <?php esc_html_e( 'Map a custom post type and its custom fields to Event structured data. The field names should be the meta key names used by your theme or plugin.', 'klaw-seo' ); ?>
</p>

<?php
$post_types     = get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' );
$all_post_types = get_post_types( [ 'public' => true ], 'objects' );
unset( $all_post_types['attachment'] );
?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-evt-pt"><?php esc_html_e( 'Event Post Type', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <select id="klaw-evt-pt" name="<?php echo esc_attr( $opt_name ); ?>[event_post_type]">
                <option value=""><?php esc_html_e( '— None —', 'klaw-seo' ); ?></option>
                <?php foreach ( $all_post_types as $pt ) : ?>
                    <option value="<?php echo esc_attr( $pt->name ); ?>" <?php selected( $evt_pt, $pt->name ); ?>>
                        <?php echo esc_html( $pt->labels->singular_name . ' (' . $pt->name . ')' ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Select the post type that represents events on your site.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-evt-date"><?php esc_html_e( 'Date Meta Key', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-evt-date" name="<?php echo esc_attr( $opt_name ); ?>[event_date_field]"
                   value="<?php echo esc_attr( $evt_date ); ?>" class="regular-text"
                   placeholder="event_date" />
            <p class="description"><?php esc_html_e( 'The post meta key that stores the event start date (YYYY-MM-DD format preferred).', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-evt-time"><?php esc_html_e( 'Time Meta Key', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-evt-time" name="<?php echo esc_attr( $opt_name ); ?>[event_time_field]"
                   value="<?php echo esc_attr( $evt_time ); ?>" class="regular-text"
                   placeholder="event_time" />
            <p class="description"><?php esc_html_e( 'The post meta key for event start time (HH:MM format preferred). Optional.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-evt-venue"><?php esc_html_e( 'Venue Meta Key', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-evt-venue" name="<?php echo esc_attr( $opt_name ); ?>[event_venue_field]"
                   value="<?php echo esc_attr( $evt_venue ); ?>" class="regular-text"
                   placeholder="event_venue" />
            <p class="description"><?php esc_html_e( 'The post meta key for the venue/location name.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-evt-desc"><?php esc_html_e( 'Description Meta Key', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-evt-desc" name="<?php echo esc_attr( $opt_name ); ?>[event_description_field]"
                   value="<?php echo esc_attr( $evt_desc ); ?>" class="regular-text"
                   placeholder="event_description" />
            <p class="description"><?php esc_html_e( 'The post meta key for a short event description. Falls back to the post excerpt if left empty.', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>
