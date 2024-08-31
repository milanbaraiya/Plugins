<?php
/*
Plugin Name: Dev Challenge Plugin
Description: Fetches data from a remote endpoint and displays it in an HTML table using a shortcode.
Version: 1.0
Author: Sonali Prajapati
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Enqueue JavaScript for AJAX requests
function dev_challenge_enqueue_scripts() {
    wp_enqueue_script( 'dev-challenge-script', plugin_dir_url( __FILE__ ) . 'js/dev-challenge.js', array( 'jquery' ), null, true );
    wp_localize_script( 'dev-challenge-script', 'devChallenge', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dev_challenge_nonce' ),
    ));
}
add_action( 'wp_enqueue_scripts', 'dev_challenge_enqueue_scripts' );

// Register AJAX action for logged-in and logged-out users
add_action( 'wp_ajax_dev_challenge_get_table', 'dev_challenge_get_table' );
add_action( 'wp_ajax_nopriv_dev_challenge_get_table', 'dev_challenge_get_table' );

// Fetch data, cache it for 1 hour, and return HTML table
function dev_challenge_get_table() {
    check_ajax_referer( 'dev_challenge_nonce', 'nonce' );

    // delete_transient('dev_challenge_table_data'); die;

    $cache_key = 'dev_challenge_table_data';
    $cached_data = get_transient( $cache_key );

    if ( $cached_data ) {
        wp_send_json_success( $cached_data );
    }

    $response = wp_remote_get( 'https://caseproof.s3.amazonaws.com/dev-challenge/table.json' );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'Failed to fetch data.' );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! $data || ! is_array( $data ) ) {
        wp_send_json_error( 'Invalid data format.' );
    }
    //echo "<pre>"; print_r($data['data']['rows']); die;
    // Generate HTML table
    $table_html = dev_challenge_generate_table( $data );

    // Cache the data for 1 hour
    set_transient( $cache_key, $table_html, HOUR_IN_SECONDS );

    wp_send_json_success( $table_html );
}

// Generate the HTML table with formatted dates
function dev_challenge_generate_table( $data ) {
    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );
    $timezone = wp_timezone();

    ob_start();
    ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $data['data']['rows'] as $row ) : ?>
                <tr>
                    <td><?php echo esc_html( $row['id'] ); ?></td>
                    <td><?php echo esc_html( $row['fname'] .' '. $row['lname'] ); ?></td>
                    <td><?php echo esc_html( $row['email']  ); ?></td>
                    <td><?php echo esc_html( wp_date( "$date_format $time_format", strtotime( $row['date'] ), $timezone ) ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

// Shortcode to display the table
function dev_challenge_shortcode() {
    return '<div id="dev-challenge-table"></div>';
}
add_shortcode( 'dev_challenge_table', 'dev_challenge_shortcode' );
