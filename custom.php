<?php
/*
 * @wordpress-plugin
 * Plugin Name:     Narola Solutions
 * Description:     Used for manage custom code.
 * Version:         1.0.0
 * Author:          Narola
 */

add_action('wp_head', 'custom_variables_declaration');

function custom_variables_declaration() {

	$disable_dates = array();

	$_close_dates = get_option("booking_closing_dates");
	$_close_timeslots = get_option('all_timeslots');
	$_close_locations = get_option('all_locations');

	foreach ($_close_dates as $_close_date) {

		$c_dates = explode(",", $_close_date);
		foreach ($c_dates as $c_date) {
			$booking_close_dates[] = date('d-m-Y', strtotime($c_date));
		}

	}

	foreach ($_close_timeslots as $_close_timeslot) {

		$c_timeslots = $_close_timeslot;
		foreach ($c_timeslots as $c_timeslot) {
			$booking_close_timeslots[] = $c_timeslot;
		}

	}

	foreach ($_close_locations as $_close_location) {

		$c_locations = $_close_location;
		foreach ($c_locations as $c_location) {
			$booking_close_locations[] = $c_location;
		}

	}

	if ($booking_close_dates != '' && $booking_close_timeslots != '' && $booking_close_locations != '') {
		if (in_array('all', $booking_close_timeslots) || in_array('all', $booking_close_locations)) {
			$disable_dates = $booking_close_dates;
		}
	}
	?>

<script type="text/javascript">
var disableDates = '<?php echo json_encode($disable_dates); ?>';
var AJAXURL = '<?php echo esc_url(admin_url("admin-ajax.php")); ?>';
var SITE_URL = '<?php echo esc_url(home_url()); ?>';
</script>
<?php
}

add_action('admin_head', 'custom_admin_js_declaration');
function custom_admin_js_declaration() {?>
<script type="text/javascript">
jQuery("document").ready(function(){
	if(jQuery("body").hasClass("post-type-product")){
		jQuery('.custom_attr_search').select2();
	}
	if(jQuery("body").hasClass("toplevel_page_picnic-booking-settings")){
		jQuery('.multiselect').select2();
	}
});
</script>
<?php
}

/* Include custom js and css */
add_action('wp_enqueue_scripts', 'enqueue_my_scripts');
function enqueue_my_scripts() {

	if (is_product() || is_cart()) {

		wp_enqueue_style('bootstrap-style', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css');
		wp_enqueue_style('bootstrap-datepicker-style', plugin_dir_url(__FILE__) . 'css/bootstrap-datepicker.min.css');
		wp_enqueue_style('custom-style', plugin_dir_url(__FILE__) . 'css/custom.css');

		wp_enqueue_script('bootstrap-script', plugin_dir_url(__FILE__) . 'js/bootstrap.min.js', array(), filemtime(plugin_dir_path('/js/bootstrap.min.js')));
		wp_enqueue_script('bootstrap-datepicker-script', plugin_dir_url(__FILE__) . 'js/bootstrap-datepicker.min.js', array(), filemtime(plugin_dir_path('/js/bootstrap-datepicker.min.js')));
		wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'js/script.js', array(), filemtime(plugin_dir_path('/js/script.js')));
	}
}

