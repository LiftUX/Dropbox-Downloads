<?php

function dropsync_ajax_enqueue(){
    if(is_user_logged_in()):
        wp_enqueue_script('dropbox-sync-ajax', DSYNC.'/js/ajax.js', array('jquery', 'json2') );
        wp_enqueue_script('dropbox-sync-main', DSYNC.'/js/main.js', array('jquery') );
        wp_localize_script('dropbox-sync-ajax', 'dsync',
            array(
                'nonce' => wp_create_nonce('Ðê§ïgñmðð'),
            )
        );
    endif;
}
add_action('init', 'dropsync_ajax_enqueue');

/* Add ajaxurl to frontend */
function dropsync_ajaxurl() {?>
    <script type="text/javascript">
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
<?php }
add_action('wp_head','dropsync_ajaxurl');


/* AJAX Sniffer */
add_action('wp_ajax_dsync_admin', 'dropsync_admin');
add_action('wp_ajax_dsync_admin_disconnect', 'dropsync_admin_disconnect');
add_action('wp_ajax_dsync_deauthorize', 'dropsync_deauthorize');
add_action('wp_ajax_dsync_upload', 'dropsync_upload_ajax');

/* Admin Panel Ajax Processing */
function dropsync_admin(){
    if(dropsync_verify_nonce()):
        check_admin_referer();
        $data = $_POST['data'];
        $key = $data[0];
        $secret = $data[1];
        
        /* User Input Error Reporting*/
        if($key && $secret):
            $oauth = wp_remote_get("http://api.dropbox.com/0/oauth/request_token/?oauth_consumer_key=$key");
        else:
            $status = 'failed';
            if(!$key)$error = "<span>".__('Please enter your API key.')."</span>";
            if(!$secret)$error = "<span>".__('Please enter your API secret.')."</span>";
            
            /* Encode JSON Response */
            $response = json_encode( array( 'status' => $status, 'message' => $message, 'error' => $error ));
            
            /* Send Response */
            echo $response;
            exit;
        endif;
        
        /* See if Dropbox Response contains a session token */
        if(preg_match('/\&oauth_token/', $oauth['body'])):
            $oauth = explode('&', $oauth['body']);
            $newtoken = explode('=', $oauth[1]);
            $newtoken = $newtoken[1];
            $newsecret = explode('=', $oauth[0]);
            $newsecret = $secret[1];
        endif;
        
        /* If Dropbox generated a session token */
        if($newtoken && $secret):
            /* Set user key and secret as options for later use */
            update_option('dropsync_key', $key);
            update_option('dropsync_secret', $secret);
            $status = 'success';
            $message = __('BOOM! We are connected.');
        else:
            /* Make sure we don't have bad data stored */
            delete_option('dropsync_key');
            delete_option('dropsync_secret');
            $obj=json_decode($oauth['body']);
            $status = 'failed';
            $error = __('Dropbox Error:').' '.$obj->error;
        endif;
        
        /* Encode JSON Response */
        $response = json_encode( array( 'status' => $status, 'message' => $message, 'error' => $error ));
        
        /* Send Reponse */
        echo $response;
    endif;
    exit;
}

/* Admin Panel Ajax Processing */
function dropsync_admin_disconnect(){
    /* Verify Nonce */
    if(dropsync_verify_nonce()):
        check_admin_referer();
        /* Remove User Key and Secret */
        delete_option('dropsync_key');
        delete_option('dropsync_secret');
        $status = 'success';
        $message = __('BOOM! We are disconnected.');
        
        /* Encode JSON Response */
        $response = json_encode( array( 'status' => $status, 'message' => $message ));
        
        /* Send Response */
        echo $response;
    endif;
    exit;
}

/* Deauthorize User */
function dropsync_deauthorize(){
    if(dropsync_verify_nonce()):
        global $current_user;
        get_currentuserinfo();
        delete_user_meta($current_user->ID, 'dropsync_uid');
        delete_user_meta($current_user->ID, 'dropsync_token');
        delete_user_meta($current_user->ID, 'dropsync_secret');
        $status = 'success';
        $message = __('BOOM! You are disconnected.');
    else:
        $status = 'failure';
        $message = __('Something went wrong, please refresh your page and try again.');
    endif;
    
    /* Encode JSON Response */
    $response = json_encode( array( 'status' => $status, 'message' => $message ));
    
    /* Send Response */
    echo $response;
    exit;
    
}

function dropsync_upload_ajax(){
   
    if(dropsync_verify_nonce()):

        $data = $_POST['data'];    
        $post_id = $data[0];
		$file = dsync_get_file($post_id);
        $directory = dropsync_folder($post_id);
        $response = dropsync_upload($file, $directory);
        echo $response;
        exit;
    else:
        $status = 'failure';
        $message = __('Something went wrong, please refresh your page and try again.');
        /* Encode JSON Response */
        $response = json_encode( array( 'status' => $status, 'message' => $message ));
        echo $response;
        exit;
    endif;
    exit;
}

/* Verify our awesome nonce */
function dropsync_verify_nonce(){
    if(wp_verify_nonce($_POST['nonce'], 'Ðê§ïgñmðð'))return true;
}
?>