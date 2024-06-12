<?php
/*
Plugin Name: STF Custom Foo Event CSV
Description: Download Foo Event Attendies CSV - Shortcode: [events-list]
Version: 1.0
*/


// Shortcode
add_shortcode('events-list', 'display_database_query');


// Function to add a menu item in the admin side menu
function display_database_query() {
    ob_start();
    ?>
    <div class="wrap">
        
        <?php
        // LIST ALL EVENTS
        global $wpdb; 
        $post_table = $wpdb->prefix . 'posts'; 
        $post_meta_table = $wpdb->prefix . 'postmeta'; 

		// FILTERS
		$filteryByTenses = $_GET['event-tenses'];
		$filteryByName = $_GET['event-name'];

		// EVENT TENSES Filter
		$byTenses = '';
		if($filteryByTenses){

			$currentDate = date('Y-m-d');
			
			// Present & Past Event
			if($filteryByTenses == 'past'){
				$byTenses = "AND pm.meta_value <='$currentDate'";
			}

			// Future Event
			if($filteryByTenses == 'upcoming'){
				$byTenses = "AND pm.meta_value >='$currentDate'";
			}
		}

		// EVENT NAME Filter
		$byEventName = '';
		if($filteryByName){
			$byEventName = "AND LOWER(p.post_title) LIKE '%$filteryByName%'";
		}



$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT
		pm.post_id,
		p.ID,
		pm.meta_id,
		pm.meta_value,
		p.post_title,
		(
		SELECT
			SUM(
				IF(
					pmv.meta_key = '_stock',
					pmv.meta_value,
					NULL
				)
			)
		FROM
			{$wpdb->posts} pv
		JOIN {$wpdb->postmeta} pmv ON
			pv.ID = pmv.post_id
		WHERE
			pv.post_parent = p.ID
		) AS variations_stock,
		(
			SELECT
				COUNT(*)
			FROM
				{$wpdb->posts}
			JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			WHERE
				{$wpdb->postmeta}.meta_key = 'WooCommerceEventsProductID' AND {$wpdb->postmeta}.meta_value = p.ID AND {$wpdb->posts}.post_status = 'publish'
		) AS 'sales',
		(
			SELECT
				meta_value
			FROM
				{$wpdb->postmeta}
			WHERE
				post_id = p.ID AND meta_key = '_stock'
		) AS 'stock'
	FROM
		{$wpdb->posts} p
	INNER JOIN {$wpdb->postmeta} pm ON
		p.ID = pm.post_id
	WHERE
		p.post_type = 'product' $byTenses AND p.post_status = 'publish' AND pm.meta_key = 'WooCommerceEventsDate' AND pm.meta_value <> '' $byEventName
	GROUP BY
		p.post_title
	ORDER BY
		pm.meta_value
	DESC
	",
        array()
    )
);

        if ($results) {
            echo '<div class="event-filters"><h3>Events:</h3>';
			echo '
			<form method="get" style="display: flex;flex-direction: row;gap: 40px;margin: 0em 0 3em 0;">
			<select name="event-tenses" value="'.$filteryByTenses.'">
				<option value="all">All</option>
				<option value="past">Past</option>
				<option value="upcoming">Upcoming</option>
			</select>
			<input type="text" name="event-name" placeholder="Search event name." value="'.$filteryByName.'"/>
			<input type="submit" value="Filter" style="border: none;">
			</form>
			
			</div>';
            echo '<table>';
            echo '<tr><td><b>Event Date<b></td><td><b>Event</b></td> <td><b>Tickets Sold</b></td> <td><b>Total Ticket Capacity</b></td> <td><b>Action</b></td></tr>';

            foreach ($results as $row) {
				$total_tickets = $row->stock + $row->sales;
                if(!$available_tickets){
                    $available_tickets = 0;
                } 

				$stocks = $total_tickets;
				$variations_stock = $row->variations_stock;
				if($variations_stock){
					$stocks = $variations_stock + $row->sales;
				}
                echo '<tr>';
                echo '<td>' . $row->meta_value. '</span></td>';
                echo '<td><a href="https://littlescientists.org.au/wp-admin/edit.php?post_type=event_magic_tickets&event_id='.$row->post_id.'" target="_blank">' . esc_html($row->post_title) . '</span></a></td>';
                echo '<td>' . esc_html($row->sales) . '</span></td>';
                echo '<td>' . esc_html($stocks) . '</span></td>';
                echo '<td> <a href="https://littlescientists.org.au/wp-admin/admin-ajax.php?action=custom_events_csv&event='.$row->ID.'"> Download CSV </td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo 'No results found';
        }

        return ob_get_clean();
        ?>
    </div>
    <?php
}

use Automattic\WooCommerce\Utilities\OrderUtil;

 /**
	 * Generates attendee CSV export.
	 */
	function custom_events_csv() {

		/*if ( ! current_user_can( 'publish_event_magic_tickets' ) ) {

			echo 'User role does not have permission to export attendee details. Please contact site admin.';
			exit();

		}*/

       

		global $woocommerce;

		$event = '';
		if ( isset( $_GET['event'] ) ) {

			$event = sanitize_text_field( wp_unslash( $_GET['event'] ) );

		}

		$include_unpaid_tickets = '';
		if ( isset( $_GET['exportunpaidtickets'] ) ) {

			$include_unpaid_tickets = sanitize_text_field( wp_unslash( $_GET['exportunpaidtickets'] ) );

		}

		$exportbillingdetails = '';
		if ( isset( $_GET['exportbillingdetails'] ) ) {

			$exportbillingdetails = sanitize_text_field( wp_unslash( $_GET['exportbillingdetails'] ) );

		}

		
		$ticket_id_label            = __( 'TicketID', 'woocommerce-events' );
		$ticket_id_numeric_label    = __( 'TicketID Numeric', 'woocommerce-events' );
		$order_id_label             = __( 'OrderID', 'woocommerce-events' );
		$attendee_first_name_label  = __( 'First Name', 'woocommerce-events' );
		// $attendee_first_name_label  = __( 'Attendee First Name', 'woocommerce-events' );
		$attendee_last_name_label   = __( 'Surname', 'woocommerce-events' );
		// $attendee_last_name_label   = __( 'Attendee Last Name', 'woocommerce-events' );
		// $attendee_email_label       = __( 'Attendee Email', 'woocommerce-events' );
		$attendee_email_label       = __( 'Email', 'woocommerce-events' );
		$ticket_status_label        = __( 'Ticket Status', 'woocommerce-events' );
		$ticket_type_label          = __( 'Ticket Type', 'woocommerce-events' );
		// $ticket_variation_label     = __( 'Variation', 'woocommerce-events' );
		$ticket_variation_label     = __( 'Ticket Type', 'woocommerce-events' );
		$attendee_telephone_label   = __( 'Mobile', 'woocommerce-events' );
		// $attendee_telephone_label   = __( 'Attendee Telephone', 'woocommerce-events' );
		$attendee_company_label     = __( 'Attendee Company', 'woocommerce-events' );
		$attendee_company_label     = __( 'Org Name', 'woocommerce-events' );
		$attendee_designation_label = __( 'Attendee Designation', 'woocommerce-events' );
		$purchaser_first_name_label = __( 'Purchaser First Name', 'woocommerce-events' );
		$purchaser_last_name_label  = __( 'Purchaser Last Name', 'woocommerce-events' );
		$purchaser_email_label      = __( 'Purchaser Email', 'woocommerce-events' );
		$purchaser_phone_label      = __( 'Purchaser Phone', 'woocommerce-events' );
		$purchaser_company_label    = __( 'Purchaser Company', 'woocommerce-events' );
		$customer_note_label        = __( 'Customer Note', 'woocommerce-events' );
		$order_status_label         = __( 'Checked in status', 'woocommerce-events' );
		$payment_method_label       = __( 'Payment Method', 'woocommerce-events' );
		$order_date_label           = __( 'Date', 'woocommerce-events' );
		// $order_date_label           = __( 'Order Date', 'woocommerce-events' );

		$billing_address_1_label   = __( 'Purchaser Street', 'woocommerce-events' );
		// $billing_address_1_label   = __( 'Billing Address 1', 'woocommerce-events' );
		$billing_address_2_label   = __( 'Purchaser Address 2', 'woocommerce-events' );
		$billing_city_label        = __( 'Purchaser City', 'woocommerce-events' );
		$billing_postal_code_label = __( 'Purchaser Postal Code', 'woocommerce-events' );
		$billing_country_label     = __( 'Purchaser Suburb', 'woocommerce-events' );
		// $billing_country_label     = __( 'Billing Country', 'woocommerce-events' );
		$billing_state_label       = __( 'Purchaser State', 'woocommerce-events' );
		// $billing_state_label       = __( 'Billing State', 'woocommerce-events' );
		$billing_phone_label       = __( 'Billing Phone', 'woocommerce-events' );


		$order_total      = __( 'Amount Paid', 'woocommerce-events' );
		$landline      = __( 'Landline', 'woocommerce-events' );
		$job_title      = __( 'Job Title', 'woocommerce-events' );
		$teachers_reg      = __( 'Teacher Reg #', 'woocommerce-events' );
		$alt_email      = __( 'Alt email', 'woocommerce-events' );
		$lnp      = __( 'LNP', 'woocommerce-events' );
		$ws_name      = __( 'WS Name', 'woocommerce-events' );
		$days_attended      = __( 'Days Attended', 'woocommerce-events' );
		$postal_code  = __( 'Postalcode', 'woocommerce-events' );
		$notes  = __( 'Notes', 'woocommerce-events' );
		$terms  = __( 'I have read and agree to the above terms and conditions.', 'woocommerce-events' );
		$coupons = __('Coupons','woocommerce-events');
		$compulsory = __('Are you a Little Scientists House?','woocommerce-events');

        $csv_blueprint = array(
			$attendee_email_label,
			$attendee_first_name_label,
			$attendee_last_name_label,
            $job_title,
            $teachers_reg,
			$attendee_company_label,
            $postal_code,
			$compulsory,
			$attendee_telephone_label,
            $landline,
            $alt_email,
			$purchaser_first_name_label,
			$purchaser_last_name_label,
			$purchaser_email_label,
			$purchaser_phone_label,
			$purchaser_company_label,
			$billing_address_1_label,
            $billing_country_label,
            $billing_state_label,
            $lnp,
            $ws_name,
            $order_date_label,
            $days_attended,
			$ticket_variation_label,
            $notes,
			$order_status_label,
			$order_total,
			$coupons,
		);

		


		// $csv_blueprint = array(
		// 	$ticket_id_label,
		// 	$ticket_id_numeric_label,
		// 	$order_id_label,
		// 	$attendee_first_name_label,
		// 	$attendee_last_name_label,
		// 	$attendee_email_label,
		// 	$ticket_status_label,
		// 	$ticket_type_label,
		// 	$ticket_variation_label,
		// 	$attendee_telephone_label,
		// 	$attendee_company_label,
		// 	$attendee_designation_label,
		// 	$purchaser_first_name_label,
		// 	$purchaser_last_name_label,
		// 	$purchaser_email_label,
		// 	$purchaser_phone_label,
		// 	$purchaser_company_label,
		// 	$customer_note_label,
		// 	$order_status_label,
		// 	$payment_method_label,
		// 	$order_date_label,
		// );
	

		$sorted_rows   = array();

		// Database
		global $wpdb; 

        $post_table = $wpdb->prefix . 'posts'; 
        
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT post_title FROM $post_table  WHERE ID=$event")
        );
	

		$ws_name_value = '';
		if ($results) {
			foreach ($results as $row) {
				$ws_name_value = esc_html($row->post_title);
			}
		}

		$events_query = new WP_Query(
			array(
				'post_type'      => array( 'event_magic_tickets' ),
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => 'WooCommerceEventsProductID',
						'value' => $event,
					),
				),
			)
		);

		$events = $events_query->get_posts();
		$x = 0;

		foreach ( $events as $event_item ) {

			$id        = $event_item->ID;
			$ticket    = get_post( $id );
			$ticket_id = $ticket->post_title;

			$order_id                  = get_post_meta( $id, 'WooCommerceEventsOrderID', true );
			$ticket_id_numeric         = get_post_meta( $id, 'WooCommerceEventsTicketID', true );
			$product_id                = get_post_meta( $id, 'WooCommerceEventsProductID', true );
			$customer_id               = get_post_meta( $id, 'WooCommerceEventsCustomerID', true );
			$woocommerce_events_status = get_post_meta( $id, 'WooCommerceEventsStatus', true );
			$ticket_type               = get_post_meta( $ticket->ID, 'WooCommerceEventsTicketType', true );

			
			
			


			$woocommerce_events_variations = get_post_meta( $id, 'WooCommerceEventsVariations', true );
			if ( ! empty( $woocommerce_events_variations ) && ! is_array( $woocommerce_events_variations ) ) {

				$woocommerce_events_variations = json_decode( $woocommerce_events_variations );

			}

        

			$variation_output = '';
			$i                = 0;
			if ( ! empty( $woocommerce_events_variations ) ) {
				foreach ( $woocommerce_events_variations as $variation_name => $variation_value ) {

					if ( $i > 0 ) {

						$variation_output .= ' | ';

					}

					$variation_name_output = str_replace( 'attribute_', '', $variation_name );
					$variation_name_output = str_replace( 'pa_', '', $variation_name_output );
					$variation_name_output = str_replace( '_', ' ', $variation_name_output );
					$variation_name_output = str_replace( '-', ' ', $variation_name_output );
					$variation_name_output = str_replace( '', ' ', $variation_name_output );
					$variation_name_output = str_replace( 'Pa_', '', $variation_name_output );
					$variation_name_output = ucwords( $variation_name_output );

					$variation_value_output = str_replace( '_', ' ', $variation_value );
					$variation_value_output = str_replace( '-', ' ', $variation_value_output );

					$variation_value_output = str_replace( ',', '', $variation_value_output );

					$variation_value_output = ucwords( $variation_value_output );

					// $variation_output .= $variation_name_output . ': ' . $variation_value_output;
					$variation_output .= $variation_value_output;

					$i++;
				}
			}

			$order = wc_get_order( $order_id );

           

			$woocommerce_events_attendee_name = get_post_meta( $ticket->ID, 'WooCommerceEventsAttendeeName', true );
			if ( empty( $woocommerce_events_attendee_name ) ) {

				if ( false !== $order ) {

					$woocommerce_events_attendee_name = $order->get_billing_first_name();

				} else {

					$woocommerce_events_attendee_name = '';

				}
			}

			$woocommerce_events_attendee_last_name = get_post_meta( $ticket->ID, 'WooCommerceEventsAttendeeLastName', true );
			if ( empty( $woocommerce_events_attendee_last_name ) ) {

				if ( false !== $order ) {

					$woocommerce_events_attendee_last_name = $order->get_billing_last_name();

				} else {

					$woocommerce_events_attendee_last_name = '';

				}
			}

			$woocommerce_events_attendee_email = get_post_meta( $ticket->ID, 'WooCommerceEventsAttendeeEmail', true );
			if ( empty( $woocommerce_events_attendee_email ) ) {

				if ( false !== $order ) {

					$woocommerce_events_attendee_email = $order->get_billing_email();

				} else {

					$woocommerce_events_attendee_email = '';

				}
			}

			$woocommerce_events_purchaser_phone = get_post_meta( $ticket->ID, 'WooCommerceEventsPurchaserPhone', true );
			if ( empty( $woocommerce_events_purchaser_phone ) ) {

				if ( false !== $order ) {

					$woocommerce_events_purchaser_phone = $order->get_billing_phone();

				} else {

					$woocommerce_events_purchaser_phone = '';

				}
			}

			$order_status = '';
			if ( false !== $order ) {

				$order_status = $order->get_status();

			} else {

				$order_status = '';

			}

			$woocommerce_events_capture_attendee_telephone   = get_post_meta( $ticket->ID, 'WooCommerceEventsAttendeeTelephone', true );
			$woocommerce_events_capture_attendee_company     = get_post_meta( $ticket->ID, 'WooCommerceEventsAttendeeCompany', true );
			$woocommerce_events_capture_attendee_designation = get_post_meta( $ticket->ID, 'WooCommerceEventsAttendeeDesignation', true );
			$woocommerce_events_purchaser_first_name         = get_post_meta( $ticket->ID, 'WooCommerceEventsPurchaserFirstName', true );
			$woocommerce_events_purchaser_last_name          = get_post_meta( $ticket->ID, 'WooCommerceEventsPurchaserLastName', true );
			$woocommerce_events_purchaser_email              = get_post_meta( $ticket->ID, 'WooCommerceEventsPurchaserEmail', true );
			$woocommerce_events_compulsory              	 = get_post_meta( $ticket->ID, 'fooevents_custom_xjfhvkckwgtrkjnsxfbt', true );
			
			
			
			$order_coupons = '';
			if ( false !== $order ) {
				$coupons_item = $order->get_used_coupons();
				$order_coupons = $coupons_item[0];

			}
			

			$customer_note = '';
			if ( false !== $order ) {

				$customer_note = $order->get_customer_note();

			}

			$payment_method = '';
			if ( false !== $order ) {

				$payment_method = $order->get_payment_method();

			}

			$order_total_amount = 0;
			if ( false !== $order ) {

				$order_total_amount = $order->get_total();

			}

			$order_date = '';
			if ( false !== $order ) {

				$order_date = $order->get_date_created();

			}

			$sorted_rows[ $x ][ $ticket_id_label ]            = $ticket_id;
			$sorted_rows[ $x ][ $ticket_id_numeric_label ]    = $ticket_id_numeric;
			$sorted_rows[ $x ][ $order_id_label ]             = $order_id;
			$sorted_rows[ $x ][ $attendee_first_name_label ]  = $woocommerce_events_attendee_name;
			$sorted_rows[ $x ][ $order_status ]  			  = $woocommerce_events_status;
			$sorted_rows[ $x ][ $attendee_last_name_label ]   = $woocommerce_events_attendee_last_name;
			$sorted_rows[ $x ][ $attendee_email_label ]       = $woocommerce_events_attendee_email;
			$sorted_rows[ $x ][ $ticket_status_label ]        = $woocommerce_events_status;
			$sorted_rows[ $x ][ $ticket_type_label ]          = $ticket_type;
			$sorted_rows[ $x ][ $ticket_variation_label ]     = $variation_output;
			$sorted_rows[ $x ][ $attendee_telephone_label ]   = $woocommerce_events_capture_attendee_telephone;
			$sorted_rows[ $x ][ $attendee_company_label ]     = $woocommerce_events_capture_attendee_company;
			$sorted_rows[ $x ][ $attendee_designation_label ] = $woocommerce_events_capture_attendee_designation;
			$sorted_rows[ $x ][ $purchaser_first_name_label ] = $woocommerce_events_purchaser_first_name;
			$sorted_rows[ $x ][ $purchaser_last_name_label ]  = $woocommerce_events_purchaser_last_name;
			$sorted_rows[ $x ][ $purchaser_email_label ]      = $woocommerce_events_purchaser_email;
			$sorted_rows[ $x ][ $purchaser_phone_label ]      = $woocommerce_events_purchaser_phone;
			$sorted_rows[ $x ][ $customer_note_label ]        = $customer_note;
			$sorted_rows[ $x ][ $order_status_label ]         = ucfirst( $woocommerce_events_status );
			$sorted_rows[ $x ][ $payment_method_label ]       = $payment_method;
			$sorted_rows[ $x ][ $order_date_label ]           = $order_date;
			$sorted_rows[ $x ][ $order_total ]				  = $order_total_amount;
			$sorted_rows[ $x ][ $ws_name ] = $ws_name_value;
			$sorted_rows[ $x ][ $coupons ] = $order_coupons;
			$sorted_rows[ $x ][ $compulsory ] = $woocommerce_events_compulsory;


			if ( false !== $order ) {

				$sorted_rows[ $x ][ $purchaser_company_label ] = $order->get_billing_company();

			} else {

				$sorted_rows[ $x ][ $purchaser_company_label ] = '';

			}

			if ( ! empty( $exportbillingdetails ) ) {

				if ( false !== $order ) {

					$billing_address_1 = $order->get_billing_address_1();

					$billing_fields   = array(
						$billing_address_1_label   => '',
						$billing_address_2_label   => '',
						$billing_city_label        => '',
						$billing_postal_code_label => '',
						$billing_country_label     => '',
						$billing_state_label       => '',
						$billing_phone_label       => '',
					);
					$billing_headings = array_keys( $billing_fields );

					foreach ( $billing_headings as $value ) {

						if ( ! in_array( $value, $csv_blueprint, true ) ) {

							$csv_blueprint[] = $value;

						}
					}

					$sorted_rows[ $x ][ $billing_address_1_label ]   = $order->get_billing_address_1();
					$sorted_rows[ $x ][ $billing_address_2_label ]   = $order->get_billing_address_2();
					$sorted_rows[ $x ][ $billing_city_label ]        = $order->get_billing_city();
					$sorted_rows[ $x ][ $billing_postal_code_label ] = $order->get_billing_postcode();
					$sorted_rows[ $x ][ $billing_country_label ]     = $order->get_billing_country();
					$sorted_rows[ $x ][ $billing_state_label ]       = $order->get_billing_state();
					$sorted_rows[ $x ][ $billing_phone_label ]       = $order->get_billing_phone();

				}
			}

			if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'is_plugin_active_for_network' ) ) {

				require_once ABSPATH . '/wp-admin/includes/plugin.php';

			}

           

			if ( is_plugin_active( 'fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php' ) || is_plugin_active_for_network( 'fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php' ) ) {

				$fooevents_custom_attendee_fields         = new Fooevents_Custom_Attendee_Fields();
				$fooevents_custom_attendee_fields_options = $fooevents_custom_attendee_fields->display_tickets_meta_custom_options_array_csv( $id, $product_id );

				$fooevents_custom_attendee_fields_headings = array_keys( $fooevents_custom_attendee_fields_options );

				// foreach ( $fooevents_custom_attendee_fields_headings as $value ) {

				// 	if ( ! in_array( $value, $csv_blueprint, true ) ) {

				// 		$csv_blueprint[] = $value;

				// 	}
				// }
				foreach ( $fooevents_custom_attendee_fields_options as $key => $value ) {
                    if($key == 'Postcode'){
					    $sorted_rows[ $x ][ $postal_code ] = $value;
                    }

                    if($key == 'Job Title'){
					    $sorted_rows[ $x ][ $job_title ] = $value;
                    }

					if($key == 'Suburb'){
					    $sorted_rows[ $x ][ $billing_country_label] = $value;
                    }

					if($key == 'State'){
					    $sorted_rows[ $x ][ $billing_state_label] = $value;
                    }

					if($key == 'Street'){
					    $sorted_rows[ $x ][ $billing_address_1_label] = $value;
                    }

                    if($key == 'Teacher Registration Number'){
					    $sorted_rows[ $x ][ $teachers_reg ] = $value;
                    }
				}
			}

			if ( is_plugin_active( 'fooevents_bookings/fooevents-bookings.php' ) || is_plugin_active_for_network( 'fooevents_bookings/fooevents-bookings.php' ) ) {

				$fooevents_bookings         = new FooEvents_Bookings();
				$fooevents_bookings_options = $fooevents_bookings->display_bookings_options_array( $id, $product_id );

				$fooevents_bookings_options_headings = array_keys( $fooevents_bookings_options );

				foreach ( $fooevents_bookings_options_headings as $value ) {

					if ( ! in_array( $value, $csv_blueprint, true ) ) {

						$csv_blueprint[] = $value;

					}
				}

				foreach ( $fooevents_bookings_options as $key => $value ) {

					$sorted_rows[ $x ][ $key ] = $value;

				}
			}

			if ( is_plugin_active( 'fooevents_seating/fooevents-seating.php' ) || is_plugin_active_for_network( 'fooevents_seating/fooevents-seating.php' ) ) {

				$fooevents_seating         = new Fooevents_Seating();
				$fooevents_seating_options = $fooevents_seating->display_tickets_meta_seat_options_array( $id );

				$fooevents_seating_headings = array_keys( $fooevents_seating_options );

				foreach ( $fooevents_seating_headings as $value ) {

					if ( ! in_array( $value, $csv_blueprint, true ) ) {

						$csv_blueprint[] = $value;

					}
				}

				foreach ( $fooevents_seating_options as $key => $value ) {

					$sorted_rows[ $x ][ $key ] = $value;

				}
			}

			if ( is_plugin_active( 'fooevents_multi_day/fooevents-multi-day.php' ) || is_plugin_active_for_network( 'fooevents_multi_day/fooevents-multi-day.php' ) ) {

				$woocommerce_events_num_days = get_post_meta( $product_id, 'WooCommerceEventsNumDays', true );

				$fooevents_multiday_events   = new Fooevents_Multiday_Events();
				$fooevents_multiday_statuses = $fooevents_multiday_events->get_array_of_check_ins( $id, $woocommerce_events_num_days );

				$fooevents_multiday_statuses_headings = array_keys( $fooevents_multiday_statuses );

				foreach ( $fooevents_multiday_statuses_headings as $value ) {

					if ( ! in_array( $value, $csv_blueprint, true ) ) {

						$csv_blueprint[] = $value;

					}
				}

				foreach ( $fooevents_multiday_statuses as $key => $value ) {

					$sorted_rows[ $x ][ $key ] = $value;

				}
			}

            

			// $checkins = $this->csv_get_checkins( $event, $ticket->ID );

			// if ( ! empty( $checkins ) ) {

			// 	$checkins_headings = array_keys( $checkins );

			// 	foreach ( $checkins_headings as $value ) {

			// 		if ( ! in_array( $value, $csv_blueprint, true ) ) {

			// 			$csv_blueprint[] = $value;

			// 		}
			// 	}

			// 	foreach ( $checkins as $key => $value ) {

			// 		$sorted_rows[ $x ][ $key ] = $value;

			// 	}
			// }

			$x++;

		}

		$output = array();

		$y = 0;
		foreach ( $sorted_rows as $item ) {

			foreach ( $item as $key => $valuetest ) {

				foreach ( $csv_blueprint as $heading ) {

					if ( $key === $heading ) {

						$output[ $y ][ $heading ] = $valuetest;

					}
				}

				foreach ( $csv_blueprint as $heading ) {

					if ( empty( $output[ $y ][ $heading ] ) ) {

						$output[ $y ][ $heading ] = '';

					}
				}
			}

			$y++;

		}


		header( 'Content-Encoding: UTF-8' );
		header( 'Content-type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . date( 'Ymdhis' ) . '.csv"' );
		echo "\xEF\xBB\xBF";

		$fp = fopen( 'php://output', 'w' );

		if ( empty( $output ) ) {

			$output[] = array( __( 'No tickets found.', 'woocommerce-events' ) );

		} else {

			fputcsv( $fp, $csv_blueprint );

		}

		foreach ( $output as $fields ) {

			fputcsv( $fp, $fields );

		}

		exit();

	}

add_action( 'wp_ajax_custom_events_csv', 'custom_events_csv');
add_action( 'wp_ajax_nopriv_custom_events_csv', 'custom_events_csv');