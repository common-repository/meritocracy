<?php
if ( ! defined( 'MERITOCRACY_VERSION' ) ) exit;

/**
 * Stripe Gateway
 * Allows point purchases using Stripe.
 * @since 1.0
 * @version 2.0
 */
if ( ! class_exists( 'myCRED_CashCred_Meritocracy' ) ):
	class myCRED_CashCred_Meritocracy extends myCRED_Cash_Payment_Gateway {

        public $id;
		public $currency='Near';


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
					'currency'          => 'Near',
					'exchange'          => $default_exchange
				)
			), $gateway_prefs );

			add_action( 'admin_enqueue_scripts',                array( $this, 'register_scripts' ), 20 );
			add_filter( 'mycred_buycred_log_refs',               array( $this, 'purchase_log' ) );

			if ( ! is_user_logged_in() )
				add_action( 'wp_ajax_nopriv_meritocracy_withdrawl_transfer_status_update', array( $this, 'meritocracy_withdrawl_transfer_status_update' ) );

			if ( is_user_logged_in() )
				add_action( 'wp_ajax_meritocracy_withdrawl_transfer_status_update',        array( $this, 'meritocracy_withdrawl_transfer_status_update' ) );


			if ( ! is_user_logged_in() )
				add_action( 'wp_ajax_nopriv_meritocracy_withdrawl_transfer_error', array( $this, 'meritocracy_withdrawl_transfer_error' ) );

			if ( is_user_logged_in() )
				add_action( 'wp_ajax_meritocracy_withdrawl_transfer_error',        array( $this, 'meritocracy_withdrawl_transfer_error' ) );



				//ajax request to save/update mycred_pref_cashcreds on login
				if ( ! is_user_logged_in() )
				add_action( 'wp_ajax_nopriv_save_mycred_pref_cashcreds', array( $this, 'save_mycred_pref_cashcreds' ) );

			if ( is_user_logged_in() )
				add_action( 'wp_ajax_save_mycred_pref_cashcreds',        array( $this, 'save_mycred_pref_cashcreds' ) );



				// if(isset($_SERVER['HTTP_REFERER']) && 'https://wallet.near.org/'==$_SERVER['HTTP_REFERER'])
				// {

				// 	header('Refresh: 0; url=admin.php?page=mycred-cashcreds');
				// 	exit;
				// }

			}







		/**
		 * Bank Transfer Form Fields
		 * @since 2.0
		 * @version 1.0
		 */
		public function form_fields() {

			$gateway_fields = array(
				'near_account' => array(
				    'form'		  =>'input',
					'type'        => 'text',
					'lable'       => 'Near Account',
					'classes'     => 'form-control',
					'placeholder' => 'Near Account ID',
				)

			);

			return  $gateway_fields;

		}

		public function cashcred_payment_settings( $data ) {

			$mycred_pref_cashcreds = mycred_get_option( 'cashcred_user_settings' , false );

			$fields    = $this->form_fields();

			$withdraw_form = new CashCred_Gateway_Fields( $data, $fields );

			?>
			<div id="panel_<?php echo esc_attr( $data );?>" class="cashcred_panel">

				<h3>Meritocracy</h3>

				<?php if( isset( $mycred_pref_cashcreds["gateway_prefs"]["bank"]["enable_additional_notes"] ) ): ?>
				<div class="form-group">
					<p><?php echo esc_html( $mycred_pref_cashcreds["gateway_prefs"]["bank"]["additional_notes"] ); ?></p>
				</div>
				<?php endif;?>


				<?php
				$withdraw_form->generate_form();
					if ( is_admin() )
					{
						wp_enqueue_script( 'near-api' );
						wp_enqueue_script( 'script-cashcred-js' );
					}




					?>

			</div>

			<?php
		}






		public function register_scripts() {
			global $post;
			$recieverID=null;
			$exchange_rate=1;


			$network=(isset($this->prefs['protocol_network']) ? $this->prefs['protocol_network'] : '');
			$admin_account=(isset($this->prefs['wallet_id']) ? $this->prefs['wallet_id'] : '');
			// $exchange_rate=(isset($this->prefs['exchange']['mycred_default']) ? $this->prefs['exchange']['mycred_default'] : '');



			if(!empty($post) && 'cashcred_withdrawal'==get_post_type($post))
			{
				$post_author=$post->post_author;
				$cc_user_settings=get_user_meta( $post_author,'cashcred_user_settings',true);

				$exchange_rate=get_post_meta($post->ID,'cost',true);
				if(empty($exchange_rate)){$exchange_rate=1;}


			//for reciever/cutomer near account id
			if(isset($cc_user_settings['meritocracy']['near_account']) && !empty($cc_user_settings['meritocracy']['near_account']))
			{
				$recieverID=$cc_user_settings['meritocracy']['near_account'];

			}



			}
			if ( ! is_user_logged_in() ) return;

			wp_register_script( 'near-api', plugins_url('assets/js/near-api.js',MERITOCRACY ), array(), MERITOCRACY_VERSION.time());
			wp_register_script( 'script-cashcred-js', plugins_url('assets/js/script-cashcred.js',MERITOCRACY ), array('jquery'), MERITOCRACY_VERSION.time() );

			wp_localize_script('script-cashcred-js', 'near_wallet', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'meritocracy_withdraw_nonce' => wp_create_nonce( 'meritocracy-nonce'),
				'near_network' => $network,
				'near_admin_account' => $admin_account,
				'near_exchange_rate' => $exchange_rate,
				'recieverID'=>$recieverID,

			));


		 }








		/**
		 * Preferences
		 * @since 1.0
		 * @version 2.0
		 */
		public function preferences( $buy_creds = NULL )
		{
			$prefs = $this->prefs;
			wp_enqueue_script( 'near-api' );
			wp_enqueue_script( 'script-cashcred-js' );

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
				<select class="form-control form-control-width" name="<?php echo esc_attr( $this->field_name( 'protocol_network' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'protocol_network' ) ); ?>" >
				<option <?php if(isset($prefs['protocol_network']) && !empty($prefs['protocol_network']) && 'testnet'===$prefs['protocol_network']){echo'selected';}?> value="testnet">Testnet</option>
				<option <?php if(isset($prefs['protocol_network']) && !empty($prefs['protocol_network']) && 'mainnet'===$prefs['protocol_network']){echo'selected';}?> value="mainnet">Mainnet</option>

				</select>

		</div>
		</div>
		<div class="form-group">
			 <input type="button" name="btn_meritocarcy_cc_login" id="btn_meritocarcy_cc_login" value="<?php esc_html_e( 'SignIn', 'meritocracy' ); ?>" class="button button-large mycred-ui-btn-purple" />
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

			$new_data = array();
			$new_data['protocol_network'] = sanitize_text_field( $data['protocol_network'] );
			$new_data['wallet_id']= sanitize_text_field( $data['wallet_id'] );
			$new_data['currency'] = sanitize_text_field( $this->currency );


			// If exchange is less then 1 we must start with a zero
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
				$new_data['exchange']          = $data['exchange'];
			}
			
			return $new_data;

		}

		public function process($post_id = false) {

			return array (
						'status'  => true ,
						'message' => __( 'Payment successfully completed.', 'meritocracy' ),
						'date'    => get_post_meta( $post_id, 'cashcred_payment_transfer_date', true )
					);
		}

		public function generate_transaction_debugging_log($post_ID,$result) {


			if(empty($post_ID))
			{
				return;
			}

			$counter = (int) mycred_get_post_meta( $post_ID, 'cashcred_log_counter', true );
			$time = current_time( 'mysql' );


			$object_save = array (
				'payment_gateway' => 'Meritocracy' ,
				'response'        => $result,
				'datetime'        => $time,
				'details'        => $time,
			);

			if( !isset( $counter ) ) {

				update_post_meta( $post_ID, 'cashcred_log_1', $object_save );
				update_post_meta( $post_ID, 'cashcred_log_counter', 1 );
				update_post_meta( $post_ID, 'cashcred_payment_transfer_date', $time );

			} else {

				$counter = $counter + 1;
				update_post_meta( $post_ID, 'cashcred_log_'.$counter, $object_save );
				update_post_meta( $post_ID, 'cashcred_log_counter', $counter );
				update_post_meta( $post_ID, 'cashcred_payment_transfer_date', $time );

			}


		}




		public function meritocracy_withdrawl_transfer_status_update()
		{
			$post_id=isset($_POST['post_name']) ? sanitize_text_field($_POST['post_name']) : null;
			$result=sanitize_text_field($_POST['result']);
			$tstatus =sanitize_text_field($_POST['tstatus']);
			 //here create debuggung log
			$this->generate_transaction_debugging_log($post_id,$result);

			if('true'==$tstatus)
			{
				//gateway not availble when re pay after pending
				// $gateway=sanitize_text_field($_POST["cashcred_pending_payment"]["gateway"]);
				// $_POST['cashcred_pay_method']=$gateway;
			$classw=new myCRED_cashCRED_Module();

			echo esc_js($classw->cashcred_pay_now($post_id,false));
			}
			else{


				$response = array(
					'status'   => false,
					'message'  => 'See debug log for error!'
				);
				echo wp_send_json( $response );
			}

			die();

		}




		public function save_mycred_pref_cashcreds()
		{
			$wallet_id= isset($_REQUEST['wallet_id']) ? sanitize_text_field($_POST['wallet_id']) :'';
			$network_id=isset($_REQUEST['network_id']) ? sanitize_text_field($_REQUEST['network_id']) : '';

			$mycred_pref_cashcreds = mycred_get_option( 'mycred_pref_cashcreds' , false );

			$mycred_pref_cashcreds["gateway_prefs"]["meritocracy"]["wallet_id"]=$wallet_id;
			$mycred_pref_cashcreds["gateway_prefs"]["meritocracy"]["protocol_network"]=$network_id;
			$temp=mycred_update_option( 'mycred_pref_cashcreds', $mycred_pref_cashcreds );

			echo esc_html($temp);
			die();



		}


	}//class end
endif;
?>
