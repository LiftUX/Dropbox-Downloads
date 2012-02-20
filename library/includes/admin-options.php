<?php

/* DM Pro Options Menu */
function dropsync_menu() {
    //add_options_page(DSYNC_NAME, DSYNC_NAME, 'manage_options', 'dropsync', 'dropsync_settings');
    add_submenu_page('options-general.php', DSYNC_NAME, DSYNC_NAME, 10, 'dropsync', 'dropsync_settings');
}
add_action('admin_menu', 'dropsync_menu');

function dropsync_settings() {
    $key = get_option('dropsync_key');
    $secret = get_option('dropsync_secret');
    $form_class = ($key) ? ' class="hide"' : '';
    $sync_class = (!$key) ? ' class="hide"' : '';
    $current_state_text = ($key) ? __("Show API Key","dropsync") : __("Hide API Key","dropsync");
    $opposite_state_text = ($key) ? __("Hide API Key","dropsync") : __("Show API Key","dropsync");
    ?>

    <div class="wrap" id="dropsync">
    
    	<div id="icon-dropbox-sync" class="icon32"></div>
        <h2><?php echo DSYNC_NAME;?></h2>
        <div id="message" class="response"></div>
        <img class="loader" src="<?php echo DSYNC.'/images/ajax-loader.gif';?>" alt="Ajax Loader" />
        
        <div id="dropbox_sync_connected" <?php echo $sync_class;?>>
        	<h3><?php _e("Dropbox API App Key","dropsync"); ?></h3>
        	<div class="description"><?php _e("Your WordPress site is connected with your Dropbox Application.","dropsync"); ?></div>

            <img <?php echo $image_class;?> src="<?php echo DSYNC;?>/images/dropbox.logo.png" alt="<?php _e('Dropbox Logo',"dropsync");?>" />
            <ul>
            	<li><a id="dsync_admin_disconnect" href="#"><?php _e('Disconnect from Dropbox API',"dropsync");?></a></li>
            	<?php if($key): ?><li><a id="toggle_keys" href="#showkeys" title="<?php echo $opposite_state_text; ?>"><?php echo $current_state_text; ?></a></li><?php endif; ?>
            </ul>
        </div>
        <div id="dsync_form" <?php echo $form_class;?>>

        	<div class="description"><?php _e("Enter your Dropbox API key below. If you don't have an API key, grab one ","dropsync");?><a target="_blank" href="https://www.dropbox.com/developers/apps"><?php _e("here","dropsync");?></a></div>

            <form method="post" action="options.php">
                <?php settings_fields('dropsync'); ?>
        
                <label for="dropsync_token"><?php _e('API Key',"dropsync");?></label>
                <input type="text" id="dropsync_key" name="dropsync_key" value="<?php echo get_option('dropsync_key'); ?>" />
		
		<label for="dropsync_secret"><?php _e('API Secret',"dropsync");?></label>
                <input type="text" id="dropsync_secret" name="dropsync_secret" value="<?php echo get_option('dropsync_secret'); ?>" />
        
                <input id="dsync_admin" name="dsync_admin" type="submit" class="button-primary" value="<?php _e('Save Changes',"dropsync") ?>" />
            </form>
        </div>
        <hr/>
        
        <div id="dsync-shortcode">
        	<h3><?php _e("Dropbox Sync Shortcode","dropsync"); ?></h3>
        	<div class="description"><?php _e("In order to use Dropbox Sync within posts or pages, you can embed the following shortcode with the attributes listed below. The accepted arguments for each attribute are listed as well.","dropsync"); ?></div>
        </div>
        
    </div>
<?php } ?>