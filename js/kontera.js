// JavaScript Document

//CHECKBOX CONTROLS 
    function checkboxenable() {
        if(document.konteta) {
			if(document.konteta.show_contentLinks_control.checked) {
				document.konteta.kontera_post_settings.disabled=false;
				document.konteta.kontera_disable_new_days.disabled=false;
			} else {
				document.konteta.kontera_post_settings.disabled=true;
				document.konteta.kontera_disable_new_days.disabled=true;
			}
        } else if (document.post){
			if(document.post.display_content_link.checked) {
				document.post.kontera_post_settings.disabled=false;
			} else {
				document.post.kontera_post_settings.disabled=true;
			}
		}
    }
    window.onload=checkboxenable;


//MOBILE TRACKING CHECKBOX CONTROLS
	function checkboxmobile() {
	//var ele = document.konteta.kontera_mobile_id;
	var ele = document.getElementById("mobilesect");
	
        if(document.konteta) {
			if(document.konteta.kontera_show_mobile_opt.checked) {
				//document.konteta.kontera_post_settings.disabled=false;
				ele.style.display = "block";
			
			} else {
				//document.konteta.kontera_post_settings.disabled=true;
				ele.style.display = "none";
			
			}
        }
    }
    //window.onload=checkboxmobile;
	

	
	
			function isValidPublisherId() {
			
			// PHP VALIDATOR REFERENCE:
			//$isValidPublisherId = (preg_match('/^\d{3,}$/', $_POST['kontera_publisher_id']));				
			//JS: (str.match(/[^a-zA-Z0-9]/) {
			//PHP: preg_match('/[^a-zA-Z0-9]/', $str);
			var str = document.konteta.kontera_publisher_id.value;
			if (str.match(/[^\d{3,}$]/) || str == "" || str ==null ){

			//alert("Bad Pub ID: "+str);
			document.konteta.kontera_publisher_id.focus();

			return false;
					
			}
			else {
			
			//alert("Good Pub ID: "+str);
			}
			}

	
			function isValidMobileId() {

			var str = document.konteta.kontera_mobile_id.value;

			var elem = document.getElementById("mobalert");

			if (str.match(/[^\d{3,}$]/) || (document.konteta.kontera_show_mobile_opt.checked && ((str == "" || str ==null )))) {

				//elem.style.visibility = "hidden";
				elem.style.display = "none";
				document.konteta.kontera_mobile_id.focus();
				//alert("Not a good mobile ID on account of special characters or length: "+str+elem);
				return false;
			}
			
			else if (str == document.konteta.kontera_publisher_id.value ) {
				

				//elem.style.visibility = "visible";
				elem.style.display = "block";
				document.konteta.kontera_mobile_id.focus();
				//alert("Pub ID same as Mobile ID"+elem);
				return false;
				
				}
			
			else {
				
				//elem.style.visibility = "hidden";
				elem.style.display = "none";
				
				//alert("This is a good Mobile ID: "+str+elem);
				return true;
				
				}

			}
			
			
	

	
	
	
	
	
	
	
	
	
	
	