add_action('admin_footer', 'enqueue_admin_scripts');
function enqueue_admin_scripts() {

	if (isset($_GET['page']) && $_GET['page'] == 'picnic-booking-settings') {
		wp_enqueue_style('bootstrap-style', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css');
		wp_enqueue_script('bootstrap-script', plugin_dir_url(__FILE__) . 'js/bootstrap.min.js', array());
	}

	wp_enqueue_script('multi-datepicker-script', plugin_dir_url(__FILE__) . 'js/jquery-ui.multidatespicker.js', array());

}

/* Remove filters and actions hooks */
add_action('init', 'remove_filters_and_actions_hook');
function remove_filters_and_actions_hook() {

	remove_action('bridge_qode_action_woocommerce_info_below_image_hover', 'bridge_qode_woocommerce_quickview_link', 1);
	remove_action('bridge_qode_action_style_dynamic', 'bridge_qode_yith_wishlist_styles');
}

/** Create custom fields for picnic setting in Woo Admin products tabs **/
add_filter('woocommerce_product_data_tabs', 'custom_product_tabs');
function custom_product_tabs($tabs) {

	$tabs['picnicoptions'] = array(
		'label' => __('Picnic Options', 'woocommerce'),
		'target' => 'picnic_options',
	);

	return $tabs;
}

add_filter('woocommerce_product_data_panels', 'picnic_options_product_tab_content');
function picnic_options_product_tab_content() {
	global $post, $woocommerce;

	$timeslots_array = array();
	$picnic_locations_array = array();

	$product = wc_get_product($post->ID);

	$get_timeslotes = get_post_meta($product->get_id(), '_picnic_timeslots', true);
	$get_picnic_locations = get_post_meta($product->get_id(), '_picnic_locations', true);

	$timeslots = get_terms(array(
		'taxonomy' => 'pa_picnic-time-slots',
		'hide_empty' => false,
	));

	$picnic_locations = get_terms(array(
		'taxonomy' => 'pa_picnic-locations',
		'hide_empty' => false,
	));

	if (!empty($get_timeslotes)) {

		$terms_ts = get_terms(array(
			'taxonomy' => 'pa_picnic-time-slots',
			'hide_empty' => false,
		));

		foreach ($get_timeslotes as $get_timeslote_id) {

			foreach ($terms_ts as $ts) {

				if ($ts->term_id == $get_timeslote_id) {

					$timeslots_array[] = $ts->term_id;
				}
			}
		}

	}

	if (!empty($get_picnic_locations)) {

		$terms_pl = get_terms(array(
			'taxonomy' => 'pa_picnic-locations',
			'hide_empty' => false,
		));

		foreach ($get_picnic_locations as $get_picnic_location_id) {

			foreach ($terms_pl as $pl) {

				if ($pl->term_id == $get_picnic_location_id) {

					$picnic_locations_array[] = $pl->term_id;
				}
			}
		}
	}?>

<div id='picnic_options' class='panel woocommerce_options_panel'>
    <div class="options_group">
        <?php if ($woocommerce->version >= '3.0'): ?>
        <p class="form-field">
            <label for="picnic_timeslots"><?php _e('Add picnic time slots', 'woocommerce');?></label>
            <select class="custom_attr_search multiselect attribute_values wc-enhanced-select enhanced" multiple="multiple" style="width: 50%;" id="picnic_timeslots"
                name="picnic_timeslots[]" data-placeholder="<?php esc_attr_e('Search for time slot', 'woocommerce');?>">
                <?php foreach ($timeslots as $timeslot) {?>
				<?php if (in_array($timeslot->term_id, $timeslots_array)) {$selected = "selected";} else { $selected = "";}?>
				<?php echo '<option value="' . esc_attr($timeslot->term_id) . '"' . $selected . '>' . wp_kses_post($timeslot->name) . '</option>';} ?></select>
                <?php echo wc_help_tip(__('Select picnic time slots for this product.', 'woocommerce')); ?>
        </p>
        <?php endif;?>
    </div>

    <div class="options_group">
        <?php if ($woocommerce->version >= '3.0'): ?>
        <p class="form-field">
            <label for="picnic_locations"><?php _e('Add picnic locations', 'woocommerce');?></label>
            <select class="custom_attr_search multiselect attribute_values wc-enhanced-select enhanced" multiple="multiple" style="width: 50%;" id="picnic_locations"
                name="picnic_locations[]"
                data-placeholder="<?php esc_attr_e('Search for picnic locations', 'woocommerce');?>">
                <?php foreach ($picnic_locations as $picnic_location) {?>
                <?php if (in_array($picnic_location->term_id, $picnic_locations_array)) {$selected = "selected";} else { $selected = "";}?>
				<?php echo '<option value="' . esc_attr($picnic_location->term_id) . '"' . $selected . '>' . wp_kses_post($picnic_location->name) . '</option>';} ?> </select>
                <?php echo wc_help_tip(__('Select picnic locations for this product.', 'woocommerce')); ?>
        </p>
        <?php endif;?>
    </div>

    <?php woocommerce_wp_text_input(array('id' => 'max_allowed_persons', 'label' => __('Add Max Allowed Persons For Picnic', 'woocommerce'), 'desc_tip' => 'true', 'description' => __('Enter maximum allowed persons for this picnic', 'woocommerce'), 'type' => 'text', 'value' => get_post_meta($product->get_id(), '_max_allowed_persons', true)));
	?>
</div>
<?php
}

/* Save picnic custom fields */
add_action('woocommerce_process_product_meta', 'save_picnic_options_fields', 20, 1);

function save_picnic_options_fields($product_id) {

	if (isset($_POST['picnic_timeslots'])) {

		update_post_meta($product_id, '_picnic_timeslots', array_map('intval', (array) wp_unslash($_POST['picnic_timeslots'])));
	}

	if (isset($_POST['picnic_locations'])) {
		update_post_meta($product_id, '_picnic_locations', array_map('intval', (array) wp_unslash($_POST['picnic_locations'])));
	}

	if (isset($_POST['max_allowed_persons'])) {
		update_post_meta($product_id, '_max_allowed_persons', $_POST['max_allowed_persons']);
	}

}

/* Display picnic custom fields on product page */

add_action('woocommerce_before_add_to_cart_form', 'display_picnic_custom_fields');

function display_picnic_custom_fields() {
	global $product, $wpdb;

	if (is_product()) {

		$date = date('Y-m-d');

		$query = "SELECT * FROM " . $wpdb->prefix . "picnic_booking_history WHERE product_id=" . $product->get_id() . " AND picnic_date ='" . $date . "'";

		$check_timeslot_avail = $wpdb->get_results($query);?>

		<div class="picnic_custom_options_wrapper">
	    <?php $get_locations = get_post_meta($product->get_id(), '_picnic_locations', true);?>

	    <?php if ($get_locations != ''): ?>
	    <div class="option_picnic_locations">
	        <label for="picnic_locations_option">Where would you like to picnic? <sup>*</sup></label>
	        <select name="picnic_locations_option" id="picnic_locations_option" class="picnic_locations_option selectbox">
	            <option  value="">Select Avaliable Location</option>
	            <?php foreach ($get_locations as $lc): ?>

	            	<?php $location = get_term_by('id', $lc, 'pa_picnic-locations');?>
	            	<option value="<?php echo $location->term_id; ?>"><?php echo $location->name; ?></option>

	        	<?php endforeach;?>
	        </select>
	    </div>
	    <?php endif;?>

	    <?php $get_max_persons = get_post_meta($product->get_id(), '_max_allowed_persons', true);?>
	    <?php if (!empty($get_max_persons)): ?>
	    <div class="option_num_persons">
	        <label for="allowed_num_persons">For how many people would you like to book? <sup>*</sup></label>
	         <select name="allowed_num_persons" id="allowed_num_persons" class="allowed_num_persons selectbox">
	            <option>Select Persons</option>
	            <?php for ($i = 2; $i <= $get_max_persons; $i++): ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php endfor;?>
	        </select>
	    </div>
	    <?php endif;?>

	    <div class="option_picnic_date">
	        <label for="picnic_date">What day would you like to picnic? <sup>*</sup></label>
	        
	        <div class="form-group">
	            <div class='input-group' style="width: 50%;">
	               <input type="text" class="picnic_date form-control datepicker selectbox" name="picnic_date"/>
	               <span class="input-group-addon">
	               		<span class="glyphicon glyphicon-calendar"></span>
	               </span>
	            </div>
	         </div>
	    </div>

	    <?php $get_timeslotes = get_post_meta($product->get_id(), '_picnic_timeslots', true);?>
	    <?php if ($get_timeslotes != ''): ?>
	    <div class="option_timeslot">
	        <label for="timeslot_option">Request a time <sup>*</sup></label>
	        <select name="timeslot_option" id="timeslot_option" class="timeslot_option selectbox">
	            <option value="">Select Avaliable Timeslot</option>
	            <?php if (empty($check_timeslot_avail)): ?>
		          <?php foreach ($get_timeslotes as $ts): ?>
		             <?php $timeslot = get_term_by('id', $ts, 'pa_picnic-time-slots');?>
		             <option value="<?php echo $timeslot->term_id; ?>"><?php echo $timeslot->name; ?></option>
		          <?php endforeach;?>
	            <?php endif;?>
	        </select>
	    </div>
	    <?php endif;?>
	    <input type="hidden" name="product_id" class="product_id" value="<?php echo $product->get_id(); ?>">
	</div>
	<div class="selection_response"></div>
<?php }
}

/* Add add-ons popup */
add_action('wp_footer', 'add_addons_modal');
function add_addons_modal() {

	if (is_product()) {

		$product_ids = array();
		$products = new WP_Query(array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'fields' => 'ids',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => 'add-ons',
				),
			),

		));

		if ($products->have_posts()) {
			while ($products->have_posts()) {
				$products->the_post();
				$product_ids[] = get_the_ID();
			}
		}?>
  <div class="modal fade addons_popup" id="addons_popup" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Spice up your picnic with our add-on selection</h5>
            </div>
            <div class="modal-body">
                <div class="addons-wrapper">
                    <?php if (!empty($product_ids)): ?>
                    <ul class="addons_list">
                        <?php foreach ($product_ids as $product_id): ?>
                        <?php $product = wc_get_product($product_id);?>
                        <li id="addon_<?php echo $product_id; ?>">
                            <div class="custom-control custom-checkbox mr-sm-2">
                               
                                <input type="hidden" name="addons" class="addons" value="<?php echo $product_id; ?>">
                                <label for="addon-<?php echo $product_id; ?>" class="custom-control-label">
                                    <?php echo $product->get_name(); ?> -
                                    <?php echo wc_price($product->get_price()); ?>
                                </label>
                            </div>
                            <div class="addon-qty-box">
                                <?php $input_id = 'quantity_' . $product_id;?>
		                        <?php woocommerce_quantity_input(array('input_id' => $input_id, 'input_value' => 0, 'min_value' => 0), $product, true);?>
                            </div>
                        </li>
                        <?php endforeach;?>
                    </ul>
                    <?php endif;?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary add-addons">Add</button>
                <button type="button" class="modal-close btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php }
}

