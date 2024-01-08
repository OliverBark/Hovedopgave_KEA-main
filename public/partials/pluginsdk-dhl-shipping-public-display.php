<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://plugins.dk/
 * @since      1.0.0
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/public/partials
 */

/**
 *Display our input form for zipcode and the "search" button
 *
 * @access public
 * @return void
 */



function find_pickup_point_by_postal_code_dhl(){
    ?>
 
    <table id="dhl-search-field-table" class="shop_table">
      <tfoot>
        <tr>
          <th class="th"> <?php echo _e("Find nærmeste pakkeshop:", "pluginsdk_dhl_shipping"); ?></th>
          <td>
            <div id="dhl-input-zipcode-div">
              <input id="dhl-input-zipcode" class="dhl-input-zipcode" type="text" placeholder=<?php echo _e("Postnummer", "pluginsdk_dhl_shipping"); ?>>
              <button type="button" id="dhl-pickup-points-search" class="button alt wp-element-button"><?php echo _e("Find", "pluginsdk_dhl_shipping"); ?></button>        
              <br>
              <br>
              <p id="selected-parcelshop-modal-display"><?php echo _e("No pickup location has been selected", "pluginsdk_dhl_shipping"); ?></p>
            </div>
          </td>
        </tr>
      </tfoot>
    </table>
  
  <?php
}

function display_modal_window_dhl()
  {
  ?>
    <!-- Modal -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div id="pluginsdk-dhl-shipping-modal">
      <section id="pluginsdk-dhl-shipping-modal-content">
        <section id="map-container">
          <div id="map">
          </div>
        </section>
        <section id="shop">
          <p><?php echo _e("Chosen Pickup point:", "pluginsdk_dhl_shipping"); ?></p>
          <p id="selected-parcelshop-modal"></p> <!-- display selected parcelshop -->
          <input id="shipping_parcelshop_number_dhl" type="hidden" name="shipping_parcelshop_number_dhl">
          <input id="shipping_parcelshop_name_dhl" type="hidden" name="shipping_parcelshop_name_dhl">
          <input id="shipping_parcelshop_adress_dhl" type="hidden" name="shipping_parcelshop_adress_dhl">
          <input id="shipping_parcelshop_zipcode_dhl" type="hidden" name="shipping_parcelshop_zipcode_dhl">
          <input id="shipping_parcelshop_city_dhl" type="hidden" name="shipping_parcelshop_city_dhl">
        </section>
        <section id="list">
          <div id="pluginsdk-dhl-shipping-shops"></div>
        </section>
        <section id="close">
          <button id="pluginsdk-dhl-shipping-close-button-modal" type="button" class="close button alt wp-element-button" data-dismiss="modal" aria-hidden="true">&times;</button>
        </section>
        <section id="confirm">
          <button id="pluginsdk-dhl-shipping-confirm-button-modal" type="button" class="button alt wp-element-button">Confirm</button>
        </section>
      </section>
    </div>
  <?php
  }

//hook for print zipcode search form, modal window with results and chosen pickup point
add_action('woocommerce_review_order_before_payment', 'display_ui_elements_dhl');

/**
 *Display modal window for all parcelshops by given zipcode
 *
 * @access public
 * @return void
 */

 function display_ui_elements_dhl()
 {
    find_pickup_point_by_postal_code_dhl();
    display_modal_window_dhl();
 }

 function pluginsdk_dhl_shipping_after_checkout_validation($posted_data, $errors) {
  $chosen_shipping_method = isset($posted_data['shipping_method'][0]) ? $posted_data['shipping_method'][0] : '';
  error_log($chosen_shipping_method);
  
  // Check if the chosen shipping method contains 'dhl_pickup_method'
  if (strpos($chosen_shipping_method, 'dhl_pickup_method') !== false) {
      // Check if the pickup shop is not set
      if (empty($_POST['shipping_parcelshop_name_dhl'])) {
          $errors->add('shipping_dhl_error', __('Please fill out the pickup shop for Dhl Pickup shipping method', 'your-textdomain'));
          error_log('chosen_shipping_method: ' .$chosen_shipping_method);
      }
  }
}

add_action('woocommerce_after_checkout_validation', 'pluginsdk_dhl_shipping_after_checkout_validation', 10, 2);

?>



