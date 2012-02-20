<?php
function dsync_setup_nav() {
    global $bp;

    bp_core_new_subnav_item(array(
        'name' => __( 'Dropbox' ), 
        'slug' => 'dropbox',
        'parent_url' => $bp->loggedin_user->domain . $bp->settings->slug . '/', 
        'parent_slug' => $bp->settings->slug, 
        'screen_function' => 'dsync_settings_screen', 
        'position' => 20, 
        'item_css_id' => 'dsync'));

    function dsync_settings_screen() {

        add_action( 'bp_template_title', 'dsync_screen_title' );
        add_action( 'bp_template_content', 'dsync_screen_content' );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }
    
    function dsync_screen_title() {
        _e("Dropbox Settings","dropboxer");
    }
    
    function dsync_screen_content() {
         //if(current_user_is_member() && current_user_on_level(1)):
            /* Dropbox Authorization */
            echo dsync_authorize_check();
        //else:
        //    $image = get_bloginfo('stylesheet_directory').'/images/pro/pro_members_only.png';
        //    echo "<a href='/pro'><img src='$image' alt='Designmoo Pro' /></a>";  
        //endif;
    }
}

add_action( 'bp_setup_nav', 'dsync_setup_nav' );

function dsync_settings_link(){
    global $bp;
    $callback = $bp->loggedin_user->domain.'settings/dropbox/';
    return $callback;
}



?>