/* Change price display html */
add_filter('woocommerce_get_price_html', 'picnic_price_html', 100, 2);
function picnic_price_html($price, $product) {

	if (is_admin()) {
		return $price;
	}

	$category = get_the_terms($product->get_id(), 'product_cat');

	foreach ($category as $cat) {
		if ($cat->slug == 'add-ons') {
			$pp = 'per item';
		} else {
			$pp = 'per person';
		}
	}

	$price_html = '';
	$price_html .= "<div class='single_product_price'>";
	$price_html .= wc_price($product->get_price()) . ' ' . $pp;
	$price_html .= "</div>";

	return $price_html;
}

/* Add picnic to cart with all selected options */
add_action('wp_ajax_ajax_add_to_cart', 'custome_add_to_cart');
add_action('wp_ajax_nopriv_ajax_add_to_cart', 'custome_add_to_cart');

function custome_add_to_cart() {

	global $woocommerce;

	$response = array();

	$data = $_POST['formData'];

	$picnic_location = $data['picnic_location'];
	$num_persons = $data['num_persons'];
	$picnic_date = $data['date'];
	$timeslot = $data['timeslot'];
	$product_id = $data['product_id'];
	$quantity = $data['quantity'];

	if (isset($data['selected_addons']) && !empty($data['selected_addons'])) {
		$selected_addons = $data['selected_addons'];
	} else {
		$selected_addons = '';
	}

	$custom_fields = array(
		'picnic_location' => $picnic_location,
		'num_persons' => $num_persons,
		'picnic_date' => $picnic_date,
		'timeslot' => $timeslot,
		'selected_addons' => $selected_addons,
	);

	update_post_meta($product_id, '_cart_custom_picnic_data', $custom_fields);

	if ($woocommerce->cart->add_to_cart($product_id, $quantity)) {

		do_action('woocommerce_ajax_added_to_cart', $product_id);

		if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
			wc_add_to_cart_message(array($product_id => $quantity), true);
		}

		$response['result'] = "success";
	} else {
		$response['result'] = "error";
	}

	if ($response['result'] == 'success') {
		WC_AJAX::get_refreshed_fragments();
	} else {
		echo json_encode($response);
	}

	wp_die();
}

/* Add custom picnic data in cart session */
add_filter('woocommerce_add_cart_item_data', 'add_custom_cart_item_data', 10, 2);

function add_custom_cart_item_data($cart_item_data, $product_id) {

	$picnic_custom_data = get_post_meta($product_id, '_cart_custom_picnic_data', true);

	if ($picnic_custom_data != "") {

		$cart_item_data["picnic_location"] = $picnic_custom_data['picnic_location'];
		$cart_item_data["num_persons"] = $picnic_custom_data['num_persons'];
		$cart_item_data["picnic_date"] = date('d-m-Y', strtotime($picnic_custom_data['picnic_date']));
		$cart_item_data["timeslot"] = $picnic_custom_data['timeslot'];

		if ($picnic_custom_data['selected_addons'] != '') {
			$cart_item_data["selected_addons"] = $picnic_custom_data['selected_addons'];
		}
	}

	return $cart_item_data;
}

/* Display picnic custom data to cart table */
add_filter('woocommerce_get_item_data', 'display_custom_data_on_cart_checkout', 10, 2);

function display_custom_data_on_cart_checkout($cart_data, $cart_item = null) {
	$custom_items = array();

	if (!empty($cart_data)) {
		$custom_items = $cart_data;
	}

	if (isset($cart_item["picnic_location"])) {

		$location = get_term_by('id', $cart_item["picnic_location"], 'pa_picnic-locations');
		$custom_items[] = array("name" => "Picnic Location", "value" => $location->name);
	}

	if (isset($cart_item["num_persons"])) {
		$custom_items[] = array("name" => "Number Of Persons", "value" => $cart_item["num_persons"]);
	}

	if (isset($cart_item["picnic_date"])) {
		$custom_items[] = array("name" => "Picnic Date", "value" => $cart_item["picnic_date"]);
	}

	if (isset($cart_item["timeslot"])) {
		$timeslot = get_term_by('id', $cart_item["timeslot"], 'pa_picnic-time-slots');

		$custom_items[] = array("name" => "Timeslot", "value" => $timeslot->name);
	}

	if (isset($cart_item['selected_addons']) && $cart_item['selected_addons'] != '') {

		$item_addons = $cart_item['selected_addons'];

		$custom_items[] = array("name" => "Add-ons");

		foreach ($item_addons as $add_ons) {

			$item = wc_get_product($add_ons['addon_id']);
			$addon_name = $add_ons['addon_qty'] . " x " . $item->get_name();
			$addon_value = wc_price($item->get_price() * $add_ons['addon_qty']);

			$custom_items[] = array("name" => $addon_name, "value" => $addon_value);
		}
	}

	return $custom_items;
}

/* Update cart item price, subtotal & total after addition of number of persons and addons */
add_filter('woocommerce_cart_item_price', 'filter_cart_item_price', 10, 3);
function filter_cart_item_price($price_html, $cart_item, $cart_item_key) {

	$total_price = 0;
	$addons_price = 0;

	$product = wc_get_product($cart_item['data']->id);
	$product_price = $product->get_price();

	if (isset($cart_item['num_persons']) && $cart_item['num_persons'] != '') {

		$all_persons_price = $cart_item['num_persons'] * $product_price;
		$total_price += $all_persons_price;

	} else {

		$total_price = $product_price;
	}

	if (isset($cart_item['selected_addons']) && $cart_item['selected_addons'] != '') {

		$added_addons = $cart_item['selected_addons'];

		foreach ($added_addons as $addon) {
			$addon_item = wc_get_product($addon['addon_id']);

			$addon_amount = ($addon_item->get_price() * $addon['addon_qty']);
			$addons_price += $addon_amount;
		}
	}

	$new_item_price = $total_price + $addons_price;

	if ($new_item_price > 0) {
		return wc_price($new_item_price);
	}

	return $price_html;
}

/* Modify the label of cart item subtotal */

add_filter('woocommerce_cart_item_subtotal', 'change_item_subtotal_html', 99, 3);
function change_item_subtotal_html($subtotal, $cart_item, $cart_item_key) {
	global $woocommerce;
	$percentage_label = "<p>25% of main price</p>";
	$subtotal = sprintf('%s %s', $subtotal, $percentage_label);
	return $subtotal;
}

/* Update cart total after addition of number of persons and addons */
add_action('woocommerce_before_calculate_totals', 'before_calculate_totals', 10, 1);
function before_calculate_totals($cart_obj) {

	foreach ($cart_obj->get_cart() as $key => $value) {

		$_total_price = 0;
		$addons_total_price = 0;

		$product = wc_get_product($value['product_id']);
		$price = $product->get_price();
		$picnic_data = get_post_meta($product->get_id(), '_cart_custom_picnic_data', true);

		if ($value['num_persons'] != '') {

			$persons_price = $value['num_persons'] * $price;
			$_total_price += $persons_price;

		} else {

			$_total_price = $price;
		}

		if ($value['selected_addons'] != '') {

			$added_addons = $value['selected_addons'];

			foreach ($added_addons as $addon) {
				$addon_item = wc_get_product($addon['addon_id']);

				$addon_price = ($addon_item->get_price() * $addon['addon_qty']);
				$addons_total_price += $addon_price;
			}
		}

		$new_price = $_total_price + $addons_total_price;
		$final_price = ($new_price * 25) / 100;
		$value['data']->set_price($final_price);
	}
}

/* Add custom picnic data in order meta */
add_action('woocommerce_add_order_item_meta', 'add_custom_order_item_data', 1, 2);
function add_custom_order_item_data($item_id, $values) {

	global $woocommerce, $wpdb;
	$custom_fields = array();

	if (isset($values["picnic_location"])) {

		$location = get_term_by('id', $values["picnic_location"], 'pa_picnic-locations');
		$custom_fields['picnic_location'] = $location->name;
	}

	if (isset($values["num_persons"])) {
		$custom_fields['num_persons'] = $values["num_persons"];
	}

	if (isset($values["picnic_date"])) {
		$custom_fields['picnic_date'] = $values["picnic_date"];
	}

	if (isset($values["timeslot"])) {
		$timeslot = get_term_by('id', $values["timeslot"], 'pa_picnic-time-slots');
		$custom_fields['timeslot'] = $timeslot->name;
	}

	if (isset($values['selected_addons']) && $values['selected_addons'] != '') {
		$item_addons = $values['selected_addons'];
		foreach ($item_addons as $add_on) {

			$item = wc_get_product($add_on['addon_id']);
			$addon_value = $add_on['addon_qty'] . " x " . $item->get_name() . " - " . wc_price($item->get_price() * $add_on['addon_qty']);

			$custom_fields['selected_addons'][] = array('addon_id' => $add_on['addon_id'], 'addon_qty' => $add_on['addon_qty'], 'addon_value' => $addon_value);
		}
	}

	if (!empty($custom_fields)) {
		wc_add_order_item_meta($item_id, '_order_custom_picnic_data', $custom_fields);
	}
}

/* Remove custom picnic options session data */
add_action('woocommerce_thankyou', 'save_picnic_option_in_order', 10, 1);

function save_picnic_option_in_order($order_id) {
	global $wpdb;

	$custom_data = array();
	$table = $wpdb->prefix . "picnic_booking_history";

	$order = wc_get_order($order_id);
	$custom_data['order_id'] = $order_id;

	foreach ($order->get_items() as $item_id => $item) {

		$product_id = $item->get_product_id();
		$custom_data['product_id'] = $product_id;

		$picnic_custom_data = get_post_meta($product_id, '_cart_custom_picnic_data', true);

		if ($picnic_custom_data["timeslot"] != '') {

			$custom_data['timeslot_id'] = $picnic_custom_data["timeslot"];
		}

		if ($picnic_custom_data["picnic_location"] != '') {

			$custom_data['location_id'] = $picnic_custom_data["picnic_location"];
		}

		if ($picnic_custom_data["num_persons"] != '') {

			$custom_data['num_of_persons'] = $picnic_custom_data["num_persons"];
		}

		if ($picnic_custom_data["selected_addons"] != '') {

			$custom_data['add_ons'] = $picnic_custom_data["selected_addons"];
		}
		if ($picnic_custom_data["picnic_date"] != '') {

			$custom_data['picnic_date'] = date('Y-m-d', strtotime($picnic_custom_data['picnic_date']));
		}

		if ($wpdb->insert($table, $custom_data)) {
			delete_post_meta($product_id, '_cart_custom_picnic_data');
		}
	}
}

/* Display picnic data on thankyou page and order detail page */

add_action('woocommerce_thankyou', 'display_picnic_data_on_order_and_thankyou_page', 20);
add_action('woocommerce_view_order', 'display_picnic_data_on_order_and_thankyou_page', 20);

function display_picnic_data_on_order_and_thankyou_page($order_id) {

	$order = wc_get_order($order_id);?>

<h2>Picnic Booking Details</h2>
<?php foreach ($order->get_items() as $item_id => $item) {?>
<?php $picnic_info = wc_get_order_item_meta($item_id, '_order_custom_picnic_data', true);?>

<table class="woocommerce-table shop_table picnic_info">
    <tbody>
        <tr>
            <th>Food Plate: </th>
            <td><?php echo $item->get_name(); ?></td>
        </tr>
        <?php if (isset($picnic_info['picnic_date']) && $picnic_info['picnic_date'] != ''): ?>
        <tr>
            <th>Picnic Date: </th>
            <td><?php echo $picnic_info['picnic_date']; ?></td>
        </tr>
        <?php endif;?>
        <?php if (isset($picnic_info['picnic_location']) && $picnic_info['picnic_location'] != ''): ?>
        <tr>
            <th>Picnic Location: </th>
            <td><?php echo $picnic_info['picnic_location']; ?></td>
        </tr>
        <?php endif;?>
        <?php if (isset($picnic_info['timeslot']) && $picnic_info['timeslot'] != ''): ?>
        <tr>
            <th>TimeSlot: </th>
            <td><?php echo $picnic_info['timeslot']; ?></td>
        </tr>
        <?php endif;?>
        <?php if (isset($picnic_info['num_persons']) && $picnic_info['num_persons'] != ''): ?>
        <tr>
            <th>Number Of Persons: </th>
            <td><?php echo $picnic_info['num_persons']; ?></td>
        </tr>
        <?php endif;?>
        <?php if (isset($picnic_info['selected_addons']) && $picnic_info['selected_addons'] != ''): ?>
        <tr>
            <th>Extra Add-Ons: </th>
            <td>
                <ul>
                    <?php foreach ($picnic_info['selected_addons'] as $addon): ?>
                    <li style="list-style: none;"><?php echo $addon['addon_value']; ?></li>
                    <?php endforeach;?>
                </ul>
            </td>
        </tr>
        <?php endif;?>

    </tbody>
</table>
<?php }
}

/* Display picnic data on admin order details */

add_action('woocommerce_after_order_itemmeta', 'display_admin_order_item_custom_data', 20, 3);
function display_admin_order_item_custom_data($item_id, $item, $product) {

	if (!(is_admin() && $item->is_type('line_item'))) {
		return;
	}
	$picnic_info = wc_get_order_item_meta($item_id, '_order_custom_picnic_data', true);?>
<div class="picnic_info">
    <?php if (isset($picnic_info['picnic_date']) && $picnic_info['picnic_date'] != ''): ?>
    <p>
        <strong>Picnic Date: </strong>
        <?php echo $picnic_info['picnic_date']; ?>
    </p>
    <?php endif;?>

    <?php if (isset($picnic_info['picnic_location']) && $picnic_info['picnic_location'] != ''): ?>

    <p>
        <strong>Picnic Location: </strong>
        <?php echo $picnic_info['picnic_location']; ?>
    </p>
    <?php endif;?>

    <?php if (isset($picnic_info['timeslot']) && $picnic_info['timeslot'] != ''): ?>
    <p>
        <strong>Timeslot: </strong>
        <?php echo $picnic_info['timeslot']; ?>
    </p>
    <?php endif;?>

    <?php if (isset($picnic_info['num_persons']) && $picnic_info['num_persons'] != ''): ?>
    <p>
        <strong>Number Of Persons:</strong>
        <?php echo $picnic_info['num_persons']; ?>
    </p>
    <?php endif;?>

    <?php if (isset($picnic_info['selected_addons']) && $picnic_info['selected_addons'] != ''): ?>
    <p>
        <strong>Extra Add-Ons:</strong>
    <ul>
        <?php foreach ($picnic_info['selected_addons'] as $addon): ?>
        <li style="list-style: none;"><?php echo $addon['addon_value']; ?></li>
        <?php endforeach;?>
    </ul>
    </p>
    <?php endif;?>
</div>
<?php
}

/* Fetch Timeslots from selected locations */

add_action('wp_ajax_retrive_timeslots', 'process_retrive_timeslots_callback');
add_action('wp_ajax_nopriv_retrive_timeslots', 'process_retrive_timeslots_callback');

function process_retrive_timeslots_callback() {

	global $wpdb;

	$timeslot_flag = 1;

	$product_id = $_POST['product_id'];
	$location_id = $_POST['location_id'];
	$picnic_date = $_POST['picnic_date'];

	$query = "SELECT * FROM " . $wpdb->prefix . "picnic_booking_history WHERE product_id=" . $product_id . " AND location_id = " . $location_id . " AND picnic_date ='" . $picnic_date . "'";

	$results = $wpdb->get_results($query);

	$get_timeslotes = get_post_meta($product_id, '_picnic_timeslots', true);

	$max_allowed_persons = get_post_meta($product_id, '_max_allowed_persons', true);
	
	$_close_meta = get_option('booking_closing_meta');

	foreach($_close_meta as $date => $close_data){

		$c_dates = explode(",", $date);

		foreach ($c_dates as $c_date) {

			$date_key = date('Y-m-d', strtotime($c_date));
			$booking_close_dates[] = $date_key;

			$c_timeslots = $close_data['timeslot'];
			foreach ($c_timeslots as $c_timeslot) {
				$booking_close_timeslots[$date_key][] = $c_timeslot;
			}

			$c_locations =  $close_data['location'];
			foreach ($c_locations as $c_location) {
				$booking_close_locations[$date_key][] = $c_location;
			}
		}
	}

	if ($booking_close_dates != '' && $booking_close_timeslots != '' && $booking_close_locations != '') {

		if (in_array($picnic_date, $booking_close_dates) && in_array($location_id, $booking_close_locations[$picnic_date])) {
			$timeslot_flag = 0;
		}
	}

	$html = "<option value=''>Select Avaliable Timeslot</option>";

	if ($timeslot_flag != 1) {

		foreach ($booking_close_timeslots[$picnic_date] as $booking_close_timeslot) {
		
			if (in_array($booking_close_timeslot, $get_timeslotes)) {
				$key = array_search($booking_close_timeslot, $get_timeslotes);
				unset($get_timeslotes[$key]);
			}
		}

		if ($get_timeslotes != '') {
			foreach ($get_timeslotes as $ts) {
				$timeslot = get_term_by('id', $ts, 'pa_picnic-time-slots');

				$html .= "<option value='" . $timeslot->term_id . "'>" . $timeslot->name . "</option>";
			}
		}

	} else {

		if (!empty($results)) {

			foreach ($results as $result) {

				if (in_array($result->timeslot_id, $get_timeslotes)) {

					$key = array_search($result->timeslot_id, $get_timeslotes);

					if ($result->num_of_persons > $max_allowed_persons) {
						unset($get_timeslotes[$key]);
					}
				}
			}

			if ($get_timeslotes != '') {
				foreach ($get_timeslotes as $ts) {
					$timeslot = get_term_by('id', $ts, 'pa_picnic-time-slots');

					$html .= "<option value='" . $timeslot->term_id . "'>" . $timeslot->name . "</option>";
				}
			}
		} else {

			if ($get_timeslotes != '') {
				foreach ($get_timeslotes as $ts) {
					$timeslot = get_term_by('id', $ts, 'pa_picnic-time-slots');

					$html .= "<option value='" . $timeslot->term_id . "'>" . $timeslot->name . "</option>";
				}
			}
		}
	}

	echo json_encode(array('html' => $html));
	wp_die();
}

/* Check picnic availability */

add_action('wp_ajax_check_location_availability', 'process_check_location_availability_callback');
add_action('wp_ajax_nopriv_check_location_availability', 'process_check_location_availability_callback');

function process_check_location_availability_callback() {

	global $wpdb;

	$response = array();
	$selection_flag = 1;

	$product_id = $_POST['product_id'];
	$location_id = $_POST['location_id'];
	$timeslot_id = $_POST['timeslot'];
	$picnic_date = $_POST['picnic_date'];
	$num_of_persons = $_POST['num_of_persons'];

	$_close_meta = get_option('booking_closing_meta');

	foreach($_close_meta as $date => $close_data){

		$c_dates = explode(",", $date);

		foreach ($c_dates as $c_date) {

			$date_key = date('Y-m-d', strtotime($c_date));
			$booking_close_dates[] = $date_key;

			$c_timeslots = $close_data['timeslot'];
			foreach ($c_timeslots as $c_timeslot) {
				$booking_close_timeslots[$date_key][] = $c_timeslot;
			}

			$c_locations =  $close_data['location'];
			foreach ($c_locations as $c_location) {
				$booking_close_locations[$date_key][] = $c_location;
			}
		}
	}

	if ($booking_close_dates != '' && $booking_close_timeslots != '' && $booking_close_locations != '') {

		if (in_array($picnic_date, $booking_close_dates) && in_array($timeslot_id, $booking_close_timeslots[$picnic_date]) && in_array($location_id, $booking_close_locations[$picnic_date])) {

			$selection_flag = 0;
		}

	} else {

		if ($booking_close_dates != '' && $booking_close_locations != '') {

			if (in_array($picnic_date, $booking_close_dates) && in_array($location_id, $booking_close_locations[$picnic_date])) {

				$selection_flag = 0;
			}
		}

		if ($booking_close_dates != '' && $booking_close_timeslots != '') {

			if (in_array($picnic_date, $booking_close_dates) && in_array($timeslot_id, $booking_close_timeslots[$picnic_date])) {

				$selection_flag = 0;
			}
		}
	}

	if ($selection_flag != 1) {

		$response['status'] = 'fail';
		$response['msg'] = 'The booking are not available for selected location or timeslot. Please try with different locations or timeslots';

	} else {

		$requested_location = get_term_by('id', $location_id, 'pa_picnic-locations');
		$timeslot = get_term_by('id', $timeslot_id, 'pa_picnic-time-slots');
		$requested_timeslot = strtotime(trim($timeslot->name));

		$current_time = strtotime(date('Y-m-d H:i', current_time('timestamp', 1)));

		$selection = $picnic_date . ' ' . trim($timeslot->name);
		$selection_time = strtotime($selection);

		$hours_diff = $selection_time - $current_time;

		if ($hours_diff < 86400) {

			$response['status'] = 'fail';
			$response['msg'] = 'You cannot book ' . $requested_location->name . ' for ' . $timeslot->name . ' timeslot. Please try to book different locations or timeslots at least 24 hours in advance';

		} else {

			$query = "SELECT * FROM " . $wpdb->prefix . "picnic_booking_history WHERE picnic_date ='" . $picnic_date . "'";

			$results = $wpdb->get_results($query);

			if (!empty($results)) {

				foreach ($results as $result) {

					$ob_location = get_term_by('id', $result->location_id, 'pa_picnic-locations');
					$ob_timeslot = get_term_by('id', $result->timeslot_id, 'pa_picnic-time-slots');

					$selection = $picnic_date . ' ' . trim($ob_timeslot->name);
					$selection_time = strtotime($selection);

					$current_timeslot = strtotime(trim($ob_timeslot->name));
					$timeslot_diff = $requested_timeslot - $current_timeslot;

					if ($location_id == $result->location_id && $timeslot_id == $result->timeslot_id) {

						$response['status'] = 'fail';
						$response['msg'] = 'You cannot book ' . $requested_location->name . ' for ' . $timeslot->name . ' timeslot because this location is already booked for ' . $timeslot->name . ' timeslot';

					} else {

						if ($timeslot_diff <= 1800 && $location_id != $result->location_id) {

							$response['status'] = 'fail';
							$response['msg'] = 'You cannot book ' . $requested_location->name . ' for ' . $timeslot->name . ' timeslot. Please try with different locations or timeslots';

						} else {

							$response['status'] = 'pass';
							$response['msg'] = $requested_location->name . ' is available for ' . $timeslot->name . ' timeslot. Please proceed for booking';
						}

					}

				}

			} else {

				$response['status'] = 'pass';
				$response['msg'] = $requested_location->name . ' is available for ' . $timeslot->name . ' timeslot. Please proceed for booking';
			}
		}
	}

	echo json_encode($response);
	wp_die();
}

/* Change shop loop add to cart button link */
add_filter('woocommerce_loop_add_to_cart_link', 'replacing_add_to_cart_button', 10, 2);
function replacing_add_to_cart_button($button, $product) {
	$button_text = __("Add Your Choice", "woocommerce");
	$button = '<a class="button" href="' . $product->get_permalink() . '">' . $button_text . '</a>';

	return $button;
}

/* Add custom column to admin order table */
add_filter('manage_edit-shop_order_columns', 'add_new_admin_order_column');
function add_new_admin_order_column($columns) {
	$columns['picnic_info'] = 'Picnic Details';
	return $columns;
}

/* Arrange the column in admin table */
add_filter('manage_edit-shop_order_columns', 'arrange_admin_order_table_columns');
function arrange_admin_order_table_columns($product_columns) {

	return array(
		'cb' => '<input type="checkbox" />',
		'order_number' => 'Order',
		'order_date' => 'Date',
		'order_status' => 'Status',
		'picnic_info' => 'Picnic Details',
		'billing_address' => 'Billing',
		'shipping_address' => 'Ship to',
		'order_total' => 'Total',
		'wc_actions' => 'Actions',
	);

}

/* Display picnic details in custom order table column */
add_action('manage_shop_order_posts_custom_column', 'get_order_picnic_meta');

function get_order_picnic_meta($column) {

	global $post;

	if ('picnic_info' === $column) {

		$order = wc_get_order($post->ID);
		$counter = 0;

		foreach ($order->get_items() as $item_id => $item) {
			$picnic_data = wc_get_order_item_meta($item_id, '_order_custom_picnic_data', true);

			if ($counter < count($order->get_items())) {
				$style = "style='margin-bottom:15px'";
			}

			if ($picnic_data != '') {
				echo '<div class="picnic_data" ' . $style . '>';
				echo '<p><strong>Food Plate: </strong>' . $item->get_name() . '</p>';

				if (isset($picnic_data['picnic_date']) && $picnic_data['picnic_date'] != '') {
					echo '<p><strong>Date: </strong>' . date('M d, Y', strtotime($picnic_data['picnic_date'])) . '</p>';
				}
				if (isset($picnic_data['picnic_location']) && $picnic_data['picnic_location'] != '') {
					echo '<p><strong>Location: </strong>' . $picnic_data['picnic_location'] . '</p>';
				}
				if (isset($picnic_data['timeslot']) && $picnic_data['timeslot'] != '') {
					echo '<p><strong>Timeslot: </strong>' . $picnic_data['timeslot'] . '</p>';
				}
				echo "</div>";
			}

			$counter = $counter + 1;
		}

	}
}

/* Add sorting on custom column to admin order table */
add_filter("manage_edit-shop_order_sortable_columns", "add_sorting_picnic_info_col");
function add_sorting_picnic_info_col($columns) {

	$columns['picnic_info'] = 'Picnic Date';
	return $columns;
}

/* Add a dropdown to filter orders by picnic date */
add_action('restrict_manage_posts', 'add_shop_order_filter_by_picnic_date');
function add_shop_order_filter_by_picnic_date() {
	global $pagenow, $typenow, $wpdb;

	if ('shop_order' === $typenow && 'edit.php' === $pagenow) {

		$filter_id = 'picnic_date';
		$current = isset($_GET[$filter_id]) ? $_GET[$filter_id] : '';

		echo '<select name="' . $filter_id . '">
        <option value="">' . __('Filter by Picnic Date', 'woocommerce') . "</option>";

		$query = "SELECT picnic_date FROM " . $wpdb->prefix . "picnic_booking_history";
		$results = $wpdb->get_results($query);

		foreach ($results as $item) {

			printf('<option value="%s"%s>%s</option>', $item->picnic_date, $item->picnic_date === $current ? '" selected="selected"' : '', date('M d, Y', strtotime($item->picnic_date)));
		}
		echo '</select>';
	}
}

/* Process the filter dropdown for orders by picnic date */
add_filter('request', 'process_admin_shop_order_by_picnic_date', 99);
function process_admin_shop_order_by_picnic_date($vars) {
	global $pagenow, $typenow, $wpdb;

	$filter_id = 'picnic_date';

	if ($pagenow == 'edit.php' && 'shop_order' === $typenow
		&& isset($_GET[$filter_id]) && !empty($_GET[$filter_id])) {

		$query = "SELECT order_id FROM " . $wpdb->prefix . "picnic_booking_history WHERE picnic_date = '" . $_GET[$filter_id] . "'";
		$results = $wpdb->get_results($query);

		foreach ($results as $id) {

			$order_key = get_post_meta($id->order_id, '_order_key', true);

			$vars['meta_key'] = '_order_key';
			$vars['meta_value'] = $order_key;
			$vars['orderby'] = 'meta_value';
		}

	}
	return $vars;
}

add_action('admin_footer', 'add_custom_admin_javascript');

function add_custom_admin_javascript() {

	$timeslots = get_terms(array(
		'taxonomy' => 'pa_picnic-time-slots',
		'hide_empty' => false,
	));

	$picnic_locations = get_terms(array(
		'taxonomy' => 'pa_picnic-locations',
		'hide_empty' => false,
	));?>

<script type="text/javascript">
jQuery(document).ready(function(){
	if(jQuery('body').hasClass('toplevel_page_picnic-booking-settings')){

		var Today = new Date();

		jQuery('.date').multiDatesPicker({
			dateFormat: "dd-mm-yy",
			minDate: Today
		});
	}

	var i = jQuery('#row_count').val();
	var html = '';

	jQuery('#add_row').click(function(){

		i++;
		html += "<tr id='row_"+i+"'>";
		html += "<td>";
		html += "<div class='form-group'><label><?php _e('Select Dates', 'woocommerce');?></label><div class='input-btn-wrap' style='display: flex;'><div class='input-group' style='margin-right: 20px;'><input type='text' class='form-control date' name='booking_closing_dates_"+i+"' /><span class='input-group-addon'><span class='glyphicon glyphicon-calendar'></span></span></div>";
		html += "<button type='button' name='remove' id='"+i+"' class='btn btn-danger btn_remove'>X</button></div></div>";
		html += "<div class='form-group'>";
		html += "<label for='all_timeslots'><?php _e('Select Timeslots', 'woocommerce');?></label>";
		html +="<select class='multiselect option_values wc-enhanced-select enhanced' multiple='multiple' style='width: 100%;' id='all_timeslots_"+i+"' name='all_timeslots_"+i+"[]' data-placeholder='<?php esc_attr_e('Search for time slot', 'woocommerce');?>''>";
		html += "<?php foreach ($timeslots as $timeslot): ?><option value='<?php echo esc_attr($timeslot->term_id); ?>'><?php echo wp_kses_post($timeslot->name); ?></option><?php endforeach;?>";
		html += "<option value='all'>Whole Day</option></select></div>";
		html += "<div class='form-group'>";
		html += "<label for='all_locations'><?php _e('Select Locations', 'woocommerce');?></label>";
		html +="<select class='multiselect option_values wc-enhanced-select enhanced' multiple='multiple' style='width: 100%;' id='all_locations_"+i+"' name='all_locations"+i+"[]' data-placeholder='<?php esc_attr_e('Search for picnic locations', 'woocommerce');?>''>";
		html += "<?php foreach ($picnic_locations as $picnic_location): ?><option value='<?php echo esc_attr($picnic_location->term_id); ?>'><?php echo wp_kses_post($picnic_location->name); ?></option><?php endforeach;?>";
		html += "<option value='all'>All Locations</option></select></div>";
		html += "</td>";
		html += "</tr>";

		jQuery('.restaurant-timing-list').append(html);
		jQuery('#row_count').val(i);

		jQuery('.multiselect').select2();

		jQuery('.date').multiDatesPicker({
			dateFormat: "dd-mm-yy",
			minDate: Today
		});

	});

	jQuery(document).on('click', '.btn_remove', function(){
           var button_id = jQuery(this).attr("id");
           var current_count = parseInt(jQuery('#row_count').val());
           jQuery('#row_'+button_id+'').remove();
           jQuery('#row_count').val(current_count - 1);
      });

});
</script>
<?php
}

/* Add custom admin menu to control open/close booking */

add_action('admin_menu', 'open_close_booking_setting');
function open_close_booking_setting() {

	add_menu_page('Picnic Booking Settings', 'Picnic Booking Settings', 'manage_options', 'picnic-booking-settings', 'open_close_booking_options_page');
}

function open_close_booking_options_page() {

	if (isset($_POST['submit'])) {

		$closed_dates = array();
		$closed_timeslots = array();
		$closed_locations = array();
		$closing_meta = array();

		if (isset($_POST['row_count']) && $_POST['row_count'] != '') {
			for ($i = 1; $i <= $_POST['row_count']; $i++) {

				$closed_dates[] = $_POST['booking_closing_dates_' . $i . ''];
				$closed_timeslots[] = $_POST['all_timeslots_' . $i . ''];
				$closed_locations[] = $_POST['all_locations_' . $i . ''];
				$closing_meta[$_POST['booking_closing_dates_' . $i . '']] = array('timeslot' => $_POST['all_timeslots_' . $i . ''], 'location' => $_POST['all_locations_' . $i . '']);
			}
		}

		update_option('booking_closing_meta', $closing_meta);
		update_option('booking_closing_dates', $closed_dates);
		update_option('all_timeslots', $closed_timeslots);
		update_option('all_locations', $closed_locations);
		update_option('closed_booking_row_count', $_POST['row_count']);
	}

	$timeslots = get_terms(array(
		'taxonomy' => 'pa_picnic-time-slots',
		'hide_empty' => false,
	));

	$picnic_locations = get_terms(array(
		'taxonomy' => 'pa_picnic-locations',
		'hide_empty' => false,
	));

	$closed_booking_row_count = get_option('closed_booking_row_count');
	$closing_Dates = get_option('booking_closing_dates');
	$closing_Timeslots = get_option('all_timeslots');
	$closing_Locations = get_option('all_locations');?>

 <div class="option-form-wrapper">
  <?php screen_icon();?>
  <form method="POST" action="">
   <div class="entry-table">
   	  <h3>Picnic Booking Closing Settings</h3>
	  <table class="restaurant-timing-list">
	   	 <?php if ($closed_booking_row_count != '' && $closed_booking_row_count > 1) {?>
	   	 	<?php for ($j = 1; $j <= $closed_booking_row_count; $j++) {?>
		   	 	<tr id="row_<?php echo $j; ?>">
				    <td>
		    	   	  <div class="form-group">
		    	   	  	<label><?php _e('Select Dates', 'woocommerce');?></label>
		    	   	  	<?php if ($j > 1): ?>
		    	   	  	<div class='input-btn-wrap' style='display: flex;'>
				            <div class='input-group' style='margin-right: 20px;'>
				               <input type="text" class="form-control date" name="booking_closing_dates_<?php echo $j; ?>" value="<?php echo $closing_Dates[$j - 1]; ?>" />
				               <span class="input-group-addon">
				               		<span class="glyphicon glyphicon-calendar"></span>
				               </span>
				            </div>
				            <button type='button' name='remove' id='<?php echo $j; ?>' class='btn btn-danger btn_remove'>X</button></div></div>
				         </div>
				       <?php else: ?>
				       	<div class='input-group'>
			               <input type="text" class="form-control date" name="booking_closing_dates_<?php echo $j; ?>" value="<?php echo $closing_Dates[$j - 1]; ?>" />
			               <span class="input-group-addon">
			               		<span class="glyphicon glyphicon-calendar"></span>
			               </span>
				        </div>
				       <?php endif;?>
			         </div>
			         <div class="form-group">
			         	<label for="all_timeslots"><?php _e('Select Timeslots', 'woocommerce');?></label>
						 <select class="multiselect option_values wc-enhanced-select enhanced" multiple="multiple" style="width: 100%;" id="all_timeslots_<?php echo $j; ?>" name="all_timeslots_<?php echo $j; ?>[]" data-placeholder="<?php esc_attr_e('Search for time slot', 'woocommerce');?>">
						    <?php foreach ($timeslots as $timeslot): ?>
						    <?php if (in_array($timeslot->term_id, $closing_Timeslots[$j - 1])) {echo $selected = 'selected';} else {echo $selected = '';}?>
							<?php echo '<option value="' . esc_attr($timeslot->term_id) . '"' . $selected . '>' . wp_kses_post($timeslot->name) . '</option>'; ?>
							<?php endforeach;?>
								<option value="all" <?php if (in_array('all', $closing_Timeslots[$j - 1])) {echo "selected = 'selected'";}?>>Whole Day</option>
						</select>
						<?php echo wc_help_tip(__('Select picnic time slots for closing', 'woocommerce')); ?>
			         </div>
			          <div class="form-group">
			         	<label for="all_locations"><?php _e('Select Locations', 'woocommerce');?></label>
					    <select class="multiselect option_values wc-enhanced-select enhanced" multiple="multiple" style="width: 100%;" id="all_locations_<?php echo $j; ?>" name="all_locations_<?php echo $j; ?>[]" data-placeholder="<?php esc_attr_e('Search for picnic locations', 'woocommerce');?>">
					        <?php foreach ($picnic_locations as $picnic_location): ?>
					        	<?php if (in_array($picnic_location->term_id, $closing_Locations[$j - 1])) {echo $selected = 'selected';} else {echo $selected = '';}?>
					     		<?php echo '<option value="' . esc_attr($picnic_location->term_id) . '"' . $selected . '>' . wp_kses_post($picnic_location->name) . '</option>'; ?>
					     	<?php endforeach;?>
							<option value="all" <?php if (in_array('all', $closing_Locations[$j - 1])) {echo "selected = 'selected'";}?>>All Locations</option>
						</select>
						<?php echo wc_help_tip(__('Select picnic locations for closing', 'woocommerce')); ?>
			         </div>
				   </td>
			  	 </tr>
	   	    <?php }?>
	   	    <?php } else {?>
	   	 	<tr id="row_1">
			    <td>
	    	   	  <div class="form-group">
	    	   	  	<label><?php _e('Select Dates', 'woocommerce');?></label>
		            <div class='input-group'>
		               <input type="text" class="form-control date" name="booking_closing_dates_1" value="<?php echo $closing_Dates[0]; ?>" />
		               <span class="input-group-addon">
		               		<span class="glyphicon glyphicon-calendar"></span>
		               </span>
		            </div>
		         </div>
		         <div class="form-group">
		         	<label for="all_timeslots"><?php _e('Select Timeslots', 'woocommerce');?></label>
					 <select class="multiselect option_values wc-enhanced-select enhanced" multiple="multiple" style="width: 100%;" id="all_timeslots_1" name="all_timeslots_1[]" data-placeholder="<?php esc_attr_e('Search for time slot', 'woocommerce');?>">
					    <?php foreach ($timeslots as $timeslot): ?>
					    <?php if (in_array($timeslot->term_id, $closing_Timeslots[0])) {echo $selected = 'selected';} else {echo $selected = '';}?>
						<?php echo '<option value="' . esc_attr($timeslot->term_id) . '"' . $selected . '>' . wp_kses_post($timeslot->name) . '</option>'; ?>
						<?php endforeach;?>
							<option value="all" <?php if (in_array('all', $closing_Timeslots[0])) {echo "selected = 'selected'";}?>>Whole Day</option>
					</select>
					<?php echo wc_help_tip(__('Select picnic time slots for closing', 'woocommerce')); ?>
		         </div>
		          <div class="form-group">
		         	<label for="all_locations"><?php _e('Select Locations', 'woocommerce');?></label>
				    <select class="multiselect option_values wc-enhanced-select enhanced" multiple="multiple" style="width: 100%;" id="all_locations_1" name="all_locations_1[]" data-placeholder="<?php esc_attr_e('Search for picnic locations', 'woocommerce');?>">
				        <?php foreach ($picnic_locations as $picnic_location): ?>
				        	<?php if (in_array($picnic_location->term_id, $closing_Locations[0])) {echo $selected = 'selected';} else {echo $selected = '';}?>
				     		<?php echo '<option value="' . esc_attr($picnic_location->term_id) . '"' . $selected . '>' . wp_kses_post($picnic_location->name) . '</option>'; ?>
				     	<?php endforeach;?>
						<option value="all" <?php if (in_array('all', $closing_Locations[0])) {echo "selected = 'selected'";}?>>All Locations</option>
					</select>
					<?php echo wc_help_tip(__('Select picnic locations for closing', 'woocommerce')); ?>
		         </div>
			   </td>
		  	 </tr>
	   	 <?php }?>
	  </table>
	  <input type="hidden" name="row_count" id="row_count" value="<?php if ($closed_booking_row_count != '') {echo $closed_booking_row_count;} else {echo '1';}?>">
	 <p><a href="javascript:void(0)" type="button" id="add_row" class="button button-primary">Add New</a></p>
  </div>
  <?php submit_button();?>
  </form>
 </div>

<?php }

/* add addons in cart directly for picnic */
add_action('wp_ajax_add_addons_in_cart', 'process_add_addons_in_cart');
add_action('wp_ajax_nopriv_add_addons_in_cart', 'process_add_addons_in_cart');

function process_add_addons_in_cart() {

	$response = array();
	$type = $_POST['type'];
	$addon_id = $_POST['addon_id'];
	$addon_qty = $_POST['addon_qty'];
	$addon_exists = 0;

	if (WC()->cart->get_cart_contents_count() == 0) {

		$response['result'] = "error";

	} else {

		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

			$product_id = $cart_item['product_id'];
			$term_list = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));

			if (in_array('food-style', $term_list)) {

				$item_selected_addons = $cart_item["selected_addons"];

				if (!empty($item_selected_addons)) {

					$ele_count = count($item_selected_addons);

					for ($i = 0; $i <= $ele_count; $i++) {

						if (in_array($addon_id, $item_selected_addons[$i])) {

							$item_selected_addons[$i]['addon_qty'] = $item_selected_addons[$i]['addon_qty'] + $addon_qty;
							$addon_exists = 1;
						}
					}

					if ($addon_exists != 1) {

						$add_addon[] = array('addon_id' => $addon_id, 'addon_qty' => $addon_qty);
						$new_addons_selection = array_merge($item_selected_addons, $add_addon);

					} else {
						$new_addons_selection = $item_selected_addons;
					}

				} else {

					$new_addons_selection[] = array('addon_id' => $addon_id, 'addon_qty' => $addon_qty);
				}

				$cart_item['selected_addons'] = $new_addons_selection;
				WC()->cart->cart_contents[$cart_item_key] = $cart_item;
				WC()->cart->set_session();

				$response['result'] = "success";

			} else {

				$response['result'] = "error";
			}
		}
	}

	if ($response['result'] == 'success') {

		WC_AJAX::get_refreshed_fragments();

	} else {

		$error_msg = 'Please add a picnic first. <br/><a href="' . esc_url(home_url('/book-now')) . '" style="color: #a94442;font-weight: 500;">Click here</a> to add a picnic';
		$response['err_msg'] = $error_msg;
		echo json_encode($response);
	}

	wp_die();
}

add_action( 'woocommerce_admin_order_totals_after_discount', 'wp_add_main_order_total', 10, 1);
function wp_add_main_order_total( $order_id ) {
	   
	$order = wc_get_order( $order_id );
	$order_total_price = 0;

	foreach ( $order->get_items() as $item_id => $item ) {
		
		$addons_price = 0;
		$order_item_price = 0;
		
		$product = $item->get_product();
		$product_price = $product->get_price();
		$quantity = $item->get_quantity();

		$picnic_info = wc_get_order_item_meta($item_id, '_order_custom_picnic_data', true);

		if (isset($picnic_info['num_persons']) && $picnic_info['num_persons'] != '') {

			$all_persons_price = $picnic_info['num_persons'] * $product_price;
			$order_item_price += $all_persons_price;

		} else {

			$order_item_price = $product_price;
		}

		if (isset($picnic_info['selected_addons']) && $picnic_info['selected_addons'] != '') {

			$added_addons = $picnic_info['selected_addons'];

			foreach ($added_addons as $addon) {
				
				$addon_item = wc_get_product($addon['addon_id']);

				$addon_amount = ($addon_item->get_price() * $addon['addon_qty']);
				$addons_price += $addon_amount;
			}
		}

		$order_total_price += (($order_item_price + $addons_price) * $quantity);	
	}
?>
	<tr>
		<td class="label">Original Total:</td>
		<td width="1%"></td>
		<td class="total"><?php echo wc_price($order_total_price);?></td>
	</tr>
<?php
}