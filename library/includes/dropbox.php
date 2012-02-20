<?php

/************************************/
//                                  //
//      Dropbox Authentication      //
//                                  //
/************************************/

/**
* Step 1 Authentication: Get Session Token
*
* @return Mixed - array (token, secret) or Boolean (false)
*/
function dropsync_request_token(){
    $app = dropsync_consumer_signature();
    $key = $app['key'];
    $oauth = wp_remote_get("http://api.dropbox.com/0/oauth/request_token/?oauth_consumer_key=$key");
    /* See if Dropbox Response contains a session token */
    if(preg_match('/\&oauth_token/', $oauth['body'])):
        $oauth = explode('&', $oauth['body']);
        $token = explode('=', $oauth[1]);
        $token = $token[1];
    endif;
    return $token ? $token : false;
}

/**
* Step 2 Authentication: Authorize URL
*
* @return string - $url
*/
function dropsync_authorize_url($token, $callback){
    /* Rework This with Dropbox Class */
    
    $url = "https://www.dropbox.com/0/oauth/authorize?oauth_token=$token&oauth_callback=$callback";
    return $url;
}

/**
* Step 2 Authentication: Authorize URL
*
* @return string - link (html)
*/
function dsync_authorize_link($callback = ''){
    /* Create Authorize Link */
    $callback = $callback ? $callback : dsync_settings_link();
    $token = dropsync_request_token();
    if($token):
        $url = dropsync_authorize_url($token, $callback);
    endif;
    return '<a class="dsync_authorize" href="'.$url.'">'.__("Authorize Dropbox", "dropsync").'</a>';
}


/**
* Step 3: Get/Store User/Secret Pair
*
* @return Boolean
*/
function dropsync_access_token($signature){
    
    $app = dropsync_consumer_signature();
    $key = $app['key'];
    $token = $signature['token'];
    $uid = $signature['uid'];
    $oauth = wp_remote_get("https://api.dropbox.com/0/oauth/access_token?oauth_token=$token&oauth_consumer_key=$key");
    $oauth = explode('&', $oauth['body']);
    $token = explode('=', $oauth[1]);
    $token = $token[1];
    $secret = explode('=', $oauth[0]);
    $secret = $secret[1];
    if($token && $secret):
        global $current_user;
        get_currentuserinfo();
        add_user_meta($current_user->ID, 'dropsync_uid', $uid, true);
        add_user_meta($current_user->ID, 'dropsync_token', $token, true);
        add_user_meta($current_user->ID, 'dropsync_secret', $secret, true);
        return true;
    else:
        return false;
    endif;
}

/**
* Step 3: Automatically redirect Dropbox callback to get/save user pair
*
* @return Mixed - Boolean || Redirect
*/
function dropsync_save_user_access(){
	global $wp_query, $post;
    $query_token = $_GET['oauth_token'];
    $query_uid = $_GET['uid'];
    $page = dsync_settings_link();
    $signature = array(
        'token' => $query_token,
        'uid' => $query_uid
    );
    
    /* Get/Store User Access Tokens */
    dropsync_access_token($signature);
		
	
    /* Redirect back to page for clean URL */
    wp_redirect($page);
    
}
if($_GET['oauth_token'] && $_GET['uid'])add_action('init', 'dropsync_save_user_access');


/**
* Step 4: Display Synced With Dropbox
*
* @echo HTML
*/
function dsync_authorized($hide = false){
    global $post;
    if($hide)$class = ' dsync_hide';?>
    
    <div class="dsync_authorized<?php echo $class;?>">
        <img class="dsync_connected" src="<?php echo DSYNC;?>/images/dropbox.logo.png" alt="<?php _e('Synced with Dropbox');?>" />
        <a id="dsync_deauthorize" class="dsync_deauthorize" href="#"><?php _e('De-Authorize Dropbox', 'dropsync');?></a>
    </div>
    <div class="dsync_deauthorized">
        <?php echo dsync_authorize_link(get_permalink($post->ID));?>
    </div>
<?php }

function dsync_authorize_check() { 
    global $post;
    $query_token = $_GET['oauth_token'];
    $query_uid = $_GET['uid'];
    global $current_user;
    get_currentuserinfo();
    $user_id = get_user_meta($current_user->ID, 'dropsync_uid');
    $user_token = get_user_meta($current_user->ID, 'dropsync_token');
    $user_secret = get_user_meta($current_user->ID, 'dropsync_secret');?>
    <div class="dsync">
        <div class="response"></div>
        <img class="loader" src="<?php echo DSYNC.'/images/ajax-loader2.gif';?>" alt="Ajax Loader" />
    </div>
    

    <?php /* Check for 3rd Step Auth */
    if($user_token && $user_secret && $user_id):
        dsync_authorized();
    /* First Step Auth */
    else:
        echo dsync_authorize_link(get_permalink($post->ID));
    endif;
    
}


/**
* Step 5: Instantiate Dropbox Class
*
* @return Object User/Dropbox Object
*/
function dropsync_authenticate(){
    
    /* Get App/User Data */
    $app = dropsync_consumer_signature();
    $user = dropsync_user_signature();
    $key = $app['key'];
    $secret = $app['secret'];
    $user_token = $user['token'];
    $user_secret = $user['secret'];
    
    /* Instantiate Dropbox Object */
    $dropbox = new DropLib($key, $secret, $user_token, $user_secret);
    $dropbox->setNoSSLCheck(true);
    $dropbox->setUseExceptions(false);
    return $dropbox;
    
}

/************************************/
//                                  //
//          Stored Data             //
//                                  //
/************************************/

/**
* Retrieve App Key?Secret Pair
*
* @return Mixed - Boolean || array(key, secret)
*/
function dropsync_consumer_signature(){
    
    $key = get_option('dropsync_key') ? get_option('dropsync_key') : false;
    $secret = get_option('dropsync_secret') ? get_option('dropsync_secret') : false;
    if($key && $secret) $signature = compact('key', 'secret');
    if($signature) return $signature;
}


/**
* Retrieve User Token/Secret Pair
*
* @return Mixed - Boolean || array(id, token, secret)
*/
function dropsync_user_signature(){
    
    /* Grab Wordpress User Info */
    global $current_user;
    get_currentuserinfo();
    
    /* Dropbox User ID */
    $id = get_user_meta($current_user->ID, 'dropsync_uid', true);
    $id = $id ? $id : false;
    
    /* Dropbox User Token */
    $token = get_user_meta($current_user->ID, 'dropsync_token', true);
    $token = $token ? $token : false;
    
    /* Dropbox User Secret */
    $secret = get_user_meta($current_user->ID, 'dropsync_secret', true);
    $secret = $secret? $secret : false;
    
    if($id && $secret && $token)$signature = compact('id', 'token', 'secret');
        
    if($signature)return $signature;
}

function dropsync_user_connected(){
	global $current_user;
	get_currentuserinfo();
	
	/* Dropbox User ID */
    $id = get_user_meta($current_user->ID, 'dropsync_uid', true);
    $id = $id ? $id : false;
    
    /* Dropbox User Token */
    $token = get_user_meta($current_user->ID, 'dropsync_token', true);
    $token = $token ? $token : false;
    
    /* Dropbox User Secret */
    $secret = get_user_meta($current_user->ID, 'dropsync_secret', true);
    $secret = $secret? $secret : false;
	
	if($id && $token && $secret)
		return $current_user->ID;
}

function dropsync_user_permitted(){
	if(function_exists('current_user_is_member') && function_exists('current_user_on_level')):
		if(current_user_is_member() && current_user_on_level(1)):
			return true;
		endif;
	endif;
}

function dropsync_purchased_product(){
	global $post, $current_user, $wpdb;
	get_currentuserinfo();
	$id = $current_user->ID;
	$purchased = false;
	$orders = $wpdb->get_results("SELECT post_content FROM $wpdb->posts WHERE post_type = 'mp_order' AND post_author = '$id'");
	foreach ($orders as $order):
		foreach($order as $products):
			$products = unserialize($products);	
			foreach ($products as $id => $info):
				if($id = $post->ID) $purchased = true;
			endforeach;
		endforeach;
	endforeach;
	
	if($purchased) return true;
}


/************************************/
//                                  //
//         File Operations          //
//                                  //
/************************************/

/**
* Convert File URL to Server Path
*
* @return String file path
*/
function dropsync_file_path($file){
    	
	/* Convert WP media URL to PATH */
	if(preg_match('/http/', $file)):
		$chunk = parse_url($file);
		$path = $chunk['path'];
		$path = explode('wp-content', $path);
		$path = ABSPATH.'wp-content'.$path[1];
	endif;
	
	/* Check if path is set */
	$path = $path ? $path : $file;
	
	/* Apply filters to allow developers to play */
	$path = apply_filters('dropsync_file_path', $path);
	return $path;

}

/**
* Determine Folder Structure
*
* @return String file path
*/
function dropsync_folder($post_id = null){
    global $post;
    $post_id = $post_id ? $post_id : $post->ID;
    $post_type = get_post_type($post_id);
    
    $root_folder = DSYNC_ROOT_FOLDER;
    
    switch($post_type){
        case('post'):
            $base_folder = urlencode(DSYNC_PRO_FOLDER);
        break;
        
        case('resource'):
            $base_folder = urlencode(DSYNC_FREE_FOLDER);
        break;
        
        case('product'):
            $base_folder = urlencode(DSYNC_PRODUCT_FOLDER);
        break;
        
        default:
            $base_folder = urlencode(DSYNC_FREE_FOLDER);
        break;
    }
    
    $categories = get_the_category($post_id);
    
    if(is_array($categories)):
        $folder = '';
        foreach ($categories as $category):
            if($category->name != 'Featured'):
                $folder = $category->name;
                break;
            endif;
        endforeach;
    endif;
    $path = $folder ? $root_folder.'/'.$base_folder.'/'.$folder.'/' : $root_folder.'/'.$base_folder.'/';
    $path = str_replace(' ', '-', $path);
    
    return $path;
}

/**
* Upload File
*
* @return String file path
*/
function dropsync_upload($file = null, $directory = null,  $custom_field = null){
    global $current_user, $post;
    get_currentuserinfo();
    
    $user_id = $current_user->ID;
    
    $directory = $directory ? $directory : dropsync_folder($post_id);
    
    /* Set File Path Location */
    $custom_field = $custom_field ? $custom_field : 'download-link';
    
    /* Get File Path */
    if(!$file)$file = get_post_meta($post->ID, $custom_field, true);

    $path = dropsync_file_path($file);
    
    /* Instantiate Dropbox Object */
    $dropbox = dropsync_authenticate();
	
    $upload = $dropbox->upload($path, $directory);

    if($upload['response'] == 'OK'):
        $status = 'success';
        $message = __('Synced with Dropbox', 'dropsync');
        $user_downloads = get_user_meta($user_id,'downloads',true);		// get the user meta field of their downloads
        if( !is_array($user_downloads) ) 								// check if user has already synced this file
        	$user_downloads = array();   								// if not, make sure this variable is registered as an array
        $user_downloads[] = $path;       								// add this file to the array
        $user_downloads = array_unique($user_downloads);				// make sure it's not already in the array
        update_user_meta($user_id,'downloads',$user_downloads);			// save the array to a user meta record
    else:
        $status = 'failed';
        $error = __('Something went wrong, please try again or contact the administrator', 'dropsync');
    endif;
    $response = json_encode( array( 'status' => $status, 'message' => $message, 'error' => $error ));
    return $response;
}

/************************************/
//                                  //
//         Content Helpers          //
//                                  //
/************************************/

function dropsync_download_link($post_id, $single = true, $download_url){
    
    $user_id = dropsync_user_connected();									// get user id
	$user_permitted = dropsync_user_permitted();
	$post_type = get_post_type($post_id);
	
	/* Check if product */
	if($post_type == 'product'):
		$purchased = dropsync_purchased_product();
		if(!$purchased) $user_permitted = false;
	endif;

	if($user_id && $user_permitted):
		$current_download = dropsync_file_path(dsync_get_file($post_id));	// get current file path on server
		$user_downloads = get_user_meta($user_id,'downloads',true);			// get user's download history
		$dropbox_check = dropsync_file_in_dropbox($current_download, $post_id);
		
		if( is_array($user_downloads) && $dropbox_check['success'] != ''):	// if this file exists in current download, show disabled button
	
			$output = "
				<div class=\"dsync_download\">
				<input type=\"hidden\" class=\"post_id\" value=\"{$post_id}\" />
				<button name=\"submit\" type=\"button\" class=\"success\" disabled><span class='icon'></span>" . __('Synced To Your Dropbox', 'dropsync') . "</button>
				</div>
			";
			
		else:																// else show the dropbox sync button
					echo $yes;
			$output = "
				<div class=\"dsync_download\">
				<input type=\"hidden\" class=\"post_id\" value=\"{$post_id}\" />
				<button name=\"submit\" type=\"button\"><span class='icon'></span>" . __('Sync To Your Dropbox', 'dropsync') . "</button>
				</div>
			";
			
		endif;
	
		if($single):
			if(is_single()):
				return $output;
			endif;
		else:
			return $output;
		endif;
	elseif(!$user_id && $user_permitted):
		$link = dsync_settings_link();
		echo "<p><a href='$link'>".__('Enable Dropbox downloads', 'dropsync')."</a></p>";
	endif;

}

function dsync_get_custom_field_name($post_id){
    
    $custom_field = 'file_file';
        
    return $custom_field;
    
}

function dsync_get_file($post_id){

	global $post;
	
	$post_id = $post_id ? $post_id : $post->ID;
    
    $override = get_post_meta($post_id, '_external_file_url', true);
    
	$file = get_post_meta($post_id, dsync_get_custom_field_name($post_id), true);
	if( $override ):
		if(is_array($override))$override = $override['file'];
		$file = $override;
		$overridden = true;
	endif;
	
	if ( !isset($overridden) && dropsync_is_remote_file($file) ):
		$remote_file = dropsync_remote_file_to_wp($file);
		$remote_file = $remote_file['url'];
		update_post_meta($post_id,'_external_file_url',$remote_file);
		$file = $remote_file;
	endif;
	
	return $file;

}

function dropsync_content_download_link($content){
    global $post;
    $post_id = $post->ID;
	if(is_singular())
		$link = dropsync_download_link($post_id);
    $output = $content.$link;
    return $output;
}

//add_filter('the_content', 'dropsync_content_download_link');

function dropsync_file_in_dropbox($file, $post_id){
    
     /* Check dropbox to see if file exists */
    $directory = dropsync_folder($post_id);
    $file = basename($file);
    $dropbox = dropsync_authenticate();
    $metadata = $dropbox->metadata($directory);


    if( is_array($metadata['body']['contents'])):

		foreach($metadata['body']['contents'] as $dropbox_file):
		    /* If file exists in dropbox folder */
		    if(basename($dropbox_file['path']) == $file):
			$in_dropbox = true;
		    endif;
		endforeach;
        
        if($in_dropbox):
            $success = __('We found this file in your Dropbox!', 'dropsync');
        else:
            $warning = __("We couldn't find this file in your Dropbox", 'dropsync');
        endif;
        
    else:
		$error = $metadata['body']['error'];
		$status = $metadata['body']['status'];
		$response = $metadata['body']['response'];
		if($error) error_log("Dropbox Error For Post $post_id: Error ($error), Status ($status), Response ($response)");

    endif;
    $response = array('success'=>$success, 'warning'=>$warning, 'error'=>$error);
    return $response;
}

function dropsync_http_get_file($url){

	if( $url ):
		
		$url_stuff = parse_url($url);
		$port = isset($url_stuff['port']) ? $url_stuff['port']:80;
		
		$fp = fsockopen($url_stuff['host'], $port);
		
		$query  = 'GET ' . $url_stuff['path'] . " HTTP/1.0\n";
		$query .= 'Host: ' . $url_stuff['host'];
		$query .= "\n\n";
		
		fwrite($fp, $query);
		
		while ($line = fread($fp, 1024)) {
		   $buffer .= $line;
		}
		
		preg_match('/Content-Length: ([0-9]+)/', $buffer, $parts);
		return substr($buffer, - $parts[1]);
	else:
		return $file['error'] = __("No Input File Found","dropsync");			
	endif;
}

function dropsync_remote_file_to_wp($filename){
	
	$name = basename($filename);
		
	return wp_upload_bits($name, null, dropsync_http_get_file($filename));

}

function dropsync_is_remote_file($filename){

	$this_site_url = parse_url( get_bloginfo('wpurl') );
	if($filename)
		$download_url = parse_url( $filename );
	
	if($this_site_url['host'] != $download_url['host'] ):
		return true;
	else:
		return false;
	endif;
	
}

function dropbox_button() {

	global $post;
	
	$post_id = $post->ID;
	
	if( is_single($post_id) )
		$single = true;
	else
		$single = false;
	
    return dropsync_download_link($post_id, $single);

}
add_shortcode('dropbox_button', 'dropbox_button');

function dropbox_download_button($args) {

	global $post;
	
	extract( shortcode_atts( array(
		'url' => null
	), $atts ) );
	
	$post_id = $post->ID;
	
	if( is_single($post_id) )
		$single = true;
	else
		$single = false;
	
    return dropsync_download_link($post_id, $single);

}
add_shortcode('dropbox_download_button', 'dropbox_download_button');

function download_button($args) {

	$url = get_post_meta(get_the_ID(),'download-link',true);	

    return '<a class="button" href="' . $url . '">Download File</a>';

}
add_shortcode('download_button', 'download_button');

function dual_download_button($args) {

	$url = get_post_meta(get_the_ID(),'download-link',true);	

    return download_button() . " &nbsp; " . dropbox_download_button();

}
add_shortcode('dual_download_button', 'dual_download_button');

?>