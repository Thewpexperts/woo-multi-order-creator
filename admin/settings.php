<?php if ( ! defined( 'ABSPATH' ) ) die('Access Restricted');  ?>
<h2><?php _e('Multi Order Settings', 'woo-multi-order'); ?></h2> 
<?php if(	(isset($_POST['enableMultiOrder']) && !empty($_POST['enableMultiOrder'])) 
					|| (isset($_POST['dayDiff']) && !empty($_POST['dayDiff'])) 
					|| (isset($_POST['customOrderPermit']) && !empty($_POST['customOrderPermit']))){
						if ( !wp_verify_nonce( $_POST['wpwmoc_pwd_nonce'], "wpwmoc_pwd_nonce")) {
								 die( 'Security check' ); 
						}
		 
	update_option('dayDifference',intval($_POST['dayDiff']));
	update_option('customOrderPermit',intval($_POST['customOrderPermit']));
	update_option('enableMultiOrder',intval($_POST['enableMultiOrder']));
	$successMsg = '<div class="updated"><p>'.__('Settings Saved Successfully', 'wpwmoc-multi-order').'</p></div>';
	echo $successMsg;
} ?>
<form action="" method="post">
	<table style="width:80%;">
		<?php
			$enableMultiOrder = get_option('enableMultiOrder');	
			$enableMultiOrderValue = ($enableMultiOrder==1)?'checked="checked"':''; 
		?>
		<tr>
			<td style="width:30%;"><label style="font-weight:bold;font-size:14px;float:left;"><?php _e('Enable Multiorder.', 'wpwmoc-multi-order'); ?></label></td>
			<td style="width:50%;"><input type="checkbox" value="1" name="enableMultiOrder" <?php echo $enableMultiOrderValue; ?>></td>
		</tr>
		<tr>
			<td style="width:30%;"><label style="font-weight:bold;font-size:14px;float:left;"><?php _e('Order Delivery Day Difference', 'wpwmoc-multi-order'); ?></label></td>
			<td style="width:50%;">
											<select name="dayDiff">
												<option value=""><?php _e('Select Day Difference', 'wpwmoc-multi-order'); ?></option>
												<?php for($i=1;$i<=10;$i++){ 
													$dayDifference = get_option('dayDifference');	
													$selectDays = ($dayDifference == $i)?'selected="selected"':''; 	
												?>
													<option value="<?php echo $i; ?>" <?php echo $selectDays; ?>><?php echo $i; ?></option>
												<?php } ?>
												
											</select>
			</td>
		</tr>
		<?php
			$customOrderPermit = get_option('customOrderPermit');	
			$checkValue = ($customOrderPermit==1)?'checked="checked"':''; 
		?>
		<tr>
			<td style="width:30%;"><label style="font-weight:bold;font-size:14px;float:left;"><?php _e('Customer can choose custom delivery date.', 'wpwmoc-multi-order'); ?></label></td>
			<td style="width:50%;"><input type="checkbox" value="1" name="customOrderPermit" <?php echo $checkValue; ?>></td>
		</tr>
		<tr>
			<td style="width:30%;"><br/><input type="hidden" name="wpwmoc_pwd_nonce" value="<?php echo wp_create_nonce("wpwmoc_pwd_nonce"); ?>" /><button class="button button-primary button-highlighted thickbox"><?php _e('Save Settings', 'wpwmoc-multi-order'); ?></button></td>
			<td style="width:50%;"></td>
		</tr>
	</table>
	
</form>