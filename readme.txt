=== MoceanAPI Abandoned Carts for WooCommerce ===
Contributors: moceanapiplugin
Tags: mocean, sms, woocommerce, abandoned carts, abandon carts, cart abandonment, sms notification, notification, exit popup
Requires at least: 4.6
Tested up to: 5.6.1
WC tested up to: 4.9.2
Stable tag: 1.1.0
Requires PHP: 5.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A plugin to save abandoned carts and send SMS notification to both admin and customer after received abandoned carts in WooCommerce. SMS notification can be sent to admin and all customers who have abandoned carts as well as with customized contents.

== Description ==

A plugin can catch abandoned carts in [WooCommerce](https://woocommerce.com) checkout form and send sms notification to admin and customer for recovering abandoned carts. This plugin showing the customers who have abandoned carts in a table with their details such as name, email, phone number, location, abandoned items, total amount and time.   

Cart is considered as abandoned only after customer save it in their cart more than 60 minutes. If customer enable email notification, an email will send to admin or specific email address once triggered abandoned carts. After that, if admin didn't take any action like send email or send sms to customers, a sms notification will send to admin or specific phone number for recovering the abandoned carts.  

User's details will be removed from abandoned carts table once users complete the payment and reaches WooCommerce "Thank you" page.

Try for FREE. 20 trial SMS credits will be given upon [registration](https://dashboard.moceanapi.com/register?fr=wp). Additional SMS credits can be requested and is subject to approval by MoceanSMS.

Features:

*	Save WooCommerce checkout field's data immediately.
*	Listed all abandoned carts with user details in a table with clear format.
*	Update Cart's item listed in abandoned carts immediate when checkout fields updated
*	Notify admin whever there's new abandoned carts by email and sms. Notification frequency is able to customize by user.
*	Notify customer by sending Bulk SMS or Email notification to recover abandoned carts.
*	SMS and Email contents can be customized for admin or customers who having abandoned cart.
*	Help to save unregistered user's carts by enabling "Exit Intent popup" function. 

== Installation ==

1. Search for "MoceanAPI Abandoned Carts for WooCommerce" in "Add Plugin" page and activate it.

2. Configure the settings in WooCommerce > MoceanAPI Abandoned Carts.

3. Enjoy.

= Have questions? =

If you have any questions, you can contact our support at support@moceanapi.com

== Frequently Asked Questions ==

= Where is the information in abandoned cart table from? =

Information in table is saved from the fields in WooCommerce Checkout page. f the fields are added outside of Checkout page or Checkout page input field ID values are changed, the plugin will not be able to load data. 
Input field ID values should be default:

* #billing_first_name
* #billing_last_name
* #billing_phone
* #billing_email
* #billing_company
* etc.

= When will the infomation in abandoned cart table saved? =

Information in abandoned cart table is saved right after the user gets to the Checkout form and one of the following events happen:

* Correct email address is entered
* Phone number is entered
* On Checkout page load if email or phone number input fields are already filled
* Any Checkout form input field with a class "input-text" is entered or changed if a valid Email or Phone number has been entered

For ghost carts, the cart will be saved as soon as the user adds an item to his cart if it have been enabled. It will remain as a ghost cart until one of the above events has occurred.

= When would a cart be considered as abandoned? =

A shopping cart only considered as abandoned after 60 minutes of the cart saved. For example, if a cart saved at 2pm, then it will be considered as abandoned only after 3pm.

= How the email and sms notification for admin work? = 

To enable email notification function, you need to set the notification intervals such as "Every hour". Once the cart is saved and is considered as abandoned after 60 minutes, you will receive a notification about it in your email. 

Set the notification intervals also be the way to enable sms notification function. But sms notification will be only send after admin receive email notification for abandoned cart(s) but do not take action for recovering abandoned cart. Action taken are sending bulk email and sms notification to customer. 

Please take note that you will not be notified about previously abandoned carts and received sms notification if action taken for new abandoned cart(s).

= How the bulk email and bulk sms notification for Customer work? = 

This bulk notification is used for recovering abandoned cart by sending email or sms to remind customer about their carts. You can also provide some offer code inside the bulk email content or bulk sms content and send to specific customer in order to increate the rate of recovering abandoned carts. 

You can select "Send SMS" or "Send Email" in bulk actions above abandoned carts table. Email and SMS only able to send when "email" and "phone" fields in abandoned carts table in not empty. 

= WooCommerce order "Failed", but no abandoned cart saved? =

Once a user reaches WooCommerce "Thank you" page - the abandoned cart is automatically removed from the table since the cart is no longer considered as abandoned (regardless of the order status). In this case you can see all of the submitted user data under WooCommerce > Orders.

= Why I cannot receive Email notification? = 

Please note how WordPress handles Cron job that is responsible for sending out email notifications. Scheduled actions can only be triggered when a visitor arrives on a WordPress site. Therefore, if you are not getting any traffic on your website you will not receive any email notifications until a visitor lands on your website.

By default, notifications will be sent to WordPress registered admin email. But you can also set a different email address at //wordpress/wp-includes/functions.php

function change_from_email( $html ){
	return 'your@gmail.com';
}
add_filter( 'moceanapi_abandoned_carts_from_email', 'change_from_email' );

You also can check about whether can receive email such as new user registered or new order placed from Wordpress . 

= Why my customer and admin unable to receive sms notification? = 

Please ensure that the billing country have been selected or the phone numbers are provided with country code. 
For example, if admin phone number is at Malaysia, the country code will be +60 so the phone number provided will be 60123456789. 


== Screenshots ==

1. Abandoned cart table
2. Email Settings
3. SMS Settings
4. Exit Intent 

== Changelog ==

= 1.1.0 =
Initial version released
