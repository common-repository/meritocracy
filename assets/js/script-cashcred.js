function paynow_requests_start_workings() {
  jQuery('#cashcred-payment-status .spinner').addClass('is-active');
  jQuery('.cashcred_paynow_text').text('Loading...');
  jQuery('#payment_response').slideUp();
  jQuery('#meritocracy_cashcred_paynow').prop('disabled', true);
  jQuery( '#payment_response' ).removeClass();
}

function paynow_requests_exit_workings_on_error() {

  jQuery( '.cashcred_paynow_text' ).text('Pay Now'); 
 
  jQuery('#cashcred-payment-status .spinner').removeClass('is-active');
  // jQuery( '.disabled_fields' ).prop('disabled', false);
  // jQuery( '.readonly_fields' ).prop('readonly', false);
  jQuery('#meritocracy_cashcred_paynow').prop('disabled', false);

}
function near_paynow() {
  //some gui work to 
  paynow_requests_start_workings();

  //statr preparing near request
	const pointsToTransfer = jQuery('#cashcred-pending-payment-points').val();
	 const near_network=near_wallet.near_network;
	 const near_admin_wallet_id=near_wallet.near_admin_account;
	 const near_exchange_rate=near_wallet.near_exchange_rate;
	 const recieverID=near_wallet.recieverID; 
   if('' == near_admin_wallet_id.trim())
   {
     alert('Admin Near Account Not Found!');
     paynow_requests_exit_workings_on_error();
     return 0;
   }

   if('' == near_network.trim())
   {
     alert('Admin Near Network Not Found!');
     paynow_requests_exit_workings_on_error();
     return 0;
   }

if(null == recieverID)
{
  alert('User/Reciever Near Account Not Found!');
  paynow_requests_exit_workings_on_error();
  return 0;
}
var mnwtype='testnet';
if("mainnet"==near_network){mnwtype='app';}
    const nearConfig = {
      networkId: near_network,
      contractName: near_admin_wallet_id,
      nodeUrl: "https://rpc."+near_network+".near.org",
      walletUrl: "https://"+mnwtype+".mynearwallet.com",
      helperUrl: "https://helper."+near_network+".near.org",
      explorerUrl: "https://explorer."+near_network+".near.org"
      
    };
    
    (async function () 
    { 
       
         const keyStore = new nearApi.keyStores.BrowserLocalStorageKeyStore();
         nearConfig.deps = { keyStore };
         window.near = await nearApi.connect(nearConfig);
         const wallet = new nearApi.WalletConnection(window.near);

         var adminCID='';
         if("mainnet"==near_wallet.near_network)
         {
           adminCID=near_wallet.near_admin_account;
         }
         else{
           adminCID=near_wallet.near_admin_account.concat('.', near_wallet.near_network);
         }

         const adminContactID = adminCID;

    var senderID=adminContactID;
     
     if(wallet.isSignedIn())
     {

     
          const signIn =  wallet.requestSignIn(
            senderID // contract requesting access
            );

          if(wallet.isSignedIn()) 
          {
            const walletAccountObj = wallet.account();
 
            const tokenForPurchase = (parseFloat(pointsToTransfer) * parseFloat(near_exchange_rate));
            
            
            const { utils } = nearApi;
            const tokenForPurchasePoints = utils.format.parseNearAmount(String(tokenForPurchase));

            var response='';
            var result='';
            var tstatus=false;
            var net_id_string='';

            if("mainnet"==near_network){
              net_id_string='';
            }
            else{
              net_id_string='.'+near_network;
            }
            var recieverID_network=recieverID+net_id_string;
            try {
              var response =  await walletAccountObj.sendMoney(
                recieverID_network, // receiver account with network
                      tokenForPurchasePoints // amount in yoctoNEAR
                    ); 

              var transactionDetails=JSON.stringify(response.transaction);
              var trans_receipt_id=JSON.stringify(response.transaction_outcome.outcome.status);
              result=JSON.stringify([trans_receipt_id,transactionDetails]);
              tstatus=true;

            } catch(err) {
              // catches errors both in fetch and response.json
              response=err;
            } 
          
             if(''==result.trim())
             {
              result=JSON.stringify([response.message]);
              tstatus=false;
             }  
            // if (response) 
            // { 
                //near transfer succesfull 
                form 		= 	jQuery('#post');
                btn_paynow	=	jQuery('#meritocracy_cashcred_paynow');
                placeholder =	jQuery('#placeholder');
                spinner		=	jQuery('#cashcred-payment-status .spinner');
			        	var data = jQuery(form).serialize() + "&action=meritocracy_withdrawl_transfer_status_update&result="+result+"&tstatus="+tstatus;
                // jQuery.post(near_wallet.ajax_url, data, function(response) {
            
                //   //do default succes work here
                  
                // });
                jQuery.ajax({
                  type: 'POST',
                  url: near_wallet.ajax_url,
                  data: data,
                  dataType: "json",
                 
                  success: function( response ) {
                     
                    jQuery( '.cashcred_paynow_text' ).text('Pay Now');
                    jQuery( '.cashcred_paynow_text' ).attr('disabled',true);
                    jQuery( '#payment_response' ).html(response.message);
                    jQuery( '#payment_response' ).addClass(""+ response.status +"");
                    jQuery( '#payment_response' ).slideDown();
                    
                    if(response.status == true){
                      jQuery( '.disabled_fields' ).prop('disabled', true);
                      jQuery( '.readonly_fields' ).prop('readonly', true);
                      //jQuery( btn_paynow ).prop('disabled', false);
                      jQuery('.cashcred_Approved').remove();
                      
                      html_approved = "<span class='cashcred_Approved'>Approved</span>";
                      comments = "<li><time>"+response.date+"</time><p>"+response.comments+"</p></li>";
                      
                      jQuery( '#cashcred-comments .history').prepend(comments);
                      jQuery('.type-cashcred_withdrawal .form-group').html(html_approved);
                      jQuery( '#cashcred-payment-status .entry-date' ).html(response.date); 
                      jQuery('#cashcred_post_ststus select').get(0).selectedIndex = 1;
                      jQuery( '#user_total_cashcred' ).html(response.total); 
        
                    }
                    
                    
                    if( jQuery('#cashcred-developer-log').length && response.log != null ) {
                    
                      jQuery( '#cashcred-developer-log .inside' ).html( response.log ); 
                    
                    }
                    
                  },
                  error: function(xhr) {
                  
                    // if error occured
                    alert("Error occured.please try again");
        
                    jQuery( '#payment_response' ).html(xhr.responseText);
                    jQuery( '#payment_response' ).addClass('false');					
                    jQuery( btn_paynow ).prop('disabled', false);
                    jQuery( '.cashcred_paynow_text' ).text('Pay Now');
                  },
                  complete: function() {
                    
                    jQuery(spinner).removeClass('is-active');
                    
                  }
                });
            // }
            // else{
            //   //error in transaction

            // }
             

            
			
            
         }
        }
        else{
          alert('Please Login first!');
          paynow_requests_exit_workings_on_error();
          return 0;
        }
 
       })(window);	
}//payment fc end

