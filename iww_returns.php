<?php
/*
Plugin Name: iWantWorkwear Returns
Plugin URL: https://iwantworkwear.com/return-form
Description: Allows for quick and east returns
Version: 1.0
Author: Gregory Bastianelli
Author URI: https://iwantworkwear.com/
*/


add_shortcode( 'return_form', 'iww_return_form' );

add_action( 'admin_post_nopriv_process_return', 'iww_process_return' );
add_action( 'admin_post_process_return', 'iww_process_return' );
add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );

function return_scripts(){
  wp_enqueue_script( 'return_js', plugins_url( 'return.js', __FILE__ ), 'jquery', rand(1,9999), true );
  wp_enqueue_style( 'return_js', plugins_url( 'return.css', __FILE__ ), '', rand(1,9999), 'all' );
  wp_localize_script( 'return_js', 'my_ajax_object',
           array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'return_scripts', 99 );

function iww_return_form(){
  // TODO: IF not logged in, ask for order id and email, if user display orders
  $order_id = 10969;
  $email =  'nhill@ueci.coop';
  // 2-factor authorization
  if( ! empty( $order_id ) && ! empty( $email )  ){
    $order = new WC_Order( $order_id );
    // creates hidden input for name to pass
    $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    // validation - makes sure the email is connected to the order
    if( $email == $order->get_billing_email() ){
      $items = $order->get_items(); ?>
      <form>
      <?php

      foreach( $items as $idtem ):
        $data = $idtem->get_data();
        $id = ( isset( $data['variation_id'] ) ) ? $data['variation_id'] : $data['product_id'];
        $product = wc_get_product( $id );
        $image_id = $product->get_image_id();
        $img_url = wp_get_attachment_image_url( $image_id );
        ?>

        <div class=" item-wrapper row py-1 px-3 mb-2">
          <input id="<?php echo $id; ?>_name" type="hidden" value="<?php echo $data['name']; ?>">
          <input id="<?php echo $id; ?>_src" type="hidden" value="<?php echo $img_url; ?>">

          <div id="<?php echo $id; ?>" order="<?php echo $id; ?>" class="row py-3 item-button mb-2  w-100">
            <div class="col-3">
              <!-- <img src="<?php echo $img_url ?>" />  -->
              <div id="<?php echo $id ?>_thumb" class="return_thumb" aria-checked="false" style="border: 1px solid #000; height: 100%; width: 100%;">
                <i class="fa fa-check-circle fa-3x"></i>
              </div>
            </div>

            <div class="col-9">
              <?php echo $data['name']; ?>
            </div>
          </div>

          <div class="<?php echo $id; ?>_show row" style="display: none";>

            <div class="<?php echo $id; ?>_qty col-12 mb-3">
              <h4>How many?</h4>
              <label>You purchased <?php echo $data['quantity']; ?></label>
              <?php iww_get_qty_tag( $id, $data['quantity'] ); ?>
            </div>

            <?php if( $product->get_type() === 'variation' ) : ?>
              <div class="<?php echo $id; ?>_type col-12 mb-3" style="display: none";>
                <h4>Return or Exchange?</h4>
                <?php get_return_type_options( $product, $id ); ?>
              </div>
            <?php else: ?>
              <input type="hidden" value="refund" name="returns[<?php echo $id; ?>][type]" />
            <?php endif; ?>


            <div class="<?php echo $id; ?>_exchange col-12 mb-3" style="display: none";>
              <h4>For What?</h4>
              <table>
                <?php
                if( $product->get_type() === 'variation' ){
                  $daddy = wc_get_product( $data['product_id'] );
                  $children = $daddy->get_children();
                } else {
                  $children = $product->get_children();
                }
                foreach( $children as $child_id ):
                  if( $child_id != $id ){
                    $child = wc_get_product( $child_id );
                    ?>
                    <tr>
                      <td><?php echo $child->get_name(); ?></td>
                      <td><input type="tel" max="<?php echo $data['quantity']; ?>" /></td>
                    </tr>
                    <?php
                  }
                endforeach;
                ?>
              </table>
            </div>



            <div class="<?php echo $id; ?>_reason col-12 mb-3" style="display: none";>
              <h4>Why are you returning this?</h4>
              <?php get_return_reasons_html( $id, array(
                'No longer needed',
                'Innaccurate website description',
                'Defective Item',
                'Better Price Available',
                'Product damaged',
                'Item arrived too late',
                'Missing or broken parts',
                'Product and shipping box damaged',
                'Wrong item sent',
                'Received an extra item ( No refund needed )',
                'Didnt approve purchase',
              ) ); ?>
            </div>
          </div> <!-- .show -->
        </div>
        <?php
        $id++;
      endforeach;
      ?>
      <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
      <input type="hidden" name="customer_name" value="<?php echo $name; ?>">
      <input type="hidden" name="email" value="<?php echo $email; ?>">
      <!-- <input type="hidden" name="action" value="process_return"> -->
      <button type="button" id="submit_preview_btn" class="btn btn-iww btn-block" />Submit</button>
    </form>
    <?php
    } else {
      echo 'The Order ID / Email combination is invalid, please try again.';
    }
  }
}



/**
 * Filter the mail content type.
 */
function wpdocs_set_html_mail_content_type() {
    return 'text/html';
}

function iww_process_return(){
  $returns = array();
  $order_id = $_POST['order_id'];
  $customer = array(
    'name'  => $_POST['customer_name'],
    'email' => $_POST['email'],
  );

  $message = '<h1>Request for Return/Exchange</h1>';
  $message .= '<p>Order: '. $order_id .'</p>';
  $message .= '<p>Name: '. $customer['name'] .'</p>';
  $message .= '<p>Email: '. $customer['email'] .'</p>';
  $message .= '<br>';
  $message .= '<table>';
  $message .= '<thead><th>Type</th><th>ID</th><th>Name</th><th>QTY</th><th>Reason</th></thead>';
  foreach( $_POST['returns'] as $return ){
    if( isset( $return['valid'] ) ){
      $message .= '<tr>';
      $message .= '<td>' . $return['type'] . '</td>';
      $message .= '<td>' . $return['variation_id'] . '</td>';
      $message .= '<td>' . $return['name'] . '</td>';
      $message .= '<td>' . $return['quantity'] . '</td>';
      $message .= '<td>' . $return['reason'] . '</td>';
      $message .= '<tr>';
    }
  }

  $message .= '<table>';

  // echo $message;

  // wp_mail( 'greg@iwantworkwear.com', 'Return Request', $message  );

}

function get_return_reasons_html( $id, $options ){
  echo '<ul class="list-group">';
  foreach( $options as $option ):
    ?>
    <li class="list-group-item">
      <div class="form-check">
        <label class="form-check-label">
          <input type="radio" class="form-check-input" value="<?php echo $option; ?>" name="returns[<?php echo $id; ?>][reason]" /><?php echo $option; ?>
        </label>
      </div>
    </li>
    <?php
  endforeach;
    echo '</ul>';
}

function iww_get_qty_tag( $turn, $max ){
  ?>
  <input id="<?php echo $id; ?>_qty" type="tel" min="1" max="<?php echo $max ?>" name="returns[<?php echo $turn; ?>][quantity]" />
  <?php
}

function get_return_type_options( $product, $id ){
  ?>
  <ul class="list-group">
    <li class="list-group-item">
      <div class="form-check">
        <label class="form-check-label">
          <input type="radio" class="form-check-input" value="exchange" name="returns[<?php echo $id; ?>][type]" />Exchange
        </label>
      </div>
    </li>
    <li class="list-group-item">
      <div class="form-check">
        <label class="form-check-label">
          <input type="radio" class="form-check-input" value="return" name="returns[<?php echo $id; ?>][type]" />Return
        </label>
      </div>
    </li>
  </ul>
  <?php
}
