(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 
	 jQuery(document).ready(function(){

		var timer;

		function getCheckoutData() { //Reading WooCommerce field values

			if(jQuery("#billing_email").length > 0){ //If email address exists

				var moceanapi_abandoned_carts_phone = jQuery("#billing_phone").val();
				var moceanapi_abandoned_carts_email = jQuery("#billing_email").val();

				var atposition = moceanapi_abandoned_carts_email.indexOf("@");
				var dotposition = moceanapi_abandoned_carts_email.lastIndexOf(".");

				if (typeof moceanapi_abandoned_carts_phone === 'undefined' || moceanapi_abandoned_carts_phone === null) { //If phone number field does not exist on the Checkout form
				   moceanapi_abandoned_carts_phone = '';
				}
				
				clearTimeout(timer);

				if (!(atposition < 1 || dotposition < atposition + 2 || dotposition + 2 >= moceanapi_abandoned_carts_email.length) || moceanapi_abandoned_carts_phone.length >= 1){ //Checking if the email field is valid or phone number is longer than 1 digit
					//If Email or Phone valid
					var moceanapi_abandoned_carts_name = jQuery("#billing_first_name").val();
					var moceanapi_abandoned_carts_surname = jQuery("#billing_last_name").val();
					var moceanapi_abandoned_carts_phone = jQuery("#billing_phone").val();
					var moceanapi_abandoned_carts_country = jQuery("#billing_country").val();
					var moceanapi_abandoned_carts_city = jQuery("#billing_city").val();
					
					//Other fields used for "Remember user input" function
					var moceanapi_abandoned_carts_billing_company = jQuery("#billing_company").val();
					var moceanapi_abandoned_carts_billing_address_1 = jQuery("#billing_address_1").val();
					var moceanapi_abandoned_carts_billing_address_2 = jQuery("#billing_address_2").val();
					var moceanapi_abandoned_carts_billing_state = jQuery("#billing_state").val();
					var moceanapi_abandoned_carts_billing_postcode = jQuery("#billing_postcode").val();
					var moceanapi_abandoned_carts_shipping_first_name = jQuery("#shipping_first_name").val();
					var moceanapi_abandoned_carts_shipping_last_name = jQuery("#shipping_last_name").val();
					var moceanapi_abandoned_carts_shipping_company = jQuery("#shipping_company").val();
					var moceanapi_abandoned_carts_shipping_country = jQuery("#shipping_country").val();
					var moceanapi_abandoned_carts_shipping_address_1 = jQuery("#shipping_address_1").val();
					var moceanapi_abandoned_carts_shipping_address_2 = jQuery("#shipping_address_2").val();
					var moceanapi_abandoned_carts_shipping_city = jQuery("#shipping_city").val();
					var moceanapi_abandoned_carts_shipping_state = jQuery("#shipping_state").val();
					var moceanapi_abandoned_carts_shipping_postcode = jQuery("#shipping_postcode").val();
					var moceanapi_abandoned_carts_order_comments = jQuery("#order_comments").val();
					var moceanapi_abandoned_carts_create_account = jQuery("#createaccount");
					var moceanapi_abandoned_carts_ship_elsewhere = jQuery("#ship-to-different-address-checkbox");

					if(moceanapi_abandoned_carts_create_account.is(':checked')){
						moceanapi_abandoned_carts_create_account = 1;
					}else{
						moceanapi_abandoned_carts_create_account = 0;
					}

					if(moceanapi_abandoned_carts_ship_elsewhere.is(':checked')){
						moceanapi_abandoned_carts_ship_elsewhere = 1;
					}else{
						moceanapi_abandoned_carts_ship_elsewhere = 0;
					}
					
					var data = {
						action:								"moceanapi_abandoned_carts_save",
						moceanapi_abandoned_carts_email:					moceanapi_abandoned_carts_email,
						moceanapi_abandoned_carts_name:					moceanapi_abandoned_carts_name,
						moceanapi_abandoned_carts_surname:					moceanapi_abandoned_carts_surname,
						moceanapi_abandoned_carts_phone:					moceanapi_abandoned_carts_phone,
						moceanapi_abandoned_carts_country:					moceanapi_abandoned_carts_country,
						moceanapi_abandoned_carts_city:					moceanapi_abandoned_carts_city,
						moceanapi_abandoned_carts_billing_company:			moceanapi_abandoned_carts_billing_company,
						moceanapi_abandoned_carts_billing_address_1:		moceanapi_abandoned_carts_billing_address_1,
						moceanapi_abandoned_carts_billing_address_2: 		moceanapi_abandoned_carts_billing_address_2,
						moceanapi_abandoned_carts_billing_state:			moceanapi_abandoned_carts_billing_state,
						moceanapi_abandoned_carts_billing_postcode: 		moceanapi_abandoned_carts_billing_postcode,
						moceanapi_abandoned_carts_shipping_first_name: 	moceanapi_abandoned_carts_shipping_first_name,
						moceanapi_abandoned_carts_shipping_last_name: 		moceanapi_abandoned_carts_shipping_last_name,
						moceanapi_abandoned_carts_shipping_company: 		moceanapi_abandoned_carts_shipping_company,
						moceanapi_abandoned_carts_shipping_country: 		moceanapi_abandoned_carts_shipping_country,
						moceanapi_abandoned_carts_shipping_address_1: 		moceanapi_abandoned_carts_shipping_address_1,
						moceanapi_abandoned_carts_shipping_address_2: 		moceanapi_abandoned_carts_shipping_address_2,
						moceanapi_abandoned_carts_shipping_city: 			moceanapi_abandoned_carts_shipping_city,
						moceanapi_abandoned_carts_shipping_state: 			moceanapi_abandoned_carts_shipping_state,
						moceanapi_abandoned_carts_shipping_postcode: 		moceanapi_abandoned_carts_shipping_postcode,
						moceanapi_abandoned_carts_order_comments: 			moceanapi_abandoned_carts_order_comments,
						moceanapi_abandoned_carts_create_account: 			moceanapi_abandoned_carts_create_account,
						moceanapi_abandoned_carts_ship_elsewhere: 			moceanapi_abandoned_carts_ship_elsewhere
					}

					timer = setTimeout(function(){
						jQuery.post(public_data.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
						function(response) {
							//console.log(response);
							//If we have successfully captured abandoned cart, we do not have to display Exit intent form anymore
							removeExitIntentForm();
						});
						
					}, 800);
				}else{
					//console.log("Not a valid e-mail or phone address");
				}
			}
		}

		function removeExitIntentForm(){//Removing Exit Intent form
			if($('#moceanapi-abandoned-carts-exit-intent-form').length > 0){ //If Exit intent HTML exists on page
				$('#moceanapi-abandoned-carts-exit-intent-form').remove();
				$('#moceanapi-abandoned-carts-exit-intent-form-backdrop').remove();
			}
		}

		jQuery("#billing_email, #billing_phone, input.input-text, input.input-checkbox, textarea.input-text").on("keyup keypress change", getCheckoutData ); //All action happens on or after changing Email or Phone fields or any other fields in the Checkout form. All Checkout form input fields are now triggering plugin action. Data saved to Database only after Email or Phone fields have been entered.
		jQuery(window).on("load", getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
		
	});

})( jQuery );