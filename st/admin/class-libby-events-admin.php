<?php

/**
 * The functionality for the events custom post type
 *
 * @link       http://stboston.com
 * @since      1.0.0
 *
 * @package    Libby_Events
 * @subpackage Libby_Events/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Libby_Events
 * @subpackage Libby_Events/admin
 * @author     Stirling Technologies <brian@stboston.com>
 */
class Libby_Events_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name . '-events-admin', plugin_dir_url( __FILE__ ) . 'js/libby-events-admin.js', array( 'jquery' ), $this->version, true );
	}

	/**
	 * Register the metaboxes for the venues
	 *
	 * @since 1.0.0
	 */
	public function register_vendor_metaboxes_and_fields() {
		$metabox = new_cmb2_box( array(
        'id'            => 'libby_event_metabox',
        'title'         => __( 'Event Details', 'libby' ),
        'object_types'  => array( 'event' ), // Post type
        'context'       => 'normal',
        'priority'      => 'default',
        'show_names'    => true, // Show field names on the left
    ) );

		// Regular text field
    $metabox->add_field( array(
        'name'       => __( 'Contact First Name', 'libby' ),
        'id'         => '_eventorganiser_fes_fname',
        'type'       => 'text',
    ) );

		$metabox->add_field( array(
        'name'       => __( 'Contact Last Name', 'libby' ),
        'id'         => '_eventorganiser_fes_lname',
        'type'       => 'text',
    ) );

		// Regular text field
    $metabox->add_field( array(
        'name'       => __( 'Contact Email', 'libby' ),
        'id'         => '_eventorganiser_fes_email',
        'type'       => 'text_email',
    ) );

		// Regular text field
		$metabox->add_field( array(
				'name'       => __( 'Contact Phone', 'libby' ),
				'id'         => 'libby-contact-phone',
				'type'       => 'text',
		) );

		// Setup Option
    $metabox->add_field( array(
        'name'       => __( 'Setup', 'libby' ),
        'id'         => '_libby_setup_options',
        'type'       => 'radio',
				'options_cb' => array( $this, 'get_venue_setup_options_array' ),
				'before' => array( $this, 'setup_equipment_options_disclaimer' )
    ) );

		// Setup Option
    $metabox->add_field( array(
        'name'       => __( 'Equipment', 'libby' ),
        'id'         => '_libby_equipment',
        'type'       => 'multicheck',
				'options_cb' => array( $this, 'get_venue_equipment_options_array' ),
				'before' => array( $this, 'setup_equipment_options_disclaimer' )
    ) );

		$event_metabox_extra_fields = array(
			'setup_time_required' => array(
				'name'       => __( 'Setup Time Required', 'libby' ),
				'id'         => '_libby_setup_time',
				'type'       => 'text',
				// 'show_on_cb' => array( $this, 'is_submitted_event' )
			),
			'breakdown_time_required' => array(
				'name'       => __( 'Breakdown Time Required', 'libby' ),
				'id'         => '_libby_breakdown_time',
				'type'       => 'text',
				// 'show_on_cb' => array( $this, 'is_submitted_event' )
			),
			'meeting_purpose' => array(
					'name'       => __( 'Meeting Purpose', 'libby' ),
					'id'         => '_libby_meeting_purpose',
					'type'       => 'textarea',
					'attributes'  => array(
						// 'readonly' => 'readonly',
						'rows' => 4
					),
			),
			'expected_attendance' => array(
					'name'       => __( 'Expected Attendance', 'libby' ),
					'id'         => '_libby_expected_attendance',
					'type'       => 'text',
					'show_on_cb' => array( $this, 'is_submitted_event' )
			),
			'private_note' => array(
	        'name'       => __( 'Private Note', 'libby' ),
	        'id'         => '_libby_private_note',
	        'type'       => 'textarea',
					'attributes'  => array(
						'readonly' => 'readonly',
						'rows' => 4
					),
					'show_on_cb' => array( $this, 'is_submitted_event' )
	    ),
			'event_link' => array(
					'name'       => __( 'Event Link', 'libby' ),
					'id'         => '_libby_link',
					'type'       => 'text',
			),
			'fee' => array(
					'name'       => __( 'Fee', 'libby' ),
					'id'         => '_libby_fee',
					'type'       => 'text',
					'attributes'  => array(
						'readonly' => 'readonly',
					),
					'show_on_cb' => array( $this, 'is_submitted_event' )
			)
		);

		/**
		 * Apply filters to add/modify event extra fields. Returning false will not register any additional fields.
		 * @var [type]
		 */
		$event_metabox_extra_fields = apply_filters( 'libby/events/event-meta-fields', $event_metabox_extra_fields );

		if ( $event_metabox_extra_fields && is_array( $event_metabox_extra_fields) ) {
			foreach ( $event_metabox_extra_fields as $key => $event_metabox_field ) {
				$metabox->add_field( $event_metabox_field );
			}
		}

	}

	public function setup_equipment_options_disclaimer( $field_args, $field ) {
		if ( ! $this->is_venue_set() ) {
				printf( '%s options will appear here after selecting a venue and saving or publishing the event.', $field_args['name'] );
		}
		else if (
			( $field_args['name'] === 'Equipment' && ! $this->venue_has_equipment() ) ||
			( $field_args['name'] === 'Setup' && ! $this->venue_has_setup_options() ) ) {
			printf( 'There are no %s options configured for the selected venue.', $field_args['name'] );
		}
	}

	/**
	 * Determine if the event was submitted
	 * @return boolean
	 */
	public function is_submitted_event() {
			global $post;
			return get_post_meta( $post->ID, '_eventorganiser_fes' );
	}

	/**
	 * Determine if the event venue has setup options to choose from
	 * @return boolean
	 */
	public function venue_has_setup_options() {
		global $post;
		$venue_id = eo_get_venue( $post->ID );
		return eo_get_venue_meta( $venue_id, '_libby_setup_options', true ) ? true : false;
	}

	/**
	 * Check if the venue has been set for the event
	 */
	public function is_venue_set() {
		global $post;
		return eo_get_venue( $post->ID ) ? true : false;
	}

	/**
	 * Determine if the event venue has equipment to choose from
	 * @return boolean
	 */
	public function venue_has_equipment() {
		global $post;
		$venue_id = eo_get_venue( $post->ID );
		return eo_get_venue_meta( $venue_id, '_libby_available_equipment', true ) ? true : false;
	}

	/**
	 * Create an options array of available setup options for CMB2 metaboxes
	 * @return array An array of equipment keyed by the name of the setup option
	 */
	public function get_venue_setup_options_array() {
		global $post;
		$venue_id = eo_get_venue( $post->ID );
		$venue_setup_options = eo_get_venue_meta( $venue_id, '_libby_setup_options', true );
		$rtn = [];
		foreach ( $venue_setup_options as $option ) {
			$rtn[$option['title']] = $option['title'];
		}
		return $rtn;
	}

	/**
	 * Create an options array of available venue equipment for CMB2 metaboxes
	 * @return array An array of equipment keyed by the name of the equipment
	 */
	public function get_venue_equipment_options_array() {
		global $post;
		$venue_id = eo_get_venue( $post->ID );
		$venue_equipment = eo_get_venue_meta( $venue_id, '_libby_available_equipment', true );
		$rtn = [];
		foreach ( $venue_equipment as $equipment ) {
			$rtn[$equipment['title']] = $equipment['title'];
		}
		return $rtn;
	}

	/**
	 * Register the custom columns for the events admin
	 * @return array $columns Registered columns
	 */
	public function register_custom_columns( $columns ) {
		// $columns['status'] = 'Status';
		unset($columns['taxonomy-event-venue']);
		$columns = apply_filters( 'libby/events/event-columns', $columns );
		return $columns;
	}

	/**
	 * Render the custom column registered in register_custom_columns
	 * @param  string $column  The name of the column
	 * @param  int $post_id The ID of the current post row
	 */
	public function render_custom_columns( $column, $post_id ) {
		switch( $column ) {
			case 'status' :
				echo ucwords( get_post_status( $post_id ) );
				break;
		}
	}

	/**
	 * Hook into the pending_to_publish action to send a confirmation email for
	 * submitted events when they are published
	 * @param  obj $post The WP_Post object of the event
	 */
	public function send_event_published_email( $post ) {
		// Make sure we have the correct post type, as this will fire for all post types.
		if ( get_post_type( $post ) !== 'event' ) {
			return;
		}

		// Make sure it was a front-end submitted event
		if ( ! get_post_meta( $post->ID, '_eventorganiser_fes' ) ) {
			return;
		}

		$eo_fes_data = get_post_meta( $post->ID, '_eventorganiser_fes_data', true );
		$to = $eo_fes_data['email'];

		$subject = sprintf( 'Your request for %s has been approved.', $post->post_title );
		$body = sprintf(
			'Dear %1$s, <br /><br /> The event administrators have approved your room request for %2$s. You can see it on the website at the following URL: <a href="%3$s" target="_blank">%3$s</a>. If you owe any fees associated with the event, please make sure to bring or mail a check to the library prior to the start of your event.',
			implode( ' ', $eo_fes_data['name'] ),
			$post->post_title,
			get_the_permalink( $post )
		);
		$booking_form_from_email = apply_filters( 'libby/events/form/admin-email', get_option( 'admin_email' ) );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', get_bloginfo( 'name' ),  $booking_form_from_email )
		);

		wp_mail( $to, $subject, $body, $headers );

	}

	/**
	 * Remove the submenu page for the EO plugins if debug is off
	 */
	public function remove_menu_pages() {
		if ( ! defined( 'WP_DEBUG') || ! WP_DEBUG ) {
			remove_submenu_page( 'options-general.php', 'event-settings' );
			remove_submenu_page( 'edit.php?post_type=event', 'eo-addons' );
		}
	}

	/**
	 * Ensure that EO doesn't add any notices that we don't want to appear
	 *
	 * We keep them on if we are in debug mode
	 */
	public function filter_admin_notices() {
		if ( ! defined( 'WP_DEBUG') || ! WP_DEBUG ) {
			echo '<style>#eo-notice{display:none;}</style>';
		}
	}

	/**
	 * Modify the custom post type registration args
	 * @param  array $args The predefined arguments
	 * @return array The modified arguments
	 */
	public function modify_event_cpt_args( $args ) {
		$args['supports'][] = 'publicize';
		$args['taxonomies'][] = 'group-type';
		return $args;
	}

}