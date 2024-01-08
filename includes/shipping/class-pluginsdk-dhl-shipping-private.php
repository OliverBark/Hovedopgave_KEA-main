<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Initialize DHL Private method in Woocommerce
 *
 * @access public
 * @return void
 */
function dhl_private_method_init() {
    if ( ! class_exists( 'WC_Dhl_Private_Method' ) ) {

        class WC_Dhl_Private_Method extends WC_Shipping_Method {

            /**
             * Min amount to be valid.
             *
             * @var integer
             */
            public $min_amount = 0;

            /**
             * Requires option.
             *
             * @var string
             */
            public $requires = '';

            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct( $instance_id = 0 ) {
                $this->id                 = 'dhl_private_method'; // Id for your shipping method. Should be unique.
                $this->instance_id        = absint( $instance_id );
                $this->method_title       = __( 'Dhl Home Delivery Parcel', 'dhl_private_method' );  // Title shown in admin
                $this->method_description = __( 'Adds the option to ship with Dhl Home Delivery Parcel to the checkout', 'dhl_private_method' ); // Description shown in admin
                $this->settings['enabled'] = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                $this->title              = 'Dhl Home Delivery Parcel';

                $this->supports = array( // makes our shipping method selectable in shipping zone
                    'shipping-zones',
                    'instance-settings',
                );
                $this->init();
            }

            function init() {
                // Load the settings API
                $this->init_form_fields();
                $this->init_settings(); // new

                // Define user set variables. All new
                $this->title            = $this->get_option( 'title' );
                $this->min_amount       = $this->get_option( 'min_amount', 0 );
                $this->requires         = $this->get_option( 'requires' );
                $this->ignore_discounts = $this->get_option( 'ignore_discounts' );
                $this->enable_free_shipping = $this->get_option( 'enable_free_shipping' );
                $this->free_shipping_total = $this->get_option( 'free_shipping_total' );
                // Actions.
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                //add_filter('woocommerce_checkout_get_value','__return_empty_string',10);// NEW!
                add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false', 99 ); // send to different address is off
                add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'pluginsdk_dhl_shipping_checkout_update_order_meta_dhl_method_private' ) );
            }

            /**
             * Get setting form fields for instances of this shipping method within zones.
             *
             * @return array
             */
            public function get_instance_form_fields() {
                return parent::get_instance_form_fields();
            }

            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping( $package = array() ) {
                global $woocommerce;
                $cart_total = $woocommerce->cart->cart_contents_total;

                // Enable "free shipping" will overrule any other
                if ( $this->enable_free_shipping === 'Yes' && $cart_total >= $this->free_shipping_total ) {
                    $cost = 0;
                } else {
                    $cost = $this->get_option( 'shipping_price' );
                }

                $rate = array(
                    'label'    => $this->title,
                    'cost'     => "{$cost}",
                    'calc_tax' => 'per_item',
                );

                // Register the rate
                $this->add_rate( $rate );
            }

            /**
             * init_form_fields function.
             *
             * @access public
             * @return void
             */
            function init_form_fields() {
                // get currency symbol out of Woocommerce object in session
                $currency = get_woocommerce_currency_symbol();

                $this->instance_form_fields = array(
                    'title'                  => array(
                        'title'       => __( 'Title', 'dhl_private_method' ),
                        'type'        => 'text',
                        'description' => __( 'Title to be displayed on site', 'dhl_private_method' ),
                        'default'     => __( 'Dhl Home Delivery Parcel', 'dhl_private_method' ),
                    ),
                    'enable_free_shipping'   => array(
                        'title'   => __( 'Enable free shipping', 'dhl_private_method' ),
                        'type'    => 'select',
                        // 'class'   => 'pluginsdk-shipping-select-enable',
                        'default' => 'taxable',
                        'options' => array(
                            'No'  => __( 'No', 'dhl_private_method' ),
                            'Yes' => __( 'Yes', 'dhl_private_method' ),
                        ),
                    ),
                    'free_shipping_total'    => array(
                        'title'       => __( 'Minimum price for free shipping, ' . $currency, 'dhl_private_method' ),
                        'type'        => 'number',
                        // 'class'       => 'pluginsdk-shipping-minimum-price',
                        'description' => __( 'If free shipping has been enabled, this will control the minimum amount that the customer will have to spend for free shipping', 'dhl_private_method' ),
                        'default'     => 0,
                        'desc_tip'    => true,
                    ),
                    'shipping_price'         => array(
                        'title'       => __( 'Shipping Price, ' . $currency, 'dhl_private_method' ),
                        'type'        => 'number',
                        'description' => __( 'This controls what the customer will have to pay for this shipping method', 'dhl_private_method' ),
                        'class'       => 'shipping_price',
                        'default'     => 50,
                        'desc_tip'    => true,
                    ),
                );
            }

            /**
             * function
             *
             * @access public
             * @return void
             */
            public function pluginsdk_dhl_shipping_checkout_update_order_meta_dhl_method_private( $order_id ) {
                // phpcs:disable WordPress.Security.NonceVerification
                global $woocommerce;
                $chosen_shipping_method1 = preg_replace( '/\d/', '', $woocommerce->session->chosen_shipping_methods[0] );
                $chosen_shipping_method2 = preg_replace( '/\d/', '', $woocommerce->session->chosen_shipping_methods );
                if ( $chosen_shipping_method1 === 'dhl_private_method' || $chosen_shipping_method2 === 'dhl_private_method' ) {
                    if ( $_POST['billing_address_1'] && ! $_POST['shipping_address_1'] ) {
                        // add_filter('woocommerce_ship_to_different_address_checked', '__return_false', 99);// new
                        update_post_meta( $order_id, '_shipping_address_1', $_POST['billing_address_1'] );
                        update_post_meta( $order_id, '_shipping_city', $_POST['billing_city'] );
                        update_post_meta( $order_id, '_shipping_postcode', $_POST['billing_postcode'] );
                    } elseif ( $_POST['shipping_address_1'] ) {
                        // add_filter('woocommerce_ship_to_different_address_checked', '__return_false', 99);// new
                        update_post_meta( $order_id, '_shipping_address_1', $_POST['shipping_address_1'] );
                        update_post_meta( $order_id, '_shipping_city', $_POST['shipping_city'] );
                        update_post_meta( $order_id, '_shipping_postcode', $_POST['shipping_postcode'] );
                    }
                }
            }
        } // class description ends
    }
}

add_action( 'woocommerce_shipping_init', 'dhl_private_method_init' );

function add_dhl_private_method( $methods ) {
    $methods['dhl_private_method'] = 'WC_Dhl_Private_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_dhl_private_method' );
