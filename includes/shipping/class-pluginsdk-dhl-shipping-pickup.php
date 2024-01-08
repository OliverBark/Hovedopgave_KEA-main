<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Initialize DHL Pickup method in Woocommerce
 *
 * @access public
 * @return void
 */
function dhl_pickup_method_init() {
    if ( ! class_exists( 'WC_Dhl_Pickup_Method' ) ) {

        class WC_Dhl_Pickup_Method extends WC_Shipping_Method {

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
                $this->id                 = 'dhl_pickup_method'; // Id for your shipping method. Should be unique.
                $this->instance_id        = absint( $instance_id );
                $this->method_title       = __( 'Dhl PickUp Parcel', 'dhl_pickup_method' );  // Title shown in admin
                $this->method_description = __( 'Dhl pickup method, which allows the customer to order shipping to nearest pickup point', 'dhl_pickup_method' ); // Description shown in admin
                $this->settings['enabled'] = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                $this->title              = 'Dhl PickUp Parcel';

                $this->supports = array( // makes our shipping method selectable in shipping zone
                    'shipping-zones',
                    'instance-settings',
                );

                $this->init();
            }

            /**
             * Init form fields
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings API
                $this->init_form_fields();
                $this->init_settings(); // new

                // Define user set variables. All new
                $this->title               = $this->get_option( 'title' );
                $this->min_amount          = $this->get_option( 'min_amount', 0 );
                $this->requires            = $this->get_option( 'requires' );
                $this->ignore_discounts    = $this->get_option( 'ignore_discounts' );
                $this->enable_free_shipping = $this->get_option( 'enable_free_shipping' );
                $this->free_shipping_total = $this->get_option( 'free_shipping_total' );

                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
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
             * Calculate shipping function.
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
             * Init form fields function.
             *
             * @access public
             * @return void
             */
            function init_form_fields() {
                // get currency symbol out of Woocommerce object in session
                $currency = get_woocommerce_currency_symbol();

                $this->instance_form_fields = array(
                    'title'                  => array(
                        'title'       => __( 'Title', 'dhl_pickup_method' ),
                        'type'        => 'text',
                        'description' => __( 'Title to be displayed on site', 'dhl_pickup_method' ),
                        'default'     => __( 'Dhl PickUp Parcel', 'dhl_pickup_method' ),
                        'desc_tip'    => true,
                    ),
                    'enable_free_shipping'   => array(
                        'title'   => __( 'Enable free shipping', 'dhl_pickup_method' ),
                        'type'    => 'select',
                        'options' => array(
                            'No'  => __( 'No', 'dhl_pickup_method' ),
                            'Yes' => __( 'Yes', 'dhl_pickup_method' ),
                        ),
                    ),
                    'free_shipping_total'    => array(
                        'title'       => __( 'Minimum price for free shipping, ' . $currency, 'dhl_pickup_method' ),
                        'type'        => 'number',
                        'description' => __( 'If free shipping has been enabled, this will control the minimum amount that the customer will have to spend for free shipping', 'dhl_pickup_method' ),
                        'default'     => 0,
                        'desc_tip'    => true,
                    ),
                    'shipping_price'         => array(
                        'title'       => __( 'Shipping Price, ' . $currency, 'dhl_pickup_method' ),
                        'type'        => 'number',
                        'description' => __( 'This controls what the customer will have to pay for this shipping method', 'dhl_pickup_method' ),
                        'class'       => 'shipping_price',
                        'default'     => 51,
                        'desc_tip'    => true,
                    ),
                );
            }
        }
    }
}

add_action( 'woocommerce_shipping_init', 'dhl_pickup_method_init' );

function add_dhl_pickup_method( $methods ) {
    $methods['dhl_pickup_method'] = 'WC_Dhl_Pickup_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_dhl_pickup_method' );
