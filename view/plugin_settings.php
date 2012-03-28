<?php
/**
 * plugin_settings.php
 * This file contains mostly the HTML to display the plugin settings.
 * All variables to be displayed should be defined in the main MakeItStatic class
 *
 * @author: budiartoa
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */


?>

<div class="wrap">
	<h2>Make it Static! - configuration</h2>
	<form method="post" action="options.php">
		<?php
		settings_fields($settings_group_name); //Output nonce, action, and option_page fields for a settings page.

		?>
		<?php
		//output all the input fields -- same as the page name in add_settings_section
		do_settings_sections('make_it_static_plugin');
		?>

		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form>
</div>