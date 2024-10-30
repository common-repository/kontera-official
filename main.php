<?php

include_once "functions.php";

define('KONTERA_ADSON_FULLPAGE','1');
define('KONTERA_ADSON_CONTENT_ONLY','2');
define('KONTERA_ADSON_COMMENT_ONLY','3');

/**
 * Set kontera default settings and parameters
 */

function kontera_add_defaults() {
	add_option('kontera_post_settings', '1');
	add_option('kontera_publisher_id', '');
	add_option('kontera_mobile_id', '');
	add_option('kontera_adlinkcolor', '0000ff');
	add_option('kontera_disabled_pages', '');
	add_option('kontera_immediately_list', '');
	add_option('kontera_contentLinks_homepage', '1');
    add_option('kontera_mobile_opt', '0');	
	add_option('kontera_adson', '1');
	add_option('kontera_disable_new_days', '0');
	
	//if the option is already exists, set it to 1
	update_option('kontera_adson', '1');
}

/**
 * delete kontera options from database
 */
function kontera_delete_plugin_options() {
	delete_option('kontera_post_settings');
	delete_option('kontera_publisher_id');
	delete_option('kontera_mobile_id');
	delete_option('kontera_adlinkcolor');
	delete_option('kontera_disabled_pages');
	delete_option('kontera_immediately_list');
	delete_option('kontera_contentLinks_homepage');
	delete_option('kontera_mobile_opt');
	delete_option('kontera_adson');
	delete_option('kontera_disable_new_days');
}

/**
 * Add kontera css file to the admin side header
 */
function load_kontera_wp_admin_style(){
        wp_register_style( 'kontera_admin_css', plugins_url( 'css/style.css', __FILE__ ) , false, '1.0.0' );
        wp_enqueue_style( 'kontera_admin_css' );
}

/**
 * Add kontera js file to the admin side header
 */
function kontera_enqueue() {
    wp_enqueue_script( 'kontera_admin_script', plugins_url('/js/kontera.js', __FILE__) );
}

/**
 * Add Kontera Ads column to the post list ui
 */
add_filter('manage_posts_columns', 'kontera_columns');

function kontera_columns($columns) {
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = $columns['title'];
	$new_columns['author'] = $columns['author'];
	$new_columns['categories'] = $columns['categories'];
	$new_columns['kontera'] = 'Kontera Ads';
	$new_columns['tags'] = $columns['tags'];
 	$new_columns['comments'] = $columns['comments'];
	$new_columns['date'] = $columns['date'];

    return $new_columns;
}

function kontera_show_columns( $name ) {
    global $post;
    $post_id = $post->ID;
    switch ($name) {
        case 'kontera':
           if(!kontera_is_page_enabled($post_id)){
			echo 'Never';
           } else if(kontera_is_page_show_immediately($post_id)){
			echo 'Immediately';
           } else {
          	echo 'Using Global Settings';
           }
           break;
	}
}

/**
 * Add Kontera control to quick edit box
 */
function kontera_add_quick_edit($column_name) {
    global $post;
	if ($column_name != 'kontera') return;
	?>
    <fieldset class="inline-edit-col-left kontera_add_quick_edit">
	<div class="inline-edit-col">
		<input type="hidden" name="kontera_show_contentLinks_noncename" id="kontera_show_contentLinks_noncename" value="" />
		<input type="checkbox" name="show_contentLinks" id="show_contentLinks" checked="checked" />
		<span class="title">Kontera Ads</span>
	</div>
    </fieldset>
	<?php 
}
 
/**
 * Save quick edit settings
 */
function kontera_save_quick_edit_data($post_id) {
	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return $post_id;	
	// Check permissions
	if ( isset($_POST['post_type']) && 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
		return $post_id;
	}	
	// OK, we're authenticated: we need to find and save the data
	$post = get_post($post_id);
	if (isset($_POST['show_contentLinks']) && ($post->post_type != 'revision')) {
		$widget_set_id = esc_attr($_POST['show_contentLinks']);
		if ($widget_set_id == 'on'){
			unblock_post($post_id);		
		} else {	
			block_post($post_id);	
		}	
	} else {
		$widget_set_id = 0;
	}
	return $widget_set_id;	
}

/**
 * Add Kontera control to bulk edit box
 */
function kontera_quick_edit_bulk( ) {
	?>
	<fieldset class="inline-edit-col-right">
	<div class="inline-edit-col kontera_quick_edit_bulk">
		<div class="inline-edit-group">
			<label for="eventdate" style="font: italic 12px Georgia, serif; float: left; margin-right: 14px;">Kontera Ads</label>
			<span class="input-text-wrap" style="float: left;">
				<select name="knotera_bulk_edit" >
					<option value="-1" >&mdash; No Change &mdash;</option>
					<option value="2" >Show Immediately</option>
					<option value="1" >Use Global Settings</option>
					<option value="0" >Never Show</option>
				</select>
			</span>
		</div>
	</div>
	</fieldset>
	<?php
}        

/**
 * Save the bulk edit new settings
 */
if(isset($_GET['post'])){
	$post_ids = isset($_GET['post']) ? array_map( 'intval', (array) $_GET['post'] ) : explode(',', $_GET['ids']);
	foreach( (array) $post_ids as $post_id ) {
		if(isset($_GET['knotera_bulk_edit'])){
			$meta_val = $_GET['knotera_bulk_edit'];
			if($meta_val == "0"){
				block_post($post_id);
				remove_post_from_immediately_list($post_id);
			} else if($meta_val == '1') {
				unblock_post($post_id);
				remove_post_from_immediately_list($post_id);
			} else if($meta_val == '2') {
				unblock_post($post_id);
				add_post_to_immediately_list($post_id);
			}
		}
	}
}

/**
 * This function echoes the kontera javascript for the configured user
 */
function kontera_echo_javascript() {
	global $post;
	$post_id = $post->ID;
	$kontera_current_page_is_home = is_home();
	$kontera_ads_disabled_on_page = ((kontera_is_young_post() && !kontera_is_page_show_immediately($post_id) ) || !kontera_is_page_enabled($post_id));


	if(($kontera_current_page_is_home && get_option('kontera_contentLinks_homepage') == 1 ) || (!$kontera_current_page_is_home && !$kontera_ads_disabled_on_page && is_single())) {
	    // get the user's settings
	    $publisher_id = get_option('kontera_publisher_id');
		$mobile_id = get_option('kontera_mobile_id');
		
		if ($mobile_id != '') {
			$mobile_id = get_option('kontera_mobile_id');
		}
		else {
			$mobile_id = get_option('kontera_publisher_id');
		}
		
	    $ad_link_color = get_option('color_value','#0000ff');
		
		?>	
	
		<!-- Kontera Ads Inserted by Wordpress Plugin -->
    	<script type="text/javascript">
		var dc_UnitID = 14;
		var dc_PublisherID = <?php echo $publisher_id ?>;
		var dc_AdLinkColor = '<?php echo $ad_link_color ?>';
		var dc_isBoldActive = 'no';
		var dc_open_new_win = 'yes';
		var dc_adprod='ADL';
		
		//Set mobile ID based on user agent (device)
		if (navigator.userAgent.match(/iPhone|iPad|iPod|Android|Blackberry|RIM Tablet|IEMobile/i)) {
		dc_PublisherID = <?php echo $mobile_id ?>;   // set publisher ID to mobile ID
		}

   	 	</script>
		<script type="text/javascript" SRC="http://kona.kontera.com/javascript/lib/KonaLibInline.js"></script>
		<!-- Kontera Ads Inserted by Wordpress Plugin -->
		
    	<?php
		}
}

/**
 * Adds the Kontera Settings menu to the Options page
 */
function kontera_menu() {
	//add_options_page('Kontera Settings', 'Kontera Settings', manage_options, __FILE__, 'kontera_options_menu');
	add_menu_page('Kontera Settings', 'Kontera Settings', 'manage_options', __FILE__, 'kontera_options_menu', plugins_url( 'favicon.png', __FILE__ ));
}

/**
 * Output the options menu page, including the form that updates the user's settings
 */
function kontera_options_menu() {

	//echo "curr_publisher_id:".get_option('kontera_publisher_id')."</br>";
	//echo "$_POST['kontera_publisher_id']:".$_POST['kontera_publisher_id']."</br>";
	//echo "curr_mobile_id:".get_option('kontera_mobile_id')."</br>";
	//echo "$_POST['kontera_mobile_id']:".$_POST['kontera_mobile_id']."</br>";

	if (get_option('kontera_mobile_id') == $_POST['kontera_mobile_id']){	
	$mobileunchange = true;
	//echo "mob id didn't change</br>";
	}

	if (isset($_POST['kontera_publisher_id'])){
		$isValidPublisherNum = (preg_match('/^\d{3,}$/', $_POST['kontera_publisher_id']));
		//$isValidPublisherId = true;
		
		if ($isValidPublisherNum) {
		
			if ($_POST['kontera_publisher_id'] == get_option('kontera_mobile_id') && $mobileunchange == true) {
		
			$isValidPublisherId = false;
			//echo "pub id same as mobile id and mob id didn't change</br>";
			}
			else {
			$isValidPublisherId = true;
			//echo "pub id not same as mobile id or mob id did change</br>";
			}
		}
		else {
			
			$isValidPublisherId = false;
			//echo "Not a valid publisher number due to length or chars in string</br>";
		}
	}	
	 else {
		$isValidPublisherId = false;
		//echo "No value for Publisher ID sent";
	}

	if (isset($_POST['kontera_mobile_id'])){
		$isValidMobileNum = (preg_match('/^\d{3,}$/', $_POST['kontera_mobile_id']));
		
		if ($_POST['kontera_publisher_id'] != $_POST['kontera_mobile_id']) {
			$isMobileNotSame = true;
			//echo "Mobile ID not same as publisher ID</br>";
			}
		else {
			//echo "Mobile ID is same as publisher ID</br>";
			
			if ($mobileunchange == true) {
				//echo "Mobile ID hasn't changed";
				$isMobileNotSame = true;
				}
			
			else {
				//echo "Mobile ID has changed";
				$isMobileNotSame = false;
				}
			}
		
		if ($isMobileNotSame == true && $isValidMobileNum == true) {
			$isValidMobileId = true;
			//echo "Mobile ID not same as publisher ID AND is valid numerically</br>";
			}
		
		//$isValidMobileId = (preg_match('/^\d{3,}$/', document.konteta.kontera_mobile_id.value));
	} else {
		$isValidMobileId = false;
		//echo "Mobile ID is same as Publisher OR is not valid numerically!";
	}
	
	if (isset($_POST['kontera_disable_new_days'])){
		$isValidBlockDays = (preg_match('/^\d+$/', $_POST['kontera_disable_new_days']));
	} else {
		$isValidBlockDays = false;
	}
	
	if (isset($_POST['new_block_post'])){
		$isValidPostID = isValidPostID($_POST['new_block_post']);
	} else {
		$isValidPostID = false;
	}
	
	if (isset($_POST['action']) && $_POST['action'] == 'update'){
		if($isValidPublisherId) {
			update_option('kontera_publisher_id', $_POST['kontera_publisher_id']);
		}
		
		//SET MOBILE ID ON THESE CODITIONS:
		//a) Mobile Reporting checkbox is selected and $isValidMobileId = true
		
		
		if($isValidMobileId && $_POST['kontera_show_mobile_opt'] == 'on') {
			update_option('kontera_mobile_id', $_POST['kontera_mobile_id']);
		}
		else {
		
		
			if ($_POST['kontera_show_mobile_opt'] != 'on') {
				update_option('kontera_mobile_id', '');
			}
			
		}
		
		if($isValidBlockDays) {
			update_option('kontera_disable_new_days', $_POST['kontera_disable_new_days']);
		}

		if (isset($_POST['new_block_post']) && $_POST['new_block_post'] != '') {
			if($isValidPostID) {
				block_post($_POST['new_block_post']);
			}
		} else {
			$isValidPostID = 1;
		}
		
		if (isset($_POST['kontera_show_contentLinks_homepage']) && $_POST['kontera_show_contentLinks_homepage'] == 'on'){
			update_option('kontera_contentLinks_homepage', '1');
		} else {
			update_option('kontera_contentLinks_homepage', '0');
		}
		
		if (isset($_POST['kontera_show_mobile_opt']) && $_POST['kontera_show_mobile_opt'] == 'on'){
			update_option('kontera_mobile_opt', '1');
		} else {
			update_option('kontera_mobile_opt', '0');
		}
	
		if (isset($_POST['show_contentLinks_control']) &&  $_POST['show_contentLinks_control'] == 'on'){
			update_option('kontera_adson', '1');
		} else {
			update_option('kontera_adson', '0');
			$isValidBlockDays = 1;
		}
	
		if (isset($_POST['kontera_post_settings']) && $_POST['kontera_post_settings'] != get_option('kontera_post_settings')){
			update_option('kontera_post_settings', $_POST['kontera_post_settings']);
		}
		if (isset($_POST['color_value'])){
		
			if (!!$_POST['color_value']) { 
		
				update_option('color_value', $_POST['color_value']);			
			}
			else {
				update_option('color_value', '#0000ff');
			}
		
		}
		
		
	} else {
		$isValidPublisherId = $isValidBlockDays = $isValidPostID = 1;
	}
	
	if(get_option('kontera_publisher_id') == ''){
		//$curr_publisher_id = 'Enter your Publisher ID';
		$placeholderp='Enter your Publisher ID';
	} else {
		$curr_publisher_id = get_option('kontera_publisher_id');
	}
	
	if(get_option('kontera_mobile_id') == ''){
		//$curr_mobile_id = 'Enter your Mobile Publisher ID';
		$placeholderm='Enter your Mobile Publisher ID';
	} else {
		$curr_mobile_id = get_option('kontera_mobile_id');
	}
		
	// define plugin parameters and descriptions
	$parameters = array (
		'kontera_publisher_id' => 'Publisher ID:',
		'kontera_mobile_id' => 'Mobile ID:',
		'kontera_homepage_settings' => 'Homepage Settings:',
		'kontera_post_settings' => 'Post Settings:',
		'kontera_disabled_pages' => 'Block Posts:',
	
		);
	
	
    echo "<div class='wrap' dir='ltr'><h2> Kontera Global Settings </h2><form method='post' action='' name='konteta' ><table class='main_settings'>";

	//Publisher ID
	echo "<tr><td class='headline'><h3>". $parameters[kontera_publisher_id]. "</h3></td> <td class='content' style='border:0px solid; padding-bottom:20px;'><input type='text' class='pubid' name='kontera_publisher_id' placeholder='".$placeholderp."' onfocus=\"this.placeholder = ''\" onblur='isValidPublisherId()' value='".$curr_publisher_id."' /> ", (!$isValidPublisherId) ? "<font style=' color:red'>Please enter a valid publisher id</font>" : "", "</br>";
	
	echo "Visit the <a href='https://publishers.kontera.com/' target='_blank'>Publisher Center</a> to find your ID.</br>";
	
	
	echo "<p id=\"kontera_show_mobile_opt\"><input type=\"checkbox\" name=\"kontera_show_mobile_opt\"  onclick=\"checkboxmobile()\"";
	if(get_option('kontera_mobile_opt') == '1' ){
			echo "checked=\"checked\"";
		}
	echo "/>
	
	Report my mobile (smartphone and tablet) traffic and revenue separately from my desktop traffic and revenue.</br></p><p id=\"mobhint\">This allows Kontera to maximize your revenue generated by mobile viewers on your WordPress site.</p>";
	
	if (get_option('kontera_mobile_opt') == '1') {
	$mobdisp = 'block';
	}
	else {
	$mobdisp = 'none';
	}
	
	//Mobile ID
	echo "<div id=\"mobilesect\" style=\"margin:-3px 0px -20px;border:0px solid;display:".$mobdisp."\" ><input type=\"text\" name=\"kontera_mobile_id\" placeholder=\"".$placeholderm."\" onfocus=\"this.placeholder = ''\" onblur=\"isValidMobileId()\" class=\"pubid\" value='".$curr_mobile_id."' /><div id=\"mobalert\" name=\"mobalert\" style=\"display:none;\">Your Mobile Publisher ID must be different than your Publisher ID. Please contact your Account Manager or <a href=\"mailto:support@kontera.com?subject=Mobile ID Help Request for Publisher ".$curr_publisher_id."\">support@kontera.com</a> for help.</div>", (!$isValidMobileId && $_POST['kontera_show_mobile_opt'] == 'on') ? "</br><font style=' color:red'>Please enter a valid mobile id</font>" : "", "<br>Your mobile metrics will be recorded under the Mobile Publisher ID entered in the box above.<br>";
	
	
	//Entering a mobile publisher ID in the above box will associate smartphone and tablet traffic, ads and revenue with your Mobile Publisher ID.
	
	echo "<br>Don't have a Mobile Publisher ID? Contact support <a href=\"mailto:support@kontera.com?subject=Mobile ID Request for Publisher ".$curr_publisher_id."\">here</a>.";
	
	echo "
	
	</div><br>";	
	

	echo "</td></tr>";
		
	//Homepage Settings
	echo "<tr style=\"background: #EFEFEF;\"><td><h3 style=\"margin-top:-1px;\"> $parameters[kontera_homepage_settings] </h3></td><td><input type=\"checkbox\" name=\"kontera_show_contentLinks_homepage\" ";
		if(get_option('kontera_contentLinks_homepage') == '1' ){
			echo "checked=\"checked\"";
		}
	echo "/> Show Kontera Ads on Homepage</td></tr>";
	
	//Post Settings
	echo "<tr><td class='headline'><h3>$parameters[kontera_post_settings]</h3></td><td><input type=\"checkbox\" name=\"show_contentLinks_control\" onclick=\"checkboxenable()\" ";
		if(get_option('kontera_adson') == '1' ){
			echo "checked=\"checked\"";
		}
	$curr_post_settings = get_option('kontera_post_settings');
	echo "/> Show Kontera Ads 
			<select name=\"kontera_post_settings\" class=\"kontera_post_settings\">
				<option value=\"1\"";
				if($curr_post_settings == '1'){ echo "selected='selected'" ;}
				echo ">on post and comments</option>
				<option value=\"2\"";
				if($curr_post_settings == '2'){ echo "selected='selected'" ;}
				echo ">on post only</option>
				<option value=\"3\"";
				if($curr_post_settings == '3'){ echo "selected='selected'" ;}
				echo ">on comments only</option>
			</select></td></tr>";
	echo "<tr><td> </td> <td>Posts may show links after <input type=\"text\" name=\"kontera_disable_new_days\" class=\"kontera_disable_new_days\" value=\"" . get_option('kontera_disable_new_days', 0) . "\" /> days. ", (!$isValidBlockDays) ? "<font style=' color:red'>Please enter a valid number of days</font>" : "", "</td></tr>";
	echo "<tr><td> </td> <td>Use the Kontera settings on the individual  posts to override this global setting.</td></tr>";
	
	//Block Posts
	echo "<tr style=\"background: #EFEFEF;\"><td class='headline'><h3> $parameters[kontera_disabled_pages] </h3></td> <td>These posts are currently blocked from showing Kontera Ads:</br><div class='kontera_block_pages'>";
				//<textarea name='kontera_disabled_pages' class='kontera_disabled_pages' readonly='readonly'>";
				$disabled_pages = get_option('kontera_disabled_pages');
				if(!empty($disabled_pages)){
					$disabled_pages = explode(',', $disabled_pages);
					foreach ($disabled_pages as $id) {
						if($id != ''){
							$post = get_post($id); 
							$title = $post->post_title;
							echo "<li id=\"block_post\" class=\"".$id."\"><a class=\"blocked_post\" id=\"".$id."\"><img src=\"".plugins_url()."/kontera/no.png\" /></a>".$title." (".$id.")</li>";
						}
					}
				} else {
					echo "<b>No blocked posts</b>";
				}
				
	//echo "</textarea></td></tr>";
	echo "</div></td></tr>";
	echo "<tr style=\"background: #EFEFEF;\"><td></td> <td>To add a post to this list, enter the ID of the post:</br><input type='text' name='new_block_post' class='new_block_post'/><input type='submit' class='blocksubmit' value='Block'/>", (!$isValidPostID) ? "</br><font style=' color:red'>It appears that the post doesn't exist. Please enter the ID of a post that has been published.</font>" : "","</td></tr>"; 
	
	//Links Settings
	?>	
	<script src="<?php echo plugins_url(); ?>/kontera/js/palette.js" type="text/javascript"></script>
	<link href="<?php echo plugins_url(); ?>/kontera/css/palette.css" media="screen" rel="stylesheet" type="text/css" />
	
	<script type="text/javascript">
    jQuery(document).ready(function() {
    jQuery('#ilctabscolorpicker').hide();
    jQuery('#ilctabscolorpicker').farbtastic("#color_value");
    jQuery("#color_value").click(function(){jQuery('#ilctabscolorpicker').slideToggle()});
  });
	</script>

	<tr>
		<td class='headline'>
			<label for="s_color"><h3>Links Settings</h3></label>
		</td>
		<td>
			<label for="color_value">
			<input type="text" id="color_value" name="color_value" class="kontera_color_select" onchange="set_colorpreview()" value="<?php echo get_option('color_value','#0000ff') ?>" style="margin-right:5px;" /> Select a color for <span  id="color_preview" name="color_preview" onclick="openPalette('color_preview')" style="color:<?php echo get_option('color_value') ?>;">Kontera links</span>
			</label>
			<br><small>Click on the field to display the color picker. Click again to close it.</small>
			<div id="ilctabscolorpicker" onmouseup="set_colorpreview()"></div>
		</td>
	
	</tr>
	
	</table>
	
	<?php 
		// this is needed for some wordpress thing to show which parameters are we updating
		$page_options = implode(',', array_keys($parameters));
	?>

	<input type="hidden" name="page_options" value="<?php echo $page_options ?>" />
	<input type="hidden" name="action" value="update" />
	<BR>	
	<p class="submit">
		<input class="button-primary submit-settings" type="submit" name="Submit" value="<?php _e('Update Kontera Settings &raquo;') ?>" />
	</p>	
	
	<!--</div>-->	

</form>	 
</div>
<?php

}

/**
 * This function add the "KonaBody" class to the content according to the post settings
 */
function kontera_add_zone_tag_to_content($text) {
	global $post;
	$post_id = $post->ID;
	$kontera_current_page_is_home = is_home();
	$kontera_ads_disabled_on_page = ((kontera_is_young_post() && !kontera_is_page_show_immediately($post_id)) || !kontera_is_page_enabled($post_id));
	
	if(kontera_is_ad_enabled()) {
		if($kontera_current_page_is_home){
			if(get_option('kontera_contentLinks_homepage') == 1 && !$kontera_ads_disabled_on_page){
				return '<div class="KonaBody">' . $text . '</div>';
			} else {
				return '<div class="KonaBody"><div class="KonaFilter">' . $text . '</div></div>'; //will make sure that the homepage will have at list one KonaBody class
			}
		} elseif (!$kontera_ads_disabled_on_page) {
			return '<div class="KonaBody">' . $text . '</div>'; 
		}
	}
	return $text;
}

/**
 * Checks if Kontra is enabeld
 */
function kontera_is_ad_enabled() {
	return (get_option('kontera_adson') == '1');
}

/**
 * Show Kontra post control in the post ui
 */
function kontera_block_content_link() {
    global $post;
    $post_id = $post->ID;
	if(get_post_type( $post_id ) == 'post' ){
	?>
	<div class="postbox">
		<h3 class="hndle"><span>Kontera Ads</span></h3>
			<div class="inside" name="konteta_post">
				<?php
				$checked = '';
				if(kontera_is_page_enabled($post_id)) {
					$checked = 'checked="checked"';
				}
				echo '<input type="checkbox" name="display_content_link" '.$checked.' onclick="checkboxenable()"/> Show Kontera Ads';
				echo '<select name="kontera_post_settings" class="kontera_post_settings" >';
				echo '<option value="1" ';
				if(!kontera_is_page_show_immediately($post_id)){ echo 'selected="selected"' ;}
				echo '>Using The Global Settings</option>
					<option value="2" ';
				if(kontera_is_page_show_immediately($post_id)){ echo 'selected="selected"' ;}
				echo '>Immediately</option>
					</select></br></br>';
				$days = get_option('kontera_disable_new_days');
				if(get_option('kontera_adson')){
					if($days > 0){
						echo 'Your current Global Setting is to show links after '.$days.' days.';
					} else {
						echo 'Your current Global Setting is to show links immediately';
					}
				} else {
					echo 'Your current Global Setting is not to show any ContentLinks';
				}
				?>
			</div>
	</div>

	<?php
	}
}

/**
 * Update kontera option according to the post control box
 */
function kontera_write_post() {
    if($_POST){
        $post_id = $_POST['post_ID'];
        if(isset($_POST['display_content_link'])){
	    	$display_content_link = $_POST['display_content_link'];
			$single_post_settings = $_POST['kontera_post_settings'];
	    	if ($single_post_settings == '2') {
	    	unblock_post($post_id);
	    	add_post_to_immediately_list($post_id);
	    	} else {
            unblock_post($post_id);
            remove_post_from_immediately_list($post_id);
	    	}
        } else {
        	block_post($post_id);
            remove_post_from_immediately_list($post_id);
        }
    }
}

/**
 * This function call the "kontera_add_zone_tag_to_content" to place the KonaBody class in the right content
 */
function addKonaBodyClass(){
	if(get_option('kontera_post_settings') == KONTERA_ADSON_FULLPAGE){
		add_filter('the_content', 'kontera_add_zone_tag_to_content'); 
		add_filter('comment_text', 'kontera_add_zone_tag_to_content'); 
	} elseif (get_option('kontera_post_settings') == KONTERA_ADSON_CONTENT_ONLY){
		add_filter('the_content', 'kontera_add_zone_tag_to_content'); 
	} elseif (get_option('kontera_post_settings') == KONTERA_ADSON_COMMENT_ONLY){
		add_filter('comment_text', 'kontera_add_zone_tag_to_content'); 
	}
}

/**
 * if Kontera is enabled load the Kontera javascript to the page footer
 */
if(kontera_is_ad_enabled()){
	add_action('wp_footer', 'kontera_echo_javascript');
}


/**
 * Add actions
 */
add_action('template_redirect', 'addKonaBodyClass');
add_action('admin_enqueue_scripts', 'load_kontera_wp_admin_style');
add_action('admin_enqueue_scripts', 'kontera_enqueue');
add_action('manage_posts_custom_column' , 'kontera_show_columns');
add_action('quick_edit_custom_box',  'kontera_add_quick_edit', 'kontera');
add_action('save_post', 'kontera_save_quick_edit_data');
add_action('bulk_edit_custom_box', 'kontera_quick_edit_bulk');
add_action('submitpost_box', 'kontera_block_content_link');
add_action('submitpage_box', 'kontera_block_content_link');
add_action('edit_post', 'kontera_write_post');
add_action('admin_menu', 'kontera_menu');
add_action('init', 'ilc_farbtastic_script');
function ilc_farbtastic_script() {
  wp_enqueue_style( 'farbtastic' );
  wp_enqueue_script( 'farbtastic' );
}

?>
