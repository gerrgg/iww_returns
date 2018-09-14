<?php
/*
Plugin Name: iWantWorkwear Returns
Plugin URL: https://iwantworkwear.com/return-form
Description: Allows for quick and east returns
Version: 1.0
Author: Gregory Bastianelli
Author URI: https://iwantworkwear.com/
*/
add_action( 'wp_enqueue_scripts', 'return_scripts', 99 );
function return_scripts(){
  wp_enqueue_script( 'return_js', plugins_url( 'return.js', __FILE__ ), 'jquery', rand(1,9999), true );
}

add_shortcode( 'return_form', 'iww_return_form' );

function iww_return_form(){
  // TODO: IF not logged in, ask for order id and email, if user display orders
  $order_id = 10969;
  $email =  'nhill@ueci.coop';
  // minimizes chances of guests messing with other orders
  if( ! empty( $order_id ) && ! empty( $email )  ){
    // make order
    $order = new WC_Order( $order_id );
    $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    // validation - makes sure the email is connected to the order
    if( $email == $order->get_billing_email() ){
      $items = $order->get_items(); ?>
      <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <style>
        .main-page-wrapper{
          padding-top: 25px;
          }
          .item-wrapper{
            border-bottom: 1px solid #f7f7f7;
          }
          .btn-iww{
            background-color: #e54d3d;
            color: #fff;
          }
        </style>
      <?php
      $i = 0;
      foreach( $items as $item ):
        $data = $item->get_data();
        $var = wc_get_product($data['variation_id']);
        $image_tag = $var->get_image();
        ?>

        <div class=" item-wrapper row py-1 px-3 mb-2">
          <input type="hidden" name="returns[<?php echo $i; ?>][variation_id]" value="<?php echo $data['variation_id']; ?>">
          <input type="hidden" name="returns[<?php echo $i; ?>][name]" value="<?php echo $data['name']; ?>">
          <div id="<?php echo $data['variation_id']; ?>" order="<?php echo $i; ?>" class="row py-3 item-button mb-2  w-100">
            <div class="col-3">
              <?php // echo $image_tag; ?>
              <div style="border: 1px solid #000; height: 100%; width: 100%;">IMG</div>
            </div>
            <div class="col-9">
              <?php echo $data['name']; ?>
            </div>
          </div>
          <div class="<?php echo $data['variation_id']; ?>_type col-12 mb-3" style="display: none";>
            <!-- TODO: add logic for exchanges -->
            <h4>Exchange or Return?</h4>
            <?php get_return_type_options( $i ); ?>
          </div>
          <div class="<?php echo $data['variation_id']; ?>_qty col-12 mb-3" style="display: none";>
            <h4>How many are you returning?</h4>
            <label>You purchased <?php echo $data['quantity']; ?></label>
            <?php iww_get_qty_tag( $i, $data['quantity'] ); ?>

          </div>
          <div class="<?php echo $data['variation_id']; ?>_reason col-12 mb-3" style="display: none";>
            <h4>Why are you returning this?</h4>
            <?php get_return_reasons_html( $i, array(
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
        </div>
        <?php
        $i++;
      endforeach;
      ?>
      <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
      <input type="hidden" name="customer_name" value="<?php echo $name; ?>">
      <input type="hidden" name="email" value="<?php echo $email; ?>">
      <input type="hidden" name="action" value="process_return">
      <input class="btn btn-iww btn-block" type="submit" />
    </form>
    <?php
    } else {
      echo 'The Order ID / Email combination is invalid, please try again.';
    }
  }
}


add_action( 'admin_post_nopriv_process_return', 'iww_process_return' );
add_action( 'admin_post_process_return', 'iww_process_return' );

function iww_process_return(){
  echo '<pre>';
  var_dump( $_POST );
  echo '</pre>';
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

  echo $message;

  // wp_mail( 'greg@iwantworkwear.com', 'Return Request', $message  );

}

function get_return_reasons_html( $i, $options ){
  echo '<ul class="list-group">';
  foreach( $options as $option ):
    ?>
    <li class="list-group-item">
      <div class="form-check">
        <label class="form-check-label">
          <input type="radio" class="form-check-input" value="<?php echo $option; ?>" name="returns[<?php echo $i; ?>][reason]" /><?php echo $option; ?>
        </label>
      </div>
    </li>
    <?php
  endforeach;
    echo '</ul>';
}

function iww_get_qty_tag( $turn, $max ){
  ?>
  <input type="number" min="1" max="<?php echo $max ?>" name="returns[<?php echo $turn; ?>][quantity]" />
  <?php
}

function get_return_type_options( $i ){
  ?>
  <ul class="list-group">
    <li class="list-group-item">
      <div class="form-check">
        <label class="form-check-label">
          <input type="radio" class="form-check-input" value="exchange" name="returns[<?php echo $i; ?>][type]" />Exchange
        </label>
      </div>
    </li>
    <li class="list-group-item">
      <div class="form-check">
        <label class="form-check-label">
          <input type="radio" class="form-check-input" value="return" name="returns[<?php echo $i; ?>][type]" />Return
        </label>
      </div>
    </li>
  </ul>
  <?php
}