function replace_paynow_btn()
{  
  
  if(jQuery("span").hasClass("cashcred_Approved")){
   
  }
  else{
    var selectedGateway=jQuery('#cashcred-pending-payment-gateway').val();
    var near_paynow_btn= jQuery('<button type="button" id="meritocracy_cashcred_paynow" class="button button-secondary btn-lg btn-block"><div class="spinner"></div> <span class="cashcred_paynow_text">Pay Now</span></button>');
     if('meritocracy'== selectedGateway)
     {
       jQuery('#cashcred_paynow').hide();
       jQuery(near_paynow_btn).insertAfter(jQuery('#cashcred_paynow'));
     }
     else{
       jQuery('#meritocracy_cashcred_paynow').hide();
       jQuery('#cashcred_paynow').show();
        
     }
}
 
//disable select on
jQuery('#cashcred_withdrawal_request').find("input,select,textarea,button").prop("disabled",true);
  
}

jQuery(document).on("change","#cashcred-pending-payment-gateway",function() {
   
    replace_paynow_btn();

});

jQuery(document).on("click","#meritocracy_cashcred_paynow",function() {
   
 //paynow working here
 near_paynow();
      //   var form 		= 	jQuery('#post');
			// var data = jQuery(form).serialize() + "&action=meritocracy_withdrawl_transfer";
      //   jQuery.post(near_wallet.ajax_url, data, function(response) {
               
                  
                  
      //     alert(response);
      
  
      // });

});

