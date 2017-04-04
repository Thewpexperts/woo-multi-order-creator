<?php /*
Plugin Name: Woo Product Wise Multi Order Creator
Description: Create multiple orders in woocommerce product wise.
Author: The WP Experts
Author URI: http://www.thewpexperts.co.uk
Version: 1.0
License: GPL 2.0
Text Domain: woocommerce
*/

 /*
Woo Product Wise Multi Order Creator is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or any later version.
 
Woo Product Wise Multi Order Creator is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Woo Product Wise Multi Order Creator. If not, see license.txt.
*/
if ( ! defined( 'ABSPATH' ) ) die('Access Restricted');
 if ( ! class_exists( 'WPWMOC_CPInstallCheck' ) ) {
    class WPWMOC_CPInstallCheck {
		static function install() {
			if ( !is_plugin_active('woocommerce/woocommerce.php')){
				deactivate_plugins(__FILE__);
				$error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>  plugins to be active!', 'woocommerce');
				die($error_message);
			}
		}
	}
}
register_activation_hook( __FILE__, array('WPWMOC_CPInstallCheck', 'install') );
add_action('init','wpwmoc_multi_order_load_function');
function wpwmoc_multi_order_load_function() {
    $enableMultiOrder = get_option('enableMultiOrder');	
	$customOrderPermit = get_option('customOrderPermit');	
	add_action('admin_menu', 'wpwmoc_register_multi_order_setting_submenu',99);
	if($enableMultiOrder == 1){
		add_action( 'woocommerce_thankyou', 'wpwmoc_multi_order_generator', 10, 1 );
			if($customOrderPermit ==1){
			add_action( 'woocommerce_after_order_notes', 'wpwmoc_order_dates_checkout_field' ); 
			add_action( 'wp_head', 'wpwmoc_date_picker_scripts' );
			add_action( 'woocommerce_checkout_update_order_meta', 'wpwmoc_checkout_update_delivery_meta' );
			add_filter("manage_edit-shop_order_columns", "wpwmoc_order_extra_columns");
			add_action("manage_posts_custom_column",  "wpwmoc_order_extra_columns_content");
			add_filter( 'manage_edit-shop_order_sortable_columns', 'wpwmoc_sortable_cake_column' );
		}
	}
} 
function wpwmoc_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=multi-order-setting">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'wpwmoc_plugin_settings_link' );
function wpwmoc_register_multi_order_setting_submenu() {
	add_submenu_page( 'woocommerce', 'Multi-order Setting', 'Multi-order Setting', 'manage_options', 'wpwmoc-multi-order-setting', 'wpwmoc_multi_order_setting' ); 
} 
function wpwmoc_multi_order_setting(){
	include(dirname(__FILE__).'/admin/settings.php');
}
function wpwmoc_date_picker_scripts() { 
	$customOrderPermit = get_option('customOrderPermit');	
	if(is_page('checkout') && $customOrderPermit == 1){
	  wp_enqueue_script( 'jquery-ui-datepicker' );
	  wp_register_style('wpwmoc-jquery-ui', plugins_url('/', __FILE__).'css/jquery-ui.css');
	  wp_enqueue_style( 'wpwmoc-jquery-ui' );   	   
	}
}
function wpwmoc_checkout_update_delivery_meta( $order_id ) {
	$dateArray = array();
	$customOrderPermit = get_option('customOrderPermit');	
	$dayDifference = get_option('dayDifference');	
	$dt = date('d/m/y', strtotime("+".$dayDifference." days"));
	$customDatePicker	= isset($_POST['customDatePicker'])?$_POST['customDatePicker']:array(); 
    if ( is_array($customDatePicker) && count($_POST['customDatePicker'])>0) {
		foreach($customDatePicker as $custom_date){
			if(!empty($custom_date)){
				$dateArray[]  = $custom_date; 
			}else{
				$dateArray[]  = $dt;
			}
		}
    }
	$dateValue = join( ', ', $dateArray ); 
	update_post_meta( $order_id, 'Delivery_Dates', $dateValue);
} 
function wpwmoc_order_dates_checkout_field( $checkout ) {
	global $woocommerce,$orderRate;
	$customOrderPermit = get_option('customOrderPermit');	
	$dayDifference = get_option('dayDifference');	
	$dt = date('Y-m-d', strtotime("+".$dayDifference." days"));
	$items = $woocommerce->cart->get_cart();
	$itemsValue	= isset($items)?$items:array();
	if ( is_array($itemsValue) && count($itemsValue)>0) {
		foreach($itemsValue as $item => $values) { 
			$_product = $values['data']->post; 
			$price = get_post_meta($values['product_id'] , '_price', true); 
			echo "<b>".$_product->post_title."</b>&nbsp;&nbsp;";
			echo '<input type="text" class="deliveryDate" id="datepicker'.$_product->ID.'" name="customDatePicker[]" placeholder="Choose dates">'; ?> 
			<script type="text/javascript">	
				jQuery(document).ready(function() {
					jQuery("#datepicker<?php echo $_product->ID;?>").datepicker({ 
						dateFormat: 'dd/mm/yy', 
						minDate: new Date('<?php echo $dt; ?>'),
					});
				});
			</script>	
			<?php 
		} 
	}
}
function wpwmoc_multi_order_generator($order_id) {
	$customOrderPermit = get_option('customOrderPermit');	
    $order = new WC_Order( $order_id );
	$items = $order->get_items();
	$count =0;
	$productID = array();
	$productQty = array();
	$deliveryDate = get_post_meta( $order_id, 'Delivery_Dates',true);
	$dateArray = explode(',',$deliveryDate);
	foreach($items as $order_item_id => $item){
		if($count >0){
			$productName = get_the_title($item['product_id']);
			$orderValueID = wpwmoc_create_new_order_record($order_id);
			wpwmoc_add_product_with_order($orderValueID,$item['product_id'],$item['qty']);
			if($customOrderPermit == 1){
					update_post_meta($orderValueID,'_Delivery_Date',$dateArray[$count]);
			}
			wc_delete_order_item($order_item_id);
		}else{
			if($customOrderPermit == 1){
					update_post_meta($order_id,'_Delivery_Date',$dateArray[0]);
			}
		}
		$count++;
	}
}
function wpwmoc_add_product_with_order($orderID,$productID,$productQty){
	$ProOrderItem = new WC_Order($orderID);	
	$ProOrderItem->add_product(get_product($productID),$productQty); 
}
function wpwmoc_create_new_order_record($orderID) {
    global $wpdb;
    global $woocommerce;
    $original_order = new WC_Order($orderID);
    $currentUser = wp_get_current_user();
    //1 Create Order
    $order_data =  array(
        'post_type'     => 'shop_order',
        'post_status'   => 'publish',
        'ping_status'   => 'closed',
        'post_author'   => $currentUser->ID, 
		'post_excerpt' => $original_order->customer_message, 
        'post_password' => uniqid( 'order_' )   // Protects the post just in case
    );
    $order_id = wp_insert_post( $order_data, true );
    if ( is_wp_error( $order_id ) ){
        $msg = "Unable to create order:" . $order_id->get_error_message();;
        throw new Exception( $msg );
    } else {
        $order = new WC_Order($order_id);
        //2 Update Order Header	
        update_post_meta( $order_id, '_order_shipping', get_post_meta($orderID, '_order_shipping', true) );
        update_post_meta( $order_id, '_order_discount', get_post_meta($orderID, '_order_discount', true) );
        update_post_meta( $order_id, '_cart_discount',  get_post_meta($orderID, '_cart_discount', true) );
        update_post_meta( $order_id, '_order_tax',              get_post_meta($orderID, '_order_tax', true) );
        update_post_meta( $order_id, '_order_shipping_tax',     get_post_meta($orderID, '_order_shipping_tax', true) );
        update_post_meta( $order_id, '_order_total',            get_post_meta($orderID, '_order_total', true) );

        update_post_meta( $order_id, '_order_key',              'wc_' . apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
        update_post_meta( $order_id, '_customer_user',          get_post_meta($orderID, '_customer_user', true) );
        update_post_meta( $order_id, '_order_currency',         get_post_meta($orderID, '_order_currency', true) );
        update_post_meta( $order_id, '_prices_include_tax',     get_post_meta($orderID, '_prices_include_tax', true) );
        update_post_meta( $order_id, '_customer_ip_address',    get_post_meta($orderID, '_customer_ip_address', true) );
        update_post_meta( $order_id, '_customer_user_agent',    get_post_meta($orderID, '_customer_user_agent', true) );
        
		//3 Add Billing Fields

        update_post_meta( $order_id, '_billing_city',           get_post_meta($orderID, '_billing_city', true));
        update_post_meta( $order_id, '_billing_state',          get_post_meta($orderID, '_billing_state', true));
        update_post_meta( $order_id, '_billing_postcode',       get_post_meta($orderID, '_billing_postcode', true));
        update_post_meta( $order_id, '_billing_email',          get_post_meta($orderID, '_billing_email', true));
        update_post_meta( $order_id, '_billing_phone',          get_post_meta($orderID, '_billing_phone', true));
        update_post_meta( $order_id, '_billing_address_1',      get_post_meta($orderID, '_billing_address_1', true));
        update_post_meta( $order_id, '_billing_address_2',      get_post_meta($orderID, '_billing_address_2', true));
        update_post_meta( $order_id, '_billing_country',        get_post_meta($orderID, '_billing_country', true));
        update_post_meta( $order_id, '_billing_first_name',     get_post_meta($orderID, '_billing_first_name', true));
        update_post_meta( $order_id, '_billing_last_name',      get_post_meta($orderID, '_billing_last_name', true));
        update_post_meta( $order_id, '_billing_company',        get_post_meta($orderID, '_billing_company', true));

        //4 Add Shipping Fields

        update_post_meta( $order_id, '_shipping_country',       get_post_meta($orderID, '_shipping_country', true));
        update_post_meta( $order_id, '_shipping_first_name',    get_post_meta($orderID, '_shipping_first_name', true));
        update_post_meta( $order_id, '_shipping_last_name',     get_post_meta($orderID, '_shipping_last_name', true));
        update_post_meta( $order_id, '_shipping_company',       get_post_meta($orderID, '_shipping_company', true));
        update_post_meta( $order_id, '_shipping_address_1',     get_post_meta($orderID, '_shipping_address_1', true));
        update_post_meta( $order_id, '_shipping_address_2',     get_post_meta($orderID, '_shipping_address_2', true));
        update_post_meta( $order_id, '_shipping_city',          get_post_meta($orderID, '_shipping_city', true));
        update_post_meta( $order_id, '_shipping_state',         get_post_meta($orderID, '_shipping_state', true));
        update_post_meta( $order_id, '_shipping_postcode',      get_post_meta($orderID, '_shipping_postcode', true));

        //6 Copy shipping items and shipping item meta from original order
        $original_order_shipping_items = $original_order->get_items('shipping');
        foreach ( $original_order_shipping_items as $original_order_shipping_item ) {
            $item_id = wc_add_order_item( $order_id, array(
                'order_item_name'       => $original_order_shipping_item['name'],
                'order_item_type'       => 'shipping'
            ) );
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'method_id', $original_order_shipping_item['method_id'] );
                wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $original_order_shipping_item['cost'] ) );
            }
        }
        
        // Store coupons
        $original_order_coupons = $original_order->get_items('coupon');
        foreach ( $original_order_coupons as $original_order_coupon ) {
            $item_id = wc_add_order_item( $order_id, array(
                'order_item_name'       => $original_order_coupon['name'],
                'order_item_type'       => 'coupon'
            ) );
            // Add line item meta
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'discount_amount', $original_order_coupon['discount_amount'] );
            }
        }

        //Payment Info
        update_post_meta( $order_id, '_payment_method',         get_post_meta($orderID, '_payment_method', true) );
        update_post_meta( $order_id, '_payment_method_title',   get_post_meta($orderID, '_payment_method_title', true) );
        update_post_meta( $order->id, 'Transaction ID',         get_post_meta($orderID, 'Transaction ID', true) );
        $order->payment_complete();

        //6 Set Order Status to processing to trigger initial emails to end user and vendor
        $order->update_status('processing'); 
    }
	return $order_id;
}
function wpwmoc_order_extra_columns($columns)
{
   $newcolumns = array(
		"cb"       		=> "<input type  = \"checkbox\" />",
		"delivery"    => esc_html__('Delivery', 'woocommerce'),
	);
 	$columns = array_merge($newcolumns, $columns);
	return $columns;
}
function wpwmoc_order_extra_columns_content($column)
{
	global $post;
	$order_id = $post->ID;
	switch ($column)
	{
		case "delivery":
		$daliveryDate = get_post_meta($order_id,'_Delivery_Date',true);
		if ( empty( $daliveryDate ) )
				echo __( '-' );
		else
			printf( __( '%s' ), $daliveryDate );
		break;
	}
}
function wpwmoc_sortable_cake_column( $columns ) {
$columns['delivery'] = 'delivery';
    return $columns;
} 