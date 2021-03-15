(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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

	 	$('.moceanapi-abandoned-carts-exit-intent-color-picker').wpColorPicker(); //Activating color picker

		function addLoadingIndicator(){ //Adding loading indicator once Submit button pressed
			$(this).parent().addClass('moceanapi-abandoned-carts-loading');
		}

		function replaceExitIntentImage(e){ //Replacing Exit Intent image
			e.preventDefault();
			var button = $(this),
			custom_uploader = wp.media({
				title: 'Add custom Exit Intent image',
				library : {
					type : 'image'
				},
				button: {
					text: 'Use image'
				},
				multiple: false
			}).on('select', function(){ //It also has "open" and "close" events
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				var image_url = attachment.url;
 				if(typeof attachment.sizes.thumbnail !== "undefined"){ //Checking if the selected image has a thumbnail image size
 					var thumbnail = attachment.sizes.thumbnail.url;
 					image_url = thumbnail;
 				}
				button.html('<img src="' + image_url + '">');
				$('#moceanapi_abandoned_carts_exit_intent_image').val(attachment.id);
				$('#moceanapi-abandoned-carts-remove-image').show();
			}).open();
		}

		function removeExitIntentImage(e){ //Removing Exit Intent image
			e.preventDefault();
			var button = $(this).hide();
			$('#moceanapi_abandoned_carts_exit_intent_image').val('');
			$('#moceanapi-abandoned-carts-upload-image').html('<input type="button" class="button" value="Add custom image">');
		};
		
		jQuery("#moceanapi-abandoned-carts-page-wrapper #submit").on("mousedown", addLoadingIndicator );
		jQuery("#moceanapi-abandoned-carts-upload-image").on("click", replaceExitIntentImage );
		jQuery("#moceanapi-abandoned-carts-remove-image").on("click", removeExitIntentImage );
	});

})( jQuery );