<?php 
/**
 * Add post to block list
 */
function block_post($post_id){
	$disabled_pages = get_option('kontera_disabled_pages');
	$disabled_pages_array = explode(',', $disabled_pages);
    if(!empty($disabled_pages) && in_array($post_id, $disabled_pages_array)) {
		return;
	}
	if($post_id > 0) {
		$disabled_pages = empty($disabled_pages) ? $post_id : $disabled_pages.",".$post_id;
		update_option('kontera_disabled_pages', $disabled_pages);
	}
}

/**
 * Remove post from block list
 */
function unblock_post($post_id){
	$disabled_pages = get_option('kontera_disabled_pages');
    if(!empty($disabled_pages)) {
    	$disabled_pages_array = explode(',', $disabled_pages);
		foreach($disabled_pages_array as $key => $page_id) {
        	if(intval($page_id) == $post_id) {
            	unset($disabled_pages_array[$key]);
			}
		}
		$disabled_pages = implode(",", $disabled_pages_array);
		update_option('kontera_disabled_pages', $disabled_pages);
	}
}

/**
 * Add post to the "immediately show" list
 */
function add_post_to_immediately_list($post_id){
	$immediately_pages = get_option('kontera_immediately_list');
	$immediately_pages_array = explode(',', $immediately_pages);
    if(!empty($immediately_pages) && in_array($post_id, $immediately_pages_array)) {
		return;
	}
	if($post_id > 0) {
		$immediately_pages = empty($immediately_pages) ? $post_id : $immediately_pages.",".$post_id;
		update_option('kontera_immediately_list', $immediately_pages);
	}
}

/**
 * Remove post from the "immediately show" list
 */
function remove_post_from_immediately_list($post_id){
	$immediately_pages = get_option('kontera_immediately_list');
    if(!empty($immediately_pages)) {
    	$immediately_pages_array = explode(',', $immediately_pages);
		foreach($immediately_pages_array as $key => $page_id) {
        	if(intval($page_id) == $post_id) {
            	unset($immediately_pages_array[$key]);
			}
		}
		$immediately_pages = implode(",", $immediately_pages_array);
		update_option('kontera_immediately_list', $immediately_pages);
	}
}

/**
 * Check if the $id is a vaild post id
 */
function isValidPostID($id) {
	$post_status = get_post_status($id);
	$post_type = get_post_type($id);
	if($post_status == 'publish' && $post_type == 'post'){
		return true;
	} else {
		return false;
	}
}

/**
 * Check if the post publish date is prior to the "show post after x days" date
 */
function kontera_is_young_post() {
	global $post;
	$disable_new_days = get_option('kontera_disable_new_days');
	$new_post_date = time() - $disable_new_days * 86400;
	return mysql2date('U', $post->post_date) > $new_post_date;
}

/**
 * Check if the post is not blocked
 */
function kontera_is_page_enabled($id) {
	// get disabled pages from user's settings
	$disabled_pages = explode(',', get_option('kontera_disabled_pages'));

	// loop through item's pages, if we are on a disabled pages return false
	global $post;
	if (isset ($post->ID)) {
 		$current_page = $post->ID;
 	}
 	if (isset ($current_page) == false) {
 		$current_page = $id;
 	}
	foreach ($disabled_pages as $page) {
		if ($current_page == intval($page)) {
			return false;
		}
	}
	return TRUE;
}

/**
 * Check if the post is in the "immediately show" list
 */
function kontera_is_page_show_immediately($id) {
	// get immediately show pages from user's settings
	$immediately_pages = explode(',', get_option('kontera_immediately_list'));

	// loop through item's pages, if we are on a immediately pages return true
	global $post;
	$current_page = $post->ID;
 	if (isset ($current_page) == false) {
 		$current_page = $id;
 	}
	foreach ($immediately_pages as $page) {
		if ($current_page == intval($page)) {
			return true;
		}
	}
	return false;
}

add_action('admin_head', 'kontera_quickedit_get_id');
add_action('wp_ajax_kontera_get_post_status', 'kontera_get_post_status' );

/**
 * this function is updating the quick edit checkbox according to the post settings (ajax)
 */
function kontera_quickedit_get_id() {?>
   <script type="text/javascript">
   jQuery(document).ready(function() {
        jQuery("a.editinline").live("click", function() {
        
		var qe_id = inlineEditPost.getId(this);
		var data = {
				action: "kontera_get_post_status",
				post_id: qe_id, 
				qe_mode: "ajaxget"
			};
		jQuery.post(
				'<?php bloginfo('url');?>/wp-admin/admin-ajax.php', data ,
				function(response){ 
					if(response == 1){ 
						jQuery('#show_contentLinks').attr('checked', true); 
					} else {
						jQuery('#show_contentLinks').attr('checked', false);
					}
				}
			);
		});
	}); 
	</script>
	<?php
}

/**
 * this function checks if the post is ad enabled (the ajax call)
 */
function kontera_get_post_status(){
	if(isset($_POST['qe_mode']) && isset($_POST['post_id'])){
		if($_POST['qe_mode'] == 'ajaxget'){
			$post_id = $_POST['post_id'];
			if(kontera_is_page_enabled($post_id)){
				$response = 1;
			} else {
				$response = 0;
			}
			echo $response;
		}
	}
	die();
}

add_action('admin_head', 'kontera_remove_from_block_list');
add_action('wp_ajax_kontera_remove_from_block_list_call', 'kontera_remove_from_block_list_call' );

/**
 * this function remove posts from the block list (ajax)
 */
function kontera_remove_from_block_list() {?>
   <script type="text/javascript">
   jQuery(document).ready(function() {
        jQuery("a.blocked_post").live("click", function() {
        
		var qe_id = jQuery(this).attr('id');
		var data = {
				action: "kontera_remove_from_block_list_call",
				post_id: qe_id
			};
		jQuery.post(
				'<?php bloginfo('url');?>/wp-admin/admin-ajax.php', data ,
				function(response){ 
					if(response != 0){ 
						jQuery('.'+qe_id).fadeOut("slow");
						if(response == 2){
							jQuery('.kontera_block_pages').html("<b>No blocked posts.</b>");
							jQuery('b').css("display","none").fadeIn("slow");
						} 
					}
				}
			);
		});
	}); 
	</script>
	<?php
}

/**
 * this function remove posts from the block list  (the ajax call)
 */
function kontera_remove_from_block_list_call(){
	if(isset($_POST['post_id'])){
		$post_id = $_POST['post_id'];
		if(!kontera_is_page_enabled($post_id)){
			unblock_post($post_id);
			$disabled_pages = get_option('kontera_disabled_pages');
   			if(empty($disabled_pages)){
   				echo 2;
   			} else {
				echo 1;
   			}
   		} else {
			echo 0;
		}
	}
	die();
}
?>