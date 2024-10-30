jQuery( document ).ready(function() {

  //to purchase points from near wallet
  jQuery("#meritocracy_purchase_points").click(function(){
    //start@#meritocracy_purchase_points
    meritocracy_purchase_points();
    //end@#meritocracy_purchase_points
  });

  //to check user isloggedin or not
  query_wallet("is_signIn");





  jQuery("#meritocracy_account_btn").click(function(){

    if(jQuery(this).val() == "SignOut")
    {
     query_wallet("do_signOut");
    }
    else if(jQuery(this).val() == "SignIn")
    {
      var flag=query_wallet("do_signIn");
      if(flag)
      {

      }


    }
    //start@#meritocracy_purchase_points

    //end@#meritocracy_purchase_points
  });

  //jQuery('.meritocracy_purchase').css('opacity', '0.6');

});//doc load end

    async function query_wallet(str)
      {
        if( !near_wallet.near_admin_account || !near_wallet.near_admin_account)
        {
          alert('Near Credentials are not set up in admin');
          return flag=false;
        }

        var mnwtype='testnet';
        if("mainnet"==near_wallet.near_network){mnwtype='app';}
      const nearConfig = {
          networkId: near_wallet.near_network,
          contractName: near_wallet.near_admin_account,
          nodeUrl: "https://rpc."+near_wallet.near_network+".near.org",
          walletUrl: "https://"+mnwtype+".mynearwallet.com",
          helperUrl: "https://helper."+near_wallet.near_network+".near.org",
          explorerUrl: "https://explorer."+near_wallet.near_network+".near.org"

        };

        const keyStore = new nearApi.keyStores.BrowserLocalStorageKeyStore();
        nearConfig.deps = { keyStore };
        window.near = await nearApi.connect(nearConfig);
        const wallet = new nearApi.WalletConnection(window.near);




  if(str==="is_signIn")
  {
    if(wallet.isSignedIn()) {
    const accountId=  wallet.getAccountId();
    jQuery('#user_near_wallet').val(accountId);
    jQuery('#user_near_wallet').attr('disabled','disabled');
    jQuery("#meritocracy_account_btn").val("SignOut");
    jQuery("#card-errors").html("");
    jQuery(".meritocracy_purchase").show();

      return ;

    } else
    {

      jQuery("#user_near_wallet").attr('disabled','disabled');
    jQuery("#meritocracy_account_btn").val("SignIn");
    jQuery("#card-errors").html("Please Login!");
    jQuery(".meritocracy_purchase").hide();
      return ;
    }
  }


  if(str=="do_signIn")
  {
  //   var userContact =document.getElementById("user_near_wallet").value;
  //   var userContactID=userContact.concat('.', near_wallet.near_network);

  //  if(!userContact) {
  //      alert('Please insert your near wallet id');
  //      return false;
  //  }
    const signIn = await wallet.requestSignIn('');
      if(wallet.isSignedIn()) {
        jQuery("#user_near_wallet").attr('disabled','disabled');
        jQuery("#meritocracy_account_btn").val("SignOut");
        jQuery("#card-errors").html("");

      }
      else{
        jQuery("#user_near_wallet").attr('disabled','disabled');
        jQuery("#meritocracy_account_btn").val("SignIn");
        jQuery("#card-errors").html("Please Login!");
      }

      return ;
  }

  if(str=="do_signOut")
  {
    wallet.signOut();
    jQuery("#user_near_wallet").attr('disabled','disabled');
    jQuery("#user_near_wallet").val("");
    jQuery("#meritocracy_account_btn").val("SignIn");
    jQuery(".meritocracy_purchase").hide();
    jQuery("#card-errors").html("Please Login!");
    return;
  }
  }





function meritocracy_purchase_points() {
             jQuery('.meritocracy_purchase').css('opacity', '0.6');
            jQuery('#meritocracy_purchase_points').attr('disabled',true);

      var userContact =document.getElementById("user_near_wallet").value;
     var pointsToPurchase = document.getElementById("purchase_points").value;
     var userCID='';
     if("mainnet"==near_wallet.near_network)
     {
       userCID=near_wallet.near_admin_account;
     }
     else{
      userCID=near_wallet.near_admin_account.concat('.', near_wallet.near_network);
     }
     var userContactID=userCID;

    if(!userContact) {
        alert('Please insert your near wallet id or sign in again!');

        jQuery('.meritocracy_purchase').css('opacity', '1');
        jQuery('#meritocracy_purchase_points').attr('disabled',false);
        return;
    }

    if(!pointsToPurchase || pointsToPurchase<=0) {
        alert('Please insert ponts to purchase');

        jQuery('.meritocracy_purchase').css('opacity', '1');
        jQuery('#meritocracy_purchase_points').attr('disabled',false);
        return;
    }

    var mnwtype='testnet';
    if("mainnet"==near_wallet.near_network){mnwtype='app';}
    const nearConfig = {
      networkId: near_wallet.near_network,
      contractName: near_wallet.near_admin_account,
      nodeUrl: "https://rpc."+near_wallet.near_network+".near.org",
      walletUrl: "https://wallet."+mnwtype+".mynearwallet.com",
      helperUrl: "https://helper."+near_wallet.near_network+".near.org",
      explorerUrl: "https://explorer."+near_wallet.near_network+".near.org"

    };
    (async function ()
    {

         const keyStore = new nearApi.keyStores.BrowserLocalStorageKeyStore();
         nearConfig.deps = { keyStore };
         window.near = await nearApi.connect(nearConfig);
         const wallet = new nearApi.WalletConnection(window.near);

          const signIn =  wallet.requestSignIn(
            userContactID // contract requesting access
            );

          if(wallet.isSignedIn())
          {
            var adminCID='';
            const walletAccountObj = wallet.account();
            if("mainnet"==near_wallet.near_network)
            {
              adminCID=near_wallet.near_admin_account;
            }
            else{
              adminCID=near_wallet.near_admin_account.concat('.', near_wallet.near_network);
            }

            const adminContactID = adminCID;
            const tokenForPurchase = (parseFloat(pointsToPurchase) * parseFloat(near_wallet.near_exchange_rate));

            const { utils } = nearApi;
            const tokenForPurchasePoints = utils.format.parseNearAmount(String(tokenForPurchase));




            try {
              var response = await walletAccountObj.sendMoney(
                adminContactID, // receiver account
                tokenForPurchasePoints // amount in yoctoNEAR
              );
            } catch(err) {
              // catches errors both in fetch and response.json
              alert(err);
            }


            var receipt_id = '';
            if (response.transaction_outcome.outcome.status.SuccessReceiptId)
            {
                receipt_id = response.transaction_outcome.outcome.status.SuccessReceiptId;
            }

            var data = {
                'action': 'meritocracy_purchase_points',
                'pointsToPurchase': pointsToPurchase,
                'meritocracy_purchase_nonce': near_wallet.meritocracy_purchase_nonce,
                'tx_receipt_id': receipt_id,
                'tx_response': response,
                'point_type_key' : near_wallet.point_type_key,

            };
            jQuery.post(near_wallet.ajax_url, data, function(response) {
              jQuery('.meritocracy_purchase').css('opacity', '1');
        jQuery('#meritocracy_purchase_points').attr('disabled',false);
        jQuery("#purchase_points").val("0");
                var response_parsed = JSON.parse(response)

                alert(response_parsed.msg);
                if('Points Successfully Purchased'==response_parsed.msg)
                {
                  //thankyou redirect
                  window.location.replace(near_wallet.thankyou_url);
                }


            });
         }

       })(window);
}//main fc end


// //buycred_front end form-> wallet id validation
// jQuery('#user_near_wallet').on('keypress', function (event) {
//     var regex = new RegExp("^[a-zA-Z0-9]+$");
//     var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
//     if (!regex.test(key)) {
//        event.preventDefault();
//        return false;
//     }
// }).on('keyup', function (event) {
//   var text= jQuery(this).val();
//   text=text.toLowerCase();
//   jQuery(this).val(text);

// });
