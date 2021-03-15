<?php
/**
 * The template for displaying MoceanAPI Abandoned Carts Exit Intent form
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/moceanapi-abandoned-carts-exit-intent.php or
 * yourtheme/moceanapi-abandoned-carts-exit-intent.php

 * Please note that you should not change the ID (moceanapi-abandoned-carts-exit-intent-email) of the Email input field.
 * If changed, the abandoned cart will not be captured
 *
 * If MoceanAPI Abandoned Carts plugin will need to update template files, you
 * might need to copy the new files to your theme to maintain compatibility.
 * 
 * @package    MoceanAPI Abandoned Carts/Templates
 * @author     Micro Ocean Technologies
 * @version    1.1.0
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}
$public = new MoceanAPI_Public(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
$image_id = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_image'));
$image_url = $public->get_plugin_url() . '/public/assets/abandoned-shopping-cart.gif';
if($image_id){
	$image = wp_get_attachment_image_src( $image_id, 'full' );
	if(is_array($image)){
		$image_url = $image[0];
	}
}
?>

<div id="moceanapi-abandoned-carts-exit-intent-form" class="moceanapi-abandoned-carts-ei-center">
	<div id="moceanapi-abandoned-carts-exit-intent-form-container" style="background-color:<?php echo $args['main_color']; ?>">
		<div id="moceanapi-abandoned-carts-exit-intent-close">
			<?php echo apply_filters( 'moceanapi_abandoned_carts_exit_intent_close_html', sprintf('<svg><line x1="1" y1="11" x2="11" y2="1" stroke="%s" stroke-width="2"/><line x1="1" y1="1" x2="11" y2="11" stroke="%s" stroke-width="2"/></svg>', $args['inverse_color'], $args['inverse_color'] ) ); ?>
		</div>
		<div id="moceanapi-abandoned-carts-exit-intent-form-content">
			<?php do_action('moceanapi_abandoned_carts_exit_intent_start'); ?>
			<div id="moceanapi-abandoned-carts-exit-intent-form-content-l">
				<?php echo wp_kses_post( apply_filters( 'moceanapi_abandoned_carts_exit_intent_image_html', sprintf('<img src="%s" alt="" title=""/>', $image_url ) ) ); ?>
			</div>
			<div id="moceanapi-abandoned-carts-exit-intent-form-content-r">
				<?php echo wp_kses_post( apply_filters( 'moceanapi_abandoned_carts_exit_intent_title_html', sprintf(
					/* translators: %s - Color code */
					__( '<h2 style="color: %s">You were not leaving your cart just like that, right?</h2>', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), $args['inverse_color'] ) ) ); ?>
				<?php do_action('moceanapi_abandoned_carts_exit_intent_after_title'); ?>
				<?php echo wp_kses_post( apply_filters( 'moceanapi_abandoned_carts_exit_intent_description_html', sprintf(
					/* translators: %s - Color code */
					__( '<p style="color: %s">Just enter your email below to save your shopping cart for later. And, who knows, maybe we will even send you a sweet discount code :)</p>', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), $args['inverse_color'] ) ) );?>
				<form>
					<?php do_action('moceanapi_abandoned_carts_exit_intent_before_form_fields'); ?>
					<?php echo wp_kses_post( apply_filters( 'moceanapi_abandoned_carts_exit_intent_email_label_html', sprintf('<label for="moceanapi-abandoned-carts-exit-intent-email" style="color: %s">%s</label>', $args['inverse_color'], __('Your email:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN) ) ) ); ?>
					<?php echo apply_filters( 'moceanapi_abandoned_carts_exit_intent_email_field_html', '<input type="email" id="moceanapi-abandoned-carts-exit-intent-email" size="30" required >' ) ; ?>
					<?php echo wp_kses_post( apply_filters( 'moceanapi_abandoned_carts_exit_intent_button_html', sprintf('<button type="submit" name="moceanapi-abandoned-carts-exit-intent-submit" id="moceanapi-abandoned-carts-exit-intent-submit" class="button" value="submit" style="background-color: %s; color: %s">%s</button>', $args['inverse_color'], $args['main_color'], __('Save cart', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN) ) ) ); ?>
				</form>
			</div>
			<?php do_action('moceanapi_abandoned_carts_exit_intent_end'); ?>
		</div>
	</div>
	<div id="moceanapi-abandoned-carts-exit-intent-form-backdrop" style="background-color:<?php echo $args['inverse_color']; ?>; opacity: 0;"></div>
</div>