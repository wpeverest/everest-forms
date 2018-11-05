=== Everest Forms - Easy Contact Form and Form Builder for WordPress ===
Contributors: WPEverest
Tags: contact form, form, form builder, contact, custom form
Requires at least: 4.0
Tested up to: 4.9
Requires PHP: 5.4
Stable tag: 1.3.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Drag and Drop form builder to easily create contact forms and more.

== Description ==
Everest Forms plugin provides you with an easy way to create any kind of forms including contact forms. Drag and Drop fields make ordering and creating forms so easy that even a beginner to WordPress can create beautiful forms within minutes. The plugin is lightweight, fast, extendible and 100% mobile responsive.

Everest Forms is specially designed keeping the usability, simplicity in mind. The form settings, admin panels are highly intuitive with a clean design.

Multiple column forms can be designed with a click. Pre-built templates and design layouts allow you to create forms that look different yet beautiful.


View [All features](https://wpeverest.com/wordpress-plugins/everest-forms/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme)

View [Demo](http://demo.wpeverest.com/everest-forms/)

Get [free support](https://wpeverest.com/support-forum/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme)

Check [documentation](http://docs.wpeverest.com/docs/everest-forms/)

### Features And Options:
* Create unlimited forms without any restrictions
* Drag and Drop Form fields
* Supports all commonly used form fields including radio, dropdowns, checkboxes, date and more
* 100% responsive form template
* Supports multiple column layout
* Shortcode support
* Multiple email recipient
* Smart Tags for dynamic email message, subject and more.
* View Form entries from your dashboard
* Quick Form Preview option
* CSV exporter for entries
* Provides two different form template design
* Google Recaptcha Supported
* Editable successful form submission message
* Redirect option after submission
* Editable Email Settings
* Editable form validation message
* Translation ready

### Premium Features and Addons
* 9 Advanced fields (Image upload, file upload, Hidden Field, Phone, Password, Custom HTML, Section Title, Address, Country)

* [MailChimp](https://wpeverest.com/wordpress-plugins/everest-forms/mailchimp/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Allows your users to subscribe to MailChimp via Everest Forms

* [ConvertKit](https://wpeverest.com/wordpress-plugins/everest-forms/convertkit/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Allows you to connect your form with ConvertKit to collect subscribers

* [PDF Form Submission](https://wpeverest.com/wordpress-plugins/everest-forms/pdf-form-submission/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Create PDF for  submitted form entries and attach to emails as well.

* [PayPal Standard](https://wpeverest.com/wordpress-plugins/everest-forms/paypal-standard/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Allows you to connect your forms site with PayPal to easily collect payments, donations, and online orders.

* [Geolocation](https://wpeverest.com/wordpress-plugins/everest-forms/geolocation/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Allows you to collect geolocation data like IP, Country, Postal and zip code along with the form submission.

* [Multi-Part Forms](https://wpeverest.com/wordpress-plugins/everest-forms/multi-part-forms/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Allows you to break long or complex forms up into multiple pages.

* [Conditional Logic](https://wpeverest.com/wordpress-plugins/everest-forms/conditional-logic/?utm_source=wporg&utm_medium=link&utm_campaign=everest-forms-readme) - Allows you to conditionally hide or show form fields based on user's interaction with other form fields.

### What's Next

Checkout out our other projects for WordPress

[ThemeGrill](https://themegrill.com/wordpress-themes/free/) - Beautiful Free WordPress Themes

[BeautifulThemes](https://beautifulthemes.com) - Collection of WordPress Themes by Well Renowned Authors.

== Installation ==

1. Install the plugin either via the WordPress.org plugin directory, or by uploading the files to your server (in the /wp-content/plugins/ directory).
2. Activate the Everest Forms plugin through the 'Plugins' menu in WordPress.
3. Go to Everest Forms->Add New and start creating the form.

== Frequently Asked Questions ==

= What is the plugin license? =

* This plugin is released under a GPL license.

= Does the plugin work with any WordPress themes?

Yes, the plugin is designed to work with any themes that have been coded following WordPress guidelines.

== Screenshots ==

1. Form Fields
2. Form Field Options
3. Form General Settings
4. Form Email Settings
5. Settings General
6. Settings Recaptcha
7. Settings Email
8. Settings Validation
9. Simple Contact Form 1
10. Simple Contact Form 2
11. Advance Form

== Changelog ==

= 1.3.3 - 05-11-2018 =
* Feature - Added a filter to process Email conditionally.
* Fix - Checkbox and radio input design issue in front-end.
* Fix - PerfectScrollbar console error in forms list table.
* Fix - Compatibility with different pagebuilder for flatpicker styles.
* Tweak - Required field asterisk alignment style issue.
* Tweak - Field container should not be overridden by theme margin.
* Tweak - Added button class in submit button for multiple theme compat.

= 1.3.2 - 31-10-2018 =
* Feature - Multi-Part addon support.
* Feature - "Reply to" address support on email.
* Feature - Introduced debugging information logger.
* Tweak - Smooth drag and drop inside form builder area.
* Tweak - jQuery UI sortable placing space while dragging.
* Tweak - Frontend form fields UI and submit button design.
* Tweak - Remove text decoration from abbr tag required field.
* Fix - Dashicon plus icon position while adding new field.
* Fix - Display error notice on empty or invalid nonce check.
* Fix - Hook into `in_admin_header` to hide unrelated notices.
* Fix - Memory limit values not correctly converting to bytes.
* Fix - Live appending fields on Integration Conditional Logic.
* Fix - Correctly handle shorthand values for memory_limit in `php.ini`.
* Fix - Enable FlatPickr on mobile devices instead of HTML5 date inputs.
* Fix - Change the query used to save session data to the database to protect against deadlocks.

= 1.3.1 - 09-10-2018 =
* Feature - Introduced form preview.
* Feature - Conditional Logics addon support.
* Fix - Meta key required for HTML and title field.
* Fix - Empty form fields data added during form save.
* Fix - Spacing of checkbox and radio button on form builder.
* Fix - cron_interval property support in `WP_Background_Processing`.
* Fix - Check post type before fetching form so error are not thrown.
* Tweak - Image upload icon used for field.
* Tweak - Introducing privacy policy content.
* Dev - Added `everest_forms_builder_fields_tab` and `everest_forms_builder_fields_tab_content` action hook.

= 1.3.0 - 11-09-2018 =
* Feature - CSV exporter for entries.
* Feature - Introduced Addons fetaures page.
* Fix - Navigation tab colors.
* Fix - Meta key field required.
* Fix - Ordering of forms fields after sorting.
* Fix - Placeholder and default value for select.
* Fix - Validation on change for meta key and CSS class name.
* Dev - Integration pannel for Everest Forms Pro.
* Dev - Everest Forms Pro fields registered.
* Deprecated - `evf_get_us_states` function is deprecated.

[See changelog for all versions](https://github.com/wpeverest/everest-forms/raw/master/CHANGELOG.txt).
