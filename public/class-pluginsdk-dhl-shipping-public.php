<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://plugins.dk/
 * @since      1.0.0
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/public
 * @author     Plugins.dk <support@plugins.dk>
 */
class Pluginsdk_Dhl_Shipping_Public
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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
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
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/pluginsdk-dhl-shipping-public.css', array(), $this->version, 'all');

        // Leaflet css
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.css');
        // Leaflet awesome markers library css
        wp_enqueue_style('leaflet-awesome-markers-css', 'https://cdn.jsdelivr.net/gh/lvoogdt/Leaflet.awesome-markers@2.0.2/dist/leaflet.awesome-markers.css');
        // Font awesome icon library css
        wp_enqueue_style('font-awesome-css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
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
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/pluginsdk-dhl-shipping-public.js', array('jquery'), $this->version, false);
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js', NULL, '1.9.3', true);
        // Leaflet awesome markers library js
        wp_enqueue_script('leaflet-awesome-markers-js', 'https://cdn.jsdelivr.net/gh/lvoogdt/Leaflet.awesome-markers@2.0.2/dist/leaflet.awesome-markers.js', NULL, '2.0.2', true);


        wp_enqueue_script('ajax-shops', plugins_url('/js/ajax-shops.js', __FILE__), /*array('jquery')*/['jquery' /*,'jquery-ui-selectable'*/], '1.0', true);
        // Localize scripts
        wp_localize_script(
            'ajax-shops',
            'ajax_object',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('javascript_nonce')
            ]
        );
    }




    /**
     * Fetching data from our endpoint
     *
     * @access private
     * @param string $zipcode
     * @return void
     */
    
    private function fetch_pickup_points($zipcode, $radius, $contryCode)
    {
        $mydhl_api_key = get_option('dhl_settings')['DHL_API_KEY'];

        $endpoint = "https://api.dhl.com/location-finder/v1/find-by-address?countryCode=" . $contryCode . "&postalCode=" . $zipcode . "&radius=" . $radius;

        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 10,
                'headers' => array(
                    'DHL-API-KEY' => $mydhl_api_key
                )
            )
        );

        if (is_wp_error($response)) {
            error_log("DHL API Error: " . $response->get_error_message());
            return $response;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log("DHL API Error: Unexpected response code " . wp_remote_retrieve_response_code($response));
            return new WP_Error();
        }

        $body = wp_remote_retrieve_body($response); // retrieving body out of our response
        $pakkeshop_data = json_decode($body); // converting a string to JSON

        return $pakkeshop_data;
    }

    public function retrieve_parcelshops()
    {
        $zipcode = $_GET['zipcode']; // assigning our postal code
        $radius = $_GET['radius']; // assigning our radius
        $countryCode = $_GET['countryCode']; // assigning our country code
        $response['status'] = true;

        if (!isset($zipcode) || empty($zipcode) || !ctype_digit($zipcode)) {
            $response['status'] = false;
            $response['error_zipcode'] = __('You must enter a zipcode in order to see the nearest pickup points, please try again', 'pluginsdk-dhl-shipping');
            wp_send_json($response);
        }

        $json_data = $this->fetch_pickup_points($zipcode, $radius, $countryCode);
        if (is_wp_error($json_data)) {
            $response['status'] = false;
            $response['return_error'] = __('Something went wrong. Please try again', 'pluginsdk-dhl-shipping');
            $response['error_details'] = $json_data->get_error_message(); // Log the error details
        } else {
            // Check if the expected property exists in the API response
            if (isset($json_data->locations)) {
                $response['locations'] = $json_data->locations;
            } else {
                $response['status'] = false;
                $response['return_error'] = __('Unexpected API response format. Please try again', 'pluginsdk-dhl-shipping');
            }
        }

        $response['zipcode'] = $zipcode;
        $response['radius'] = $radius;
        $response['countryCode'] = $countryCode;
        $response['body'] = $json_data;

        wp_send_json($response);
    }


    //Updates the order with the shipping information of the chosen shop
    public function pluginsdk_dhl_shipping_update_order_meta_method($order_id)
    {
        //matches digits and replaces with empty string in array chosen_shipping_method [0]
        global $woocommerce;

        $chosen_shipping_method1 = preg_replace('/\d/', '', $woocommerce->session->chosen_shipping_methods[0]);
        $chosen_shipping_method2 = preg_replace('/\d/', '', $woocommerce->session->chosen_shipping_methods[1]);

        //checks if billig_first_name is set, not null, if true assigns to variable
        $first_name = isset($_POST['billing_first_name']) ? $_POST['billing_first_name'] : '';
        $last_name = isset($_POST['billing_last_name']) ? $_POST['billing_last_name'] : '';

        $prefix = '';

        //pickup delivery
        if (strpos($chosen_shipping_method1, 'dhl_pickup_method') !== false) {
            $prefix = 'shipping_parcelshop';
        }
        //home delivery
        elseif (strpos($chosen_shipping_method2, 'dhl_private_method') !== false) {
            $prefix = 'billing';
        }

        if ($prefix !== '') {
            $this->updateShippingMeta($order_id, $first_name, $last_name, $prefix);
        }
    }

    private function updateShippingMeta($order_id, $first_name, $last_name, $prefix)
    {
        $companyfield = isset($_POST[$prefix . '_name_dhl']) ? $_POST[$prefix . '_name_dhl'] : '';
        $address = isset($_POST[$prefix . '_adress_dhl']) ? $_POST[$prefix . '_adress_dhl'] : '';
        $postal_code = isset($_POST[$prefix . '_zipcode_dhl']) ? $_POST[$prefix . '_zipcode_dhl'] : '';
        $city = isset($_POST[$prefix . '_city_dhl']) ? $_POST[$prefix . '_city_dhl'] : '';

        if ($prefix === 'billing') {

            update_post_meta($order_id, '_billing_first_name', $first_name);
            update_post_meta($order_id, '_billing_last_name', $last_name);
            update_post_meta($order_id, '_shipping_company', $companyfield);
            update_post_meta($order_id, '_shipping_address_1', $address);
            update_post_meta($order_id, '_shipping_address_2', '');
            update_post_meta($order_id, '_shipping_postcode', $postal_code);
            update_post_meta($order_id, '_shipping_city', $city);
        } else if ($prefix === 'shipping_parcelshop') {

            update_post_meta($order_id, '_shipping_first_name', '');
            update_post_meta($order_id, '_shipping_last_name', '');
            update_post_meta($order_id, '_shipping_company', $companyfield);
            update_post_meta($order_id, '_shipping_address_1', $address);
            update_post_meta($order_id, '_shipping_address_2', '');
            update_post_meta($order_id, '_shipping_postcode', $postal_code);
            update_post_meta($order_id, '_shipping_city', $city);
        }
    }



    private function validate_pickup_shop_selection()
    {
        global $woocommerce;

        $chosen_shipping_methods = $woocommerce->session->get('chosen_shipping_methods');
        $chosen_shipping_method1 = isset($chosen_shipping_methods[0]) ? $chosen_shipping_methods[0] : '';


        // Check if a pickup method is chosen
        if (strpos($chosen_shipping_method1, 'dhl_pickup_method') !== false) {
            $selected_shop = isset($_POST['shipping_parcelshop_name_dhl']) ? $_POST['shipping_parcelshop_name_dhl'] : '';
        }

        if (empty($selected_shop)) {
            wc_add_notice(__('Please select a pickup shop before placing the order.', 'your-text-domain'), 'error');
        }
    }

}

