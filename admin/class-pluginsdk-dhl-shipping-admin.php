<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://plugins.dk/
 * @since      1.0.0
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/admin
 * @author     Plugins.dk <support@plugins.dk>
 */
class Pluginsdk_Dhl_Shipping_Admin
{



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
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Hook enqueue scripts method to admin enqueue scripts action
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

		// AJAX actions for generating PDF
		add_action('wp_ajax_pluginsdk_dhl_shipping_ajax_generate_pdf', array($this, 'pluginsdk_dhl_shipping_ajax_generate_pdf'));
		add_action('wp_ajax_nopriv_pluginsdk_dhl_shipping_ajax_generate_pdf', array($this, 'pluginsdk_dhl_shipping_ajax_generate_pdf'));

		// AJAX actions for generating return label
		add_action('wp_ajax_pluginsdk_dhl_shipping_ajax_generate_return_label', array($this, 'pluginsdk_dhl_shipping_ajax_generate_return_label'));
		add_action('wp_ajax_nopriv_pluginsdk_dhl_shipping_ajax_generate_return_label', array($this, 'pluginsdk_dhl_shipping_ajax_generate_return_label'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pluginsdk_Dhl_Shipping_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pluginsdk_Dhl_Shipping_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/pluginsdk-dhl-shipping-admin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		if (is_admin()) {
			wp_enqueue_script('jquery');

			wp_enqueue_script('sidebar', plugin_dir_url(__FILE__) . 'js/pluginsdk-dhl-shipping-admin.js', array('jquery'), $this->version, false);
			wp_enqueue_script('generate-pdf', plugin_dir_url(__FILE__) . 'js/generate-pdf.js', array('jquery'), $this->version, true);
			wp_enqueue_script('generate_return_label', plugin_dir_url(__FILE__) . 'js/generate_return_label.js', array('jquery'), $this->version, true);

			wp_localize_script(
				'sidebar',
				'pluginsdk_dhl_shipping_data',
				[
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('pluginsdk_dhl_shipping_order_nonce')
				]
			);
		}
	}





	// Add a menu item to the Wordpress admin menu
	public function dhl_settings_menu()
	{
		add_menu_page(
			'Dhl Settings', //page title
			'Dhl Menu', //menu title
			'manage_options', //capability required to access the page
			'pluginsdk_dhl_shipping_settings', //menu slug
			array($this, 'dhl_settings_page') // callback function to render the page
		);
	}

	// Render the settings page
	function dhl_settings_page()
	{
		$options = get_option('dhl_settings');

		?>
		<div class="wrap">
			<h2>
				<?php echo esc_html__('Plugins.dk Dhl Settings', 'pluginsdk_dhl_shipping'); ?>
			</h2>
			<?php settings_errors(); ?>

			<?php
			$active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dhl_general';
			?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=pluginsdk_dhl_shipping_settings&tab=dhl_general"
					class="nav-tab <?php echo $active_tab === 'dhl_general' ? 'nav-tab-active' : ''; ?>">General</a>
				<a href="?page=pluginsdk_dhl_shipping_settings&tab=dhl_settings"
					class="nav-tab <?php echo $active_tab === 'dhl_settings' ? 'nav-tab-active' : ''; ?>">Dhl</a>
			</h2>
			<?php
			if ($active_tab === 'dhl_general') {
				settings_fields('dhl_general_group');
				do_settings_sections('dhl_general_group');
			} else {
				?>
				<div class="wrap">
					<form method="post" action="options.php">
						<?php
						settings_fields('dhl_settings_group');
						do_settings_sections('dhl_settings_page');
						?>

						<h3>DHL Settings</h3>
						<table class="form-table">
							<tr>
								<th style="width:100px;"><label for="pluginsdk_dhl_api_key">
										<?php echo esc_html__('DHL-API-KEY:', 'pluginsdk_dhl_shipping'); ?>
									</label></th>
								<td><input type="text" id="pluginsdk_dhl_api_key" name="dhl_settings[DHL_API_KEY]"
										value="<?php echo esc_attr($options['DHL_API_KEY'] ?? ''); ?>"></td>
							</tr>
						</table>
						<?php submit_button(); ?>
					</form>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	public function dhl_register_settings()
	{
		register_setting('dhl_general_group', 'dhl_general');

		add_settings_section(
			'dhl_general',
			__('General Indstillinger', 'pluginsdk_dhl_shipping'),
			array($this, 'general_settings_section_callback'),
			'dhl_general_group'
		);

		add_settings_field(
			'DHL_API_KEY',
			'Dhl Api Key',
			array($this, 'dhl_api_key_callback'),
			'dhl_settings_page',
			'dhl_settings_section'
		);

		register_setting(
			'dhl_settings_group',
			'dhl_settings',
			'dhl_sanitize_settings'
		);
	}

	function dhl_api_key_callback()
	{
		$options = get_option('dhl_settings');
		?>
		<input type="text" name="dhl_settings[DHL_API_KEY]" value="<?php echo esc_attr($options['dhl_api_key'] ?? ''); ?>">
		<?php
	}

	function general_settings_section_callback()
	{
		echo 'hej, denne side bruges til at lave generelle indstillinger... hvis der er nogle';
	}

	/**
	 * 
	 * meta box shipping label
	 * 
	 */
	function pluginsdk_dhl_shipping_dhl_order_label()
	{
		add_meta_box(
			'pluginsdk_dhl_shipping_label_sidebar', //unique id for this meta_box
			'Shipping Label', //title for the meta_box
			array($this, 'pluginsdk_dhl_shipping_dhl_order_label_callback'), //callback for HTML to be renderes inside the meta_box
			'shop_order', //$screen
			'side' //position on the page
		);
	}


	function pluginsdk_dhl_shipping_dhl_order_label_callback($post)
	{
		$id = $post->ID;
		$order = new WC_Order($post->ID);
		$order_shipping = $order->get_shipping_method();

		if (str_contains($order_shipping, "Dhl")) {
			$output = <<<HTML
			<p>Post ID: #<span id="order-id">$id</span><br></p>
			<form id="dhl-label-form">
				<p class="label-meta-box">Parcel dimensions (cm):</p>
				<div id="parcel-dimensions">
					<div class="dimension-unit">
						<label for="parcel-length">Length:</label>
						<input class="parcel-dimension" id="parcel-length" type="number" min="1" max="100" value="1" name="parcel-length"><br>
					</div>
					<div class="dimension-unit">
						<label for="parcel-width">Width:</label>
						<input class="parcel-dimension" id="parcel-width" type="number" min="1" max="100" value="1" name="parcel-width"><br>
					</div>
					<div class="dimension-unit">
						<label for="parcel-height">Height:</label>
						<input class="parcel-dimension" id="parcel-height" type="number" min="1" max="100" value="1" name="parcel-height"><br>
					</div>
				</div>
				<p class="label-meta-box">Parcel weight (kilograms):</p>
				<div id="parcel-weight-input">
					<div class="weight-unit">
						<label for="parcel-weight"></label>
						<input id="parcel-weight" name="parcel-weight" type="number" min="1" value="1" max="20"><br>
					</div>
				</div>
				<div id="dhl-generate-labels">
					<button id="generate-shipping-label">Generate Shipping Label ↻</button>
					<button id="generate-return-label-button">Generate Return Label</button>
				</div>
			</form>
	HTML;

			echo $output;
		}
	}



	/**
	 * Get order data for a given order.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array Order data.
	 */
	private function get_order_data($order)
	{
		$order_data = array();

		$order_data['shipping_company'] = $order->get_shipping_company();
		$order_data['shipping_address'] = $order->get_shipping_address_1();
		$order_data['shipping_city'] = $order->get_shipping_city();
		$order_data['shipping_postcode'] = $order->get_shipping_postcode();
		$order_data['shipping_country'] = $order->get_shipping_country();

		$order_data['order_id'] = $order->get_id();
		$order_data['customer_name'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$order_data['customer_address'] = $order->get_billing_address_1();
		$order_data['customer_city'] = $order->get_billing_city();
		$order_data['customer_postcode'] = $order->get_billing_postcode();
		$order_data['customer_country'] = $order->get_billing_country();
		$order_data['customer_phone'] = $order->get_billing_phone();

		return $order_data;
	}


	/**
	 * 
	 * AJAX GENERATE PDF
	 * 
	 */

	public function pluginsdk_dhl_shipping_ajax_generate_pdf()
	{
		check_ajax_referer('pluginsdk_dhl_shipping_order_nonce', 'security');

		$order_id = sanitize_text_field($_POST['order_id']);
		$order = new WC_Order($order_id);
		$order_shipping = $order->get_shipping_method();

		if (str_contains($order_shipping, "Dhl")) {
			$order_data = $this->get_order_data($order);
			$shipping_method = $order_shipping;
			$length = sanitize_text_field($_POST['length']);
			$width = sanitize_text_field($_POST['width']);
			$height = sanitize_text_field($_POST['height']);
			$weight = sanitize_text_field($_POST['weight']);

			$pdf_content = $this->generate_shipping_label_pdf($order_data, $shipping_method, $length, $width, $height, $weight);

			error_log($pdf_content);

			if ($pdf_content) {
				
				header('Content-Type: application/json');

				// Return the PDF URL in the response
				echo json_encode(array('success' => true, 'pdf_url' => plugins_url('pdf/shipping-label-' . $order_data['order_id'] . '.pdf', __FILE__)));
			} else {
				echo json_encode(array('success' => false, 'message' => 'Failed to generate PDF.'));
			}

		} else {
			echo json_encode(array('success' => false, 'message' => 'Invalid shipping method.'));
		}

		wp_die();
	}

	/**
	 * Generate the shipping label PDF.
	 *
	 * @param array  $order_data      Order data containing customer information.
	 * @param string $shipping_method Chosen shipping method.
	 * @return string|bool Path to the generated PDF on success, false on failure.
	 */


	private function generate_shipping_label_pdf($order_data, $shipping_method, $length, $width, $height, $weight)
	{
		// Initialize dompdf
		$options = new Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isPhpEnabled', true);

		$dompdf = new Dompdf($options);

		$html = $this->generate_shipping_label_html($order_data, $shipping_method, $length, $width, $height, $weight);

		$dompdf->loadHtml($html);
		
		$dompdf->setPaper('A5', 'portrait');
		
		$dompdf->render();

		$pdfPath = plugin_dir_path(__FILE__) . 'pdf/shipping-label-' . $order_data['order_id'] . '.pdf';

		file_put_contents($pdfPath, $dompdf->output());

		return $pdfPath;
	}


	/**
	 * Generate the HTML for the DHL-style shipping label.
	 *
	 * @param array  $order_data      Order data containing customer information.
	 * @param string $shipping_method Chosen shipping method.
	 * @param int    $length           Parcel length.
	 * @param int    $width            Parcel width.
	 * @param int    $height           Parcel height.
	 * @param float  $weight           Parcel weight.
	 * @return string HTML content for the DHL-style shipping label.
	 */
	private function generate_shipping_label_html($order_data, $shipping_method, $length, $width, $height, $weight)
	{


		$html = '<div style="border: 2px solid #FFD200; padding: 20px; width: 400px; margin: 20px; font-family: Arial, sans-serif; background-color: #FFFFFF; color: #000000;">';

		$html .= '<h2 style="color: #FFD200; margin-bottom: 10px;">Shipping Label</h2>';

		// Shipping address
		$html .= '<p><strong>Shipping Address:</strong> </br>' . $order_data['shipping_company'] . '</br>' . $order_data['shipping_address']
		. '</br>' . $order_data['shipping_city'] . '</br>' . $order_data['shipping_country'] . '</br>' . $order_data['shipping_postcode'] . '</p>';

		// Customer details
		$html .= '<p><strong>Modtager:</strong> ' . $order_data['customer_name'] . '</br>' . $order_data['customer_address'] 
		. '</br>' . $order_data['customer_city'] . '</br>' . $order_data['customer_country'] . '</br>' . $order_data['customer_postcode'] . '</p>';

		// Order details
		$html .= '<p><strong>Order ID:</strong> ' . $order_data['order_id'] . '</p>';

		// Shipping method
		$html .= '<p><strong>Shipping Method:</strong> ' . $shipping_method . '</p>';

		// Parcel details
		$html .= '<p><strong>Parcel Dimensions:</strong> ' . $length . 'cm x ' . $width . 'cm x ' . $height . 'cm</p>';
		$html .= '<p><strong>Parcel Weight:</strong> ' . $weight . 'kg</p>';

		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}


	/**
	 * 
	 * AJAX GENERATE RETURN LABEL
	 * 
	 */
	public function pluginsdk_dhl_shipping_ajax_generate_return_label()
	{
		check_ajax_referer('pluginsdk_dhl_shipping_order_nonce', 'security');

		$order_id = sanitize_text_field($_POST['order_id']);
		$order = new WC_Order($order_id);



		$order_data = $this->get_order_data($order);

		// Generate return label PDF
		$pdf_content = $this->generate_return_label_pdf($order_data);

		error_log($pdf_content);

		if ($pdf_content) {
			header('Content-Type: application/json');

			echo json_encode(array('success' => true, 'pdf_url' => plugins_url('pdf/return-label-' . $order_data['order_id'] . '.pdf', __FILE__)));
		} else {
			echo json_encode(array('success' => false, 'message' => 'Failed to generate return label PDF.'));
		}
		wp_die();
	}

	/**
	 * Generate the return label PDF.
	 *
	 * @param array $order_data Order data containing customer information.
	 * @return string|bool Path to the generated PDF on success, false on failure.
	 */
	private function generate_return_label_pdf($order_data)
	{
		// Initialize dompdf
		$options = new Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isPhpEnabled', true);

		$dompdf = new Dompdf($options);

		// Generate HTML for return label
		$html = $this->generate_return_label_html($order_data);

		// Load HTML content into dompdf
		$dompdf->loadHtml($html);

		// Set paper size (optional)
		$dompdf->setPaper('A5', 'portrait');

		// Render the HTML as PDF
		$dompdf->render();

		// Save the PDF to a file
		$pdfPath = plugin_dir_path(__FILE__) . 'pdf/return-label-' . $order_data['order_id'] . '.pdf';

		file_put_contents($pdfPath, $dompdf->output());

		return $pdfPath;
	}

	/**
	 * Generate the HTML for the return label.
	 *
	 * @param array $order_data Order data containing customer information.
	 * @return string HTML content for the return label.
	 */
	private function generate_return_label_html($order_data)
	{
		$html = '<div style="border: 2px solid #FFD200; padding: 20px; width: 400px; font: size 6px; margin: 20px; font-family: Arial, sans-serif; background-color: #FFFFFF; color: #000000;">';

		$html .= '<h2 style="color: #FFD200; margin-bottom: 10px;">Return Label</h2>';

		// Return address (hardcoded random address)
		$return_address = array(
			'Plugins.dk',
			'Herstedvang 7 C 1 th',
			'2620 Albertslund'
		);

		$html .= '<p><strong>Sender:</strong> ' . $order_data['customer_name'] . '</br>' . $order_data['customer_address'] . '</br>' . $order_data['customer_city'] . $order_data['customer_postcode'] . '</p>';
		$html .= '<p><strong>Return Address:</strong> </br>' . implode('</br>', $return_address) . '</p>';



		// Order details
		$html .= '<p><strong>Order ID:</strong> ' . $order_data['order_id'] . '</p>';

		$html .= '</div>';

		return $html;
	}




	// ------ TRACK & TRACE ------ //

	function pluginsdk_dhl_shipping_dhl_order_track_and_trace()
	{
		add_meta_box(
			'pluginsdk_dhl_shipping_tracktrace_sidebar', // unique ID for this meta_box (TODO: pluginsdk_dhl_shipping plugin-name)
			'Tracking', // Title for meta_box
			array($this, 'pluginsdk_dhl_shipping_dhl_order_track_and_trace_callback'), // Callback for HTML to be rendered inside the box
			'shop_order', // $screen
			'side' // Position on the page
		);
	}

	function pluginsdk_dhl_shipping_dhl_order_track_and_trace_callback($post)
	{
		$id = $post->ID;
		$tnt = get_post_meta($id, '_tracking', true);
		$order = new WC_Order($post->ID);
		$order_shipping = $order->get_shipping_method();

		if (str_contains($order_shipping, "Dhl")) {
			?>
			<b><u>Dispatch:</u></b>
			<p hidden>
				<?= $tnt ?>
			</p>
			<p><a id='tracking' class='tracking' href='https://www.dhl.com/dk-da/home/tracking.html/<?= $tnt . "'" ?>target="_blank"><?= $tnt ?></a></p>

		<?php
				$tnt_return = get_post_meta($id, '_tracking_return', true);
				?>

		<b><u>Return:</u></b>
		<p hidden><?= $tnt_return ?></p>
		<p><a id="tracking-return" class="tracking-return" href="https://www.dhl.com/dk-da/home/tracking.html<?= $tnt_return . '"' ?>target="_blank"><?= $tnt_return ?></a></p>
		<?php
		}
	}

}
?>