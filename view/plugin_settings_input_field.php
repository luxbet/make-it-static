<?php
/**
 * Generic field input that can be used to display other types of settings
 * Note that the name of the input is always created using the constant table field as this is what wordpress use to identify which option field table to be saved into
 * User: budiartoa
 * Date: 16/03/12
 * Time: 1:30 PM
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 *@license http://www.opensource.org/licenses/BSD-3-Clause
 */

//get the default values already set in the database
$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);

if ($current_settings_type == 'input') {
?>

	<input id='<?=$current_settings_field_id?>' name='<?=MakeItStatic::CONFIG_TABLE_FIELD;?>[<?=$current_settings_field_name?>]' size='<?=$current_settings_field_size?>' type='text' value='<?=$options[$current_settings_field_name]?>' />

<?php } else if ($current_settings_type == 'textarea') {?>
	<textarea cols="80" rows="10" id='<?=$current_settings_field_id?>' name='<?=MakeItStatic::CONFIG_TABLE_FIELD;?>[<?=$current_settings_field_name?>]'><?=$options[$current_settings_field_name]?></textarea>
<?php } ?>