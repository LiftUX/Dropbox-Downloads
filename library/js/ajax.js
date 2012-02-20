function DSyncAjax(action, data, container, callback){
    var args = {
        action: action,
        data: data,
        container: container,
        nonce: dsync.nonce
    };

    jQuery.post(ajaxurl, args,        
        function(response) {
            var obj = JSON.parse(response);
            obj.container = container;
            if(typeof callback == 'function'){
                callback(obj, container);
            }else{
                jQuery(container).html(obj.message);
            }
        }
    );
}
