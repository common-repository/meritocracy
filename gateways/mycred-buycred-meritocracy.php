<?php
if ( ! defined( 'MERITOCRACY_VERSION' ) ) exit;

/**
 * Stripe Gateway
 * Allows point purchases using Stripe.
 * @since 1.0
 * @version 2.0
 */
if ( ! class_exists( 'myCRED_Meritocracy' ) ):
	class myCRED_Meritocracy extends myCRED_Payment_Gateway {

		public $visitors_allowed;
        public $unique_id;
        public $classes;
        public $id;
        public $logo;
        public $title;
        public $desc;
        public $cost;
        public $amount;
        public $to;
        public $label;
        public $ctype;
        public $content;
		public $currency='Near';

		/**
		 * Construct
         * @since 2.2.6 @filter added `mycred_buycred_populate_transaction` to avoide adding pending payments
		 */
		function __construct( $gateway_prefs ) {
			$types            = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label )
				$default_exchange[ $type ] = 1;

			parent::__construct( array(
				'id'               => 'meritocracy',
				'label'            => 'Meritocracy',
				'gateway_logo_url' => plugins_url( 'assets/images/meritocracy-logo.png', MERITOCRACY ),
				'defaults' => array(
					'protocol_network'   => '',
					'wallet_id'         => '',
					'exchange'          => $default_exchange,
					'currency'			=> $this->currency
				)
			), $gateway_prefs );

			add_action( 'mycred_front_enqueue',                array( $this, 'register_scripts' ), 20 );
			add_filter( 'mycred_buycred_refs',                   array( $this, 'add_reference' ) );
			add_filter( 'mycred_buycred_log_refs',               array( $this, 'purchase_log' ) );
		}


		/**
		 * Add Reference
		 * @since 1.0.5
		 * @version 2.0
		 */
		public function add_reference( $references ) {

			if ( ! array_key_exists( 'buy_creds_with_meritocracy', $references ) )
				$references['buy_creds_with_meritocracy'] = __( 'buyCRED Purchase (Meritocracy)', 'meritocracy' );

			return $references;

		}

		/**
		 * Add Gateway to Purchase Log
		 * @since 1.0.3
		 * @version 1.0.0
		 */
		public function purchase_log( $instances ) {

			$instances[] = 'buy_creds_with_meritocracy';
			return $instances;

		}

        /**
         * Filter's Callback
         * @since 2.2.6
         * @version 1.0
         */
        public function mycred_buycred_populate_transaction( $populate_transaction, $id )
        {
            if( $id == 'meritocracy ' )
                $populate_transaction = true;

            return $populate_transaction;
        }



		public function register_scripts() {

			if ( ! is_user_logged_in() ) return;


			$admin_account=(isset($this->prefs['wallet_id']) ? $this->prefs['wallet_id'] : '');
			$point_type=mycred( parent::get_point_type());
			$network=(isset($this->prefs['protocol_network']) ? $this->prefs['protocol_network'] : '');
			$exchange_rate=(isset($this->prefs['exchange'][$point_type->get_point_type_key()]) ? $this->prefs['exchange'][$point_type->get_point_type_key()] : '');
			

			wp_register_script( 'near-api', plugins_url('assets/js/near-api.js',MERITOCRACY ), array(), MERITOCRACY_VERSION.time() );
			wp_register_script( 'script-buycred-js', plugins_url('assets/js/script-buycred.js',MERITOCRACY ), array('jquery'), MERITOCRACY_VERSION.time());
			wp_localize_script('script-buycred-js', 'near_wallet', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'meritocracy_purchase_nonce' => wp_create_nonce( 'meritocracy-nonce'),
				'near_network' => $network,
				'near_admin_account' => $admin_account,
				'near_exchange_rate' => $exchange_rate,
				'point_type_key'=> $point_type->get_point_type_key(),
				'thankyou_url'=> parent::get_thankyou()

			));



		 }




		/**
		 * Process Purchase
		 * @since 1.0
		 * @version 2.0
		 */
		public function process() { }

		/**
		 * AJAX Buy Handler
		 * @since 1.8
		 * @version 1.0
		 */
		public function ajax_buy() {

			// Return a JSON response

			// $response = do_shortcode('[mycred_stripe_buy amount='.$_REQUEST["amount"].']Continue[/mycred_stripe_buy]');
			// $response .= "<script>jQuery('.mycred-stripe-buy-button').click();jQuery('.mycred_stripe_close_btn').on( 'click', function(ev) {jQuery('#buycred-checkout-wrapper').removeClass('open');jQuery('#buycred-checkout-form').empty();jQuery('#buycred-checkout-form').append('<div class=\"loading-indicator\"></div>');});</script>";

			// $this->send_json( $response );

		}

		public function checkout_footer( $content = '' )
        {
			echo do_shortcode( '[meritocracy_buy_form]' );
            return $content;
        }

        /**
         * Full page Checkout
         * @param $gateway_prefs
         * @since 2.2.6
         * @verison 1.0
         */
        public function checkout_page_body()
        {
            echo wp_kses_post($this->checkout_header());

            echo wp_kses_post($this->checkout_logo( false ));

            echo wp_kses_post($this->checkout_order());

            echo wp_kses_post($this->checkout_footer());

            //  echo wp_kses_post($this->checkout_cancel());

        }

		/**
		 * Results Handler
		 * @since 1.0
		 * @version 2.0.1
		 */
		public function returning() {

			add_shortcode( 'meritocracy_buy_form', array( $this, 'meritocracy_buy_form_callback' ) );

		}

		/**
		 * Admin Init
		 * @since 2.0
		 * @version 1.0
		 */
		public function admin_init() {

			if ( ! is_user_logged_in() && $this->visitors_allowed )
				add_action( 'wp_ajax_nopriv_meritocracy_purchase_points', array( $this, 'ajax_new_purchase' ) );

			if ( is_user_logged_in() )
				add_action( 'wp_ajax_meritocracy_purchase_points',        array( $this, 'ajax_new_purchase' ) );

		}

		/**
		 * Buy Handler
		 * @since 1.0
		 * @version 2.0
		 */
		public function buy() {

			wp_die( '<p>Stripe payments not allowed via the buyCRED Checkout page.</p>' );

		}

		/**
		 * Shortcode:
		 * @since 1.0
		 * @version 1.0
		 */
		public function meritocracy_buy_form_callback( $atts, $content = '' )
		{
			wp_enqueue_script( 'near-api' );
			wp_enqueue_script( 'script-buycred-js' );

			$point_type=mycred( parent::get_point_type());
			$network=(isset($this->prefs['protocol_network']) ? $this->prefs['protocol_network'] : '');
			$exchange_rate=(isset($this->prefs['exchange'][$point_type->get_point_type_key()]) ? $this->prefs['exchange'][$point_type->get_point_type_key()] : '');
			
            if ( ! $this->visitors_allowed && ! is_user_logged_in() ) return;
            ?>

                <section class="mycred_buy_section_1">

                   <hr>

                </section>
                <section class="mycred_buy_section_2">
                    <h2><?php _e( 'Payment Information', 'meritocracy' ); ?></h2>


                    <div class="form-row">
                        <label for="card-element">
                            <?php _e( 'Near Account', 'meritocracy' ); ?>
                        </label>
						<p class="account-info">
						<input type="text" name="user_near_wallet" id="user_near_wallet" class="form-control" />
							<input type="button" id="meritocracy_account_btn" class="button button-primary button-large" value="SignIn">
							<br>

							<p id="info-messages"></p>
						</p>
                        <div class="meritocracy_purchase">

							<p class="additional-info"><?php

							if(isset($_GET['account_id']) && isset($_GET['public_key']) && isset($_GET['all_keys'])) {
								_e( 'Access Succfully granted, now you can go with the purchase.', 'meritocracy' );
							}


							?></p>
						<label for="card-element">
                            <?php _e( 'Points', 'meritocracy' ); ?>
                        </label>
							<div class="form-group">

								<input type="number" name="purchase_points" id="purchase_points" value="<?php
								if(isset($_POST['amount']) && !empty($_POST['amount']))
								{
									echo esc_html($_POST['amount']);
								}

								?>"/>

								<p><em><?php _e( sprintf('1 %s Equals %.2f Near',$point_type->plural(),$exchange_rate), 'meritocracy' ); ?></em></p>

								<p><em><?php //_e( sprintf("Network in use is '%s'",$network), 'meritocracy' ); ?></em></p>
							</div>
							<div class="form-group">
								<p class="submit"><input type="button" id="meritocracy_purchase_points" class="button button-primary button-large" value="Purchase" ></p>
							</div>

						</div>

                        <!-- Used to display Element errors. -->
                        <div id="card-errors" role="alert">
						</div>
                    </div>


                </section>


            </div>

            </div>

            </div>


            </div>
            </form>
            </div>


            <?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;

		}

		/**
		 * AJAX: New Purchase
		 * Triggered when Stripe has verified a card and has a token for us.
		 * Will attempt to charge and capture payment using this card. If successfull,
		 * we will request points to be paid out. If payout is declined, the charge is refunded.
		 * @since 2.0
		 * @version 1.0.1
		 */
		public function ajax_new_purchase()
		{
			$response = array(
				'msg' => __('An error occurred, please contact admin', 'meritocracy')
			);
			if( wp_verify_nonce( sanitize_text_field($_POST['meritocracy_purchase_nonce']), 'meritocracy-nonce' ) ) {
				$points_to_award = sanitize_text_field($_POST['pointsToPurchase']);
				$tx_receipt_id = sanitize_text_field($_POST['tx_receipt_id']);
				$tx_response = sanitize_text_field($_POST['tx_response']);
				$point_type_key=sanitize_text_field($_POST['point_type_key']);


				$user_id = get_current_user_id();

				$data = array(
					'tx_response' => $tx_response,
					'tx_receipt_id' => $tx_receipt_id,
				);
				$log = 'Purchase mycred points from Near Tokens, Tx = '.$tx_receipt_id;
				if(!empty($user_id)) {
					if( mycred_add('purchase_from_near',$user_id,$points_to_award,$log,array(), array(),$point_type_key) ) {
						$response['msg'] = __('Points Successfully Purchased', 'meritocracy');
					}
				}

			}

			echo json_encode($response);
			die();
		}


		/**
		 * Preferences
		 * @since 1.0
		 * @version 2.0
		 */
		public function preferences( $buy_creds = NULL )
		{
			$prefs = $this->prefs;

			?>
			<style type="text/css">
				.mycred-meritocracy .form-control{
					max-width: 75%;
				}
				.mycred-meritocracy .form-control-width{
					max-width: 100px !important;
				}
			</style>
			 <div class="row">
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php esc_html_e( 'Details', 'meritocracy' ); ?></h3>
		<div class="form-group mycred-meritocracy">
			<label for="<?php echo esc_attr( $this->field_id( 'near_account_details' ) ); ?>"><?php esc_html_e( 'Near Account', 'meritocracy' ); ?></label>
			<div class="form-inline">
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'wallet_id' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'wallet_id' ) ); ?>" value="<?php echo esc_attr( $prefs['wallet_id'] ); ?>" class="form-control" />
				<select class="form-control form-control-width" name="<?php echo esc_attr( $this->field_name( 'protocol_network' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'protocol_network' ) ); ?>">
				<option <?php if(isset($prefs['protocol_network']) && !empty($prefs['protocol_network']) && 'mainnet'===$prefs['protocol_network']){echo'selected';}?> value="mainnet">Mainnet</option>
				<option <?php if(isset($prefs['protocol_network']) && !empty($prefs['protocol_network']) && 'testnet'===$prefs['protocol_network']){echo'selected';}?> value="testnet">Testnet</option>
				</select>

		</div>
		</div>
	</div>
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php esc_html_e( 'Setup', 'meritocracy' ); ?></h3>

		<div class="form-group">
			<label><?php esc_html_e( 'Exchange Rates', 'meritocracy' ); ?></label>
			<?php $this->exchange_rate_setup('Near'); ?>
		</div>
	</div>
</div>
			<?php
		}

		/**
		 * Sanatize Prefs
		 * @since 1.0
		 * @version 2.0
		 */
		public function sanitise_preferences( $data ) {

			$new_data                      = array();
			$new_data['protocol_network']           = sanitize_text_field( $data['protocol_network'] );
			$new_data['wallet_id']              = sanitize_text_field( $data['wallet_id'] );


			// If exchange is less then 1 we must start with a zero
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
			}
			$new_data['exchange']          = $data['exchange'];
			return $new_data;

		}



		protected function inform_admin_of_error( $instance = '', $error_message = '', $user = NULL, $object = NULL, $type = 'mycred_default' ) {

			if ( $instance == 'refunds' ) {

				$subject = 'buyCRED Stripe - Refund Error';

				$message = 'The buyCRED Stripe add-on has failed to refund a users payment as %plural% could not be deposited into their account after they made a payment. The user has been informed to contact you regarding this.' . "\n\n";

				$message .= 'The charge ID that needs to be refunded is: %charge_id%.' . "\n\n";
				$message .= 'Error given by Stripe when the refund failed: %error%';

				$message = str_replace( '%charge_id%', $object->id, $message );
				$message = str_replace( '%error%', $error_message, $message );

				if ( $point_type != 'mycred_default' )
					$mycred = mycred( $point_type );
				else
					$mycred = $this->core;

				$message = $mycred->template_tags_general( $mycred );

			}

			if ( apply_filters( 'mycred_stripe_admin_error', true, $error_message, $user, $object, $type ) === true )
				wp_mail( get_option( 'admin_email' ), $subject, $message );

		}

	}
endif;

?>