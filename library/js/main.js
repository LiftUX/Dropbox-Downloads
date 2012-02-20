jQuery(function($){

    $('#dsync_admin').click(function(e){
    	e.preventDefault();
        $('.loader').show();
        var action = $(this).attr('id');
        var container = '#message';
        var data = new Array();
        var callback = adminSetup;
        data[0] = $('#dropsync_key').val();
        data[1] = $('#dropsync_secret').val();
        DSyncAjax(action, data, container, callback);
    });
    
    $('#dsync_admin_disconnect').click(function(e){
    	e.preventDefault();
        $('.loader').show();
        var action = $(this).attr('id');
        var container = '#message';
        var data = '';
        var callback = adminDisconnect;
        DSyncAjax(action, data, container, callback);
    });
    
    $('#dsync_deauthorize').click(function(e){
    	e.preventDefault();
        $('.loader').show();
        var action = $(this).attr('id');
        var container = '.response';
        var data = '';
        var callback = userDeauthorize;
        DSyncAjax(action, data, container, callback);
    });
    
    $('#dsync_authorize').click(function(e){
    	e.preventDefault();
        $('.loader').show();
        var action = $(this).attr('id');
        var container = '.response';
        var data = '';
        var callback = userDeauthorize;
        DSyncAjax(action, data, container, callback);
    });
    
    /* Toggle Admin Hide/Show Message */
    if( $('a#toggle_keys').length ){
        $('a#toggle_keys').click(function(e){
            e.preventDefault();
            $("#dsync_form").slideToggle();
            new_title = $(this).text();				
            old_title = $(this).attr('title');
            $(this).attr('title',new_title);
            $(this).text(old_title);
        });
    }

    /* Admin Options Add Key/Secret Callback */
    var adminSetup = function(obj){
        if(obj.status == 'success'){
            $('.loader').hide();
            $('#dsync_form').fadeOut();
            $('#dropbox_sync_connected').fadeIn();
            $(obj.container).removeClass('error').addClass('success').html(obj.message).wrapInner("<p></p>").fadeIn().delay(3000).fadeOut();
        }
        if(obj.error){
            $('.loader').hide();
            $(obj.container).removeClass('success').addClass('error').html(obj.error).wrapInner("<p></p>").fadeIn();
        }
    }
    /* Admin Options Disconnect Key/Secret Callback */
    var adminDisconnect = function(obj){
        if(obj.status == 'success'){
            $('.loader').hide();
            $('#dropbox_sync_connected').hide();
            $('#dsync_form').fadeIn();
            $(obj.container).removeClass('error').addClass('success').html(obj.message).wrapInner("<p></p>").fadeIn().delay(3000).fadeOut();
        }
    }
    
    /* Deauthorize User from Dropbox */
    var userDeauthorize = function(obj){
        if(obj.status == 'success'){
            $('.loader').hide();
            $('.dsync_authorized').fadeOut();
            $('.dsync_deauthorized').fadeIn();
            $(obj.container).removeClass('error').addClass('success').html(obj.message).wrapInner("<p></p>").fadeIn().delay(3000).fadeOut();
        }
    }

    $('.dsync_download').each(function(){
    
    	$this = $(this);
        	
    	$(this).find('button').click(function(){
	        $(this).attr('class','loading');
	        $(this).html("<span class='icon'></span> please wait...");
	        var action = 'dsync_upload';
	        var container = '.'+$this.attr('class')+' button';
	        var data = new Array();
	        var callback = userUpload;
	        data[0] = $this.find('.post_id').val();
	        DSyncAjax(action, data, container, callback);
	        return false;
	    });
    
    });

    var userUpload = function(obj){
        if(obj.status == 'success'){
            $(obj.container).attr('disabled','disabled').attr('class','success').html("<span class='icon'></span>"+obj.message);
        }
        if(obj.error){
            $(obj.container).attr('class','error').html("<span class='icon'></span>"+obj.error);
        }
    }

});