jQuery( document ).ready(function() {
   
  //check for transaction get variable
  const urlParams = new URLSearchParams(window.location.search);
  const txhash = urlParams.get("transactionHashes")

  if(txhash !== null){
    //record transaction success
    near_mainnet_tx_entry(txhash);
  }

  replace_paynow_btn();
 
     

    query_wallet("is_signIn");

      jQuery("#btn_meritocarcy_cc_login").click(function(){
       
        //do validations here
        // var near_netwrok=jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').val().trim();
        // var near_wallet_id=jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').val().trim();
        
             
            if(jQuery(this).val() == "SignOut")
              {   
               query_wallet("do_signOut");
              }
              else if(jQuery(this).val() == "SignIn")
              {
                 var flag=query_wallet("do_signIn");
              }
        
      
    

    });

     
  
  });//doc load end
  
  async function query_wallet(str) 
  { 
    // if((str==="is_signIn") && (!near_wallet.near_network || !near_wallet.near_network)) 
    // {
    //   alert('Near Credentials are not set up in admin');
    //   return flag=false;
    // }
    var network_id=jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').val();
    var wallet_id=jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').val();
    // console.log(network_id);
    // console.log(wallet_id);
    var mnwtype='testnet';
    if("mainnet"==network_id){mnwtype='app';}
  const nearConfig = {
      networkId: network_id,
      contractName: wallet_id,
      nodeUrl: "https://rpc."+network_id+".near.org",
      walletUrl: "https://"+mnwtype+".mynearwallet.com",
      helperUrl: "https://helper."+network_id+".near.org",
      explorerUrl: "https://explorer."+network_id+".near.org"
      
    };

    const keyStore = new nearApi.keyStores.BrowserLocalStorageKeyStore();
    nearConfig.deps = { keyStore };
    window.near = await nearApi.connect(nearConfig);
    const wallet = new nearApi.WalletConnection(window.near);
  
    
  
  
    if(str==="is_signIn")
    {
      if(wallet.isSignedIn()) {
      const accountId=  wallet.getAccountId();
      // jQuery('#withdraw_form_near_id').val(accountId); 
      var check_net_id=accountId.split('.')[1];
      if(!(typeof(check_net_id) != "undefined" && check_net_id !== null)) {
        check_net_id='mainnet';
      }

      //to set network from near to mainnet as we are getting it from url

      if("near" == check_net_id ) {
        check_net_id='mainnet';
      }

      
        
      const net_id=check_net_id;
      const wallet_id=accountId.split('.')[0];

      //do ajax request to save signed in user settings to mycred_pref_cashcreds
      var data = {
        'action': 'save_mycred_pref_cashcreds',
        'wallet_id': wallet_id, 
        'network_id': net_id
            
        };
        jQuery.post(near_wallet.ajax_url, data, function(response) {
            // var response_parsed = JSON.parse(response)
        
        });

        // console.log('select net=');
        // console.log(net_id);
      // jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').attr('disabled', true);
      jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').attr('readonly',true);

      jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').val(net_id);
      jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').attr('value',wallet_id);
      jQuery("#btn_meritocarcy_cc_login").val("SignOut"); 
      
     
       
      return ;
  
      } else
      { 
      // jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').attr('disabled', false);
      jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').attr('readonly',true);
      jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').val('');
      jQuery("#btn_meritocarcy_cc_login").val("SignIn"); 
      
        return ;
      }
    }
  
    
    if(str=="do_signIn")
    {
        var nearId =jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').val();
        var netId =jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').val();
      var userContactID=nearId.concat('.', netId);
  
     if(!netId) {
         alert('Please insert your near wallet network');
         return false;
     }

    //  if(!nearId) {
    //     alert('Please insert your near wallet id');
    //     return false;
    // }
      const signIn = await wallet.requestSignIn('');
        if(wallet.isSignedIn()) {  
        //   jQuery("#withdraw_form_near_id").attr('disabled','disabled');
          jQuery("#btn_meritocarcy_cc_login").val("SignOut");
          
        }
        else{ 
          jQuery("#btn_meritocarcy_cc_login").val("SignIn");
        }
  
        return ;
    }
  
    if(str=="do_signOut")
    {
      wallet.signOut();
      
        //do ajax request to sclear signed in user settings to mycred_pref_cashcreds
      var data = {
        'action': 'save_mycred_pref_cashcreds',
        'wallet_id': '', 
        'network_id': ''
            
        };
        jQuery.post(near_wallet.ajax_url, data, function(response) {
            // var response_parsed = JSON.parse(response)
            document.location = String(document.location).replace(/\?.*$/, '?page=mycred-cashcreds');
        });

        // jQuery('#mycred-gateway-prefs-meritocracy-protocol-network').attr('disabled', false);
        jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').attr('readonly',true);
        jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').val('');
      jQuery("#btn_meritocarcy_cc_login").val("SignIn"); 
      
      
      
 
      return;
    }
    }
  
  
  
  
 //CASHcred_BACKend form-> wallet id validation
 jQuery('#mycred-gateway-prefs-meritocracy-wallet-id').on('keypress', function (event) { 
  var regex = new RegExp("^[a-zA-Z0-9]+$");
  var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
  if (!regex.test(key)) {
     event.preventDefault();
     return false;
  }
}).on('keyup', function (event) { 
var text= jQuery(this).val();
text=text.toLowerCase();
jQuery(this).val(text);

});

  
  
function near_mainnet_tx_entry(txHash) {
  //some gui work to 
  paynow_requests_start_workings();

  //statr preparing near request
	const pointsToTransfer = jQuery('#cashcred-pending-payment-points').val();
	 const near_network=near_wallet.near_network;
	 const near_admin_wallet_id=near_wallet.near_admin_account;
	 const near_exchange_rate=near_wallet.near_exchange_rate;
	 const recieverID=near_wallet.recieverID; 
   if('' == near_admin_wallet_id.trim())
   {
     alert('Admin Near Account Not Found!');
     paynow_requests_exit_workings_on_error();
     return 0;
   }

   if('' == near_network.trim())
   {
     alert('Admin Near Network Not Found!');
     paynow_requests_exit_workings_on_error();
     return 0;
   }

if(null == recieverID)
{
  alert('User/Reciever Near Account Not Found!');
  paynow_requests_exit_workings_on_error();
  return 0;
}
	  
var mnwtype='testnet';
if("mainnet"==near_network){mnwtype='app';}
    const nearConfig = {
      networkId: near_network,
      contractName: near_admin_wallet_id,
      nodeUrl: "https://rpc."+near_network+".near.org",
      walletUrl: "https://wallet."+mnwtype+".mynearwallet.com",
      helperUrl: "https://helper."+near_network+".near.org",
      explorerUrl: "https://explorer."+near_network+".near.org"
      
    };
    
    (async function () 
    { 
       
         const keyStore = new nearApi.keyStores.BrowserLocalStorageKeyStore();
         nearConfig.deps = { keyStore };
         window.near = await nearApi.connect(nearConfig);
         const wallet = new nearApi.WalletConnection(window.near);

        var adminCID='';
          if("mainnet"==near_wallet.near_network)
          {
            adminCID=near_wallet.near_admin_account;
          }
          else{
            adminCID=near_wallet.near_admin_account.concat('.', near_wallet.near_network);
          }

          const adminContactID = adminCID;

		 var senderID=adminContactID;
     
         

      if(wallet.isSignedIn()) 
      {
        const { utils } = nearApi;
        
        var response='';
        var result='';
        var tstatus=false;

        
        try {

          let decodedTxHash = utils.serialize.base_decode(txHash);
        var response = await near.connection.provider.txStatus(decodedTxHash,senderID);
        
          var transactionDetails=JSON.stringify(response.transaction);
          var trans_receipt_id=JSON.stringify(response.transaction_outcome.outcome.status);
          result=JSON.stringify([trans_receipt_id,transactionDetails]);
          tstatus=true;

        } catch(err) {
          // catches errors both in fetch and response.json
          response=err;
        } 
      
          if(''==result.trim())
          {
          result=JSON.stringify([response.message]);
          tstatus=false;
          }  
        // if (response) 
        // { 
            //near transfer succesfull 
            form 		= 	jQuery('#post');
            btn_paynow	=	jQuery('#meritocracy_cashcred_paynow');
            placeholder =	jQuery('#placeholder');
            spinner		=	jQuery('#cashcred-payment-status .spinner');
            var data = jQuery(form).serialize() + "&action=meritocracy_withdrawl_transfer_status_update&result="+result+"&tstatus="+tstatus;
            // jQuery.post(near_wallet.ajax_url, data, function(response) {
        
            //   //do default succes work here
              
            // });
            jQuery.ajax({
              type: 'POST',
              url: near_wallet.ajax_url,
              data: data,
              dataType: "json",
              
              success: function( response ) {
                  
                jQuery( '.cashcred_paynow_text' ).text('Pay Now');
                jQuery( '.cashcred_paynow_text' ).attr('disabled',true);
                jQuery( '#payment_response' ).html(response.message);
                jQuery( '#payment_response' ).addClass(""+ response.status +"");
                jQuery( '#payment_response' ).slideDown();
                
                if(response.status == true){
                  jQuery( '.disabled_fields' ).prop('disabled', true);
                  jQuery( '.readonly_fields' ).prop('readonly', true);
                  //jQuery( btn_paynow ).prop('disabled', false);
                  jQuery('.cashcred_Approved').remove();
                  
                  html_approved = "<span class='cashcred_Approved'>Approved</span>";
                  comments = "<li><time>"+response.date+"</time><p>"+response.comments+"</p></li>";
                  
                  jQuery( '#cashcred-comments .history').prepend(comments);
                  jQuery('.type-cashcred_withdrawal .form-group').html(html_approved);
                  jQuery( '#cashcred-payment-status .entry-date' ).html(response.date); 
                  jQuery('#cashcred_post_ststus select').get(0).selectedIndex = 1;
                  jQuery( '#user_total_cashcred' ).html(response.total); 
    
                }
                
                
                if( jQuery('#cashcred-developer-log').length && response.log != null ) {
                
                  jQuery( '#cashcred-developer-log .inside' ).html( response.log ); 
                
                }
                
              },
              error: function(xhr) {
              
                // if error occured
                alert("Error occured.please try again");
    
                jQuery( '#payment_response' ).html(xhr.responseText);
                jQuery( '#payment_response' ).addClass('false');					
                jQuery( btn_paynow ).prop('disabled', false);
                jQuery( '.cashcred_paynow_text' ).text('Pay Now');
              },
              complete: function() {
                
                jQuery(spinner).removeClass('is-active');
                
              }
            });
      }
       
 
       })(window);	
}//payment fc end