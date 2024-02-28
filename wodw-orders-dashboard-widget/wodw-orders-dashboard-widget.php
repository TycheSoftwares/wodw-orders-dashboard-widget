<?php
/*
Plugin Name: Woocommerce Orders Dashboard Widget
Plugin URI: https://www.tychesoftwares.com
Description: WooCommerce Orders Dashboard Widget to show recent orders on the dashboard.
Version: 1.0
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
Text Domain: wodw-order-widget
Domain Path: /languages/
License: GPLv2 or later
Requires PHP: 5.3+
WC requires at least: 3.0.0
WC tested up to: 8.9
*/


if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wc_Orders_Dashboard_Widget class
 */

if ( !class_exists( 'Wc_Orders_Dashboard_Widget' ) ) {

class Wc_Orders_Dashboard_Widget {
	
	public function __construct() {

		add_action( 'init', 					array( &$this, 'wodw_update_po_file' ) );		
		add_action( 'admin_enqueue_scripts', 	array( &$this, 'wodw_my_enqueue_scripts_css' ) );
		add_action( 'wp_dashboard_setup', 		array( &$this, 'wodw_orders_dashboard_widgets' ) );
	}

	/**
	 * Localisation
	 *
	 * @version 1.0
	 */

	public function wodw_update_po_file() {
        $domain = 'wodw-order-widget';
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        
        if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
            return $loaded;
        } else {
            load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
        }
    }

	/**
	 * This function include css files required for admin side.
	 *
	 * @version 1.0
	 */

	public function wodw_my_enqueue_scripts_css() {

		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';

		if ( $screen_id == "dashboard" ) {
			wp_enqueue_style( 'wodw-orders-dashboard-widget-css', plugins_url( '/assets/css/wodw_style.css', __FILE__ ) );
		}
		
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 * This function is hooked into the 'wp_dashboard_setup' action below.
	 *
	 * @version 1.0
	 */
	public function wodw_orders_dashboard_widgets(){
	  
		wp_add_dashboard_widget(
			'wc_order_widget_id',  					// Widget Slug
		    'WooCommerce Orders',  					// Title
		    array( $this,'wodw_orders_dashboard_widget_function') 	// Display function    
		);
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 *
	 * @version 1.0
	 */

	public function wodw_orders_dashboard_widget_function() {	

		$args 	= array( 
					'post_type' 		=> 'shop_order',
					'post_status' 		=> 'All',
					'posts_per_page' 	=> 5 
				  );

	    $orders = get_posts( $args );

		if( count( $orders ) > 0 ) {
			?>		
			<table width="100%" class="vao_orders_table">
				<tr>
					<th><?php _e( 'Order Id', 		'wodw-order-widget' ); ?></th>
					<th><?php _e( 'Order Status', 	'wodw-order-widget' ); ?></th>
					<th><?php _e( 'Action', 		'wodw-order-widget' ); ?></th>
				</tr>
			<?php		
			foreach ( $orders as $key => $value ) {
				$order 			= new WC_Order( $value->ID );
				$order_status 	= $order->get_status();
				$status 		= 'order-status status-';
				$order_status_class = $status.$order_status;

				?>
				<tr>
					<td>
					<?php
						// Get the order ID and its link
						if ( $order ) {
		                	echo '<a href="' . admin_url( 'post.php?post=' . $order->get_order_number() . '&action=edit' ) . '">Order #' . $order->get_order_number() . '</a>';
		            ?>
		            </td>	            
		            <td class="<?php echo $order_status_class;?>">
		            <?php
		            	
		                echo esc_html( wc_get_order_status_name( $order_status ) ); // Get status of the order
		            }
		            ?>
		        	</td>	        	
		        	<td>
					
					<?php

					// Adding action buttons for each order

					do_action( 'woocommerce_admin_order_actions_start', $order );

					$actions = array();

					if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
						$actions['processing'] = array(
							'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $value->ID ), 'woocommerce-mark-order-status' ),
							'name'      => __( 'Processing', 'wodw-order-widget' ),
							'action'    => "processing",

						);
					}

					if ( $order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
						$actions['complete'] = array(
							'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $value->ID ), 'woocommerce-mark-order-status' ),
							'name'      => __( 'Complete', 'wodw-order-widget' ),
							'action'    => "complete",
						);
					}

					$actions['view'] = array(
						'url'       => admin_url( 'post.php?post=' . $value->ID . '&action=edit' ),
						'name'      => __( 'View', 'wodw-order-widget' ),
						'action'    => "view",
					);

					$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $order );

					foreach ( $actions as $action ) {
						printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a> ',
							esc_attr( $action['action'] ), 
							esc_url( $action['url'] ), 
							esc_attr( $action['name'] ), 
							esc_attr( $action['name'] ) 
						);
					}

					do_action( 'woocommerce_admin_order_actions_end', $order );
					
					?>
					</td>
				</tr>
				<?php			
			}

			?></table><?php
			
			// Adding All orders link which will redirect to Orders page of WooCommerce
			printf( '<div id="vao_orders">
						<span class="dashicons dashicons-cart"></span> 
						<a href="%s">' . __( 'View all orders', 'wodw-order-widget' ) . '</a>
					</div>',
					admin_url( 'edit.php?post_type=shop_order' ) 
			);		
		}else{
			// If no orders then display No Orders message
			echo __( 'No Orders.', 'wodw-order-widget' );
		}
	}
}
$wc_orders_dashboard_widget = new Wc_Orders_Dashboard_Widget();
}
?>
