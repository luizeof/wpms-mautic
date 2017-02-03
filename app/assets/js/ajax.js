// see http://solislab.com/blog/5-tips-for-using-ajax-in-wordpress/
// and https://pippinsplugins.com/using-ajax-your-plugin-wordpress-admin/

jQuery(document).ready(function() {
    console.log('MauticSyncAjax', MauticSyncAjax);  

    var $ = jQuery, 
        url = $.trim($('#mautic-url').val()),
        public_key = $.trim($('#mautic-public-key').val()),
        secret_key = $.trim($('#mautic-secret-key').val()),
        auth_info = $.trim($('#mautic-auth-info').val()),
        ajax_url = MauticSyncAjax.ajaxurl,
        submit_nonce = MauticSyncAjax.submitNonce,
        auth_nonce = MauticSyncAjax.authNonce;

    console.log('secret_key: ', secret_key);
    console.log('ajax_url: ', ajax_url);

    if (auth_info == "") { 
        auth_info = false; 
        console.log('auth_info = ' + auth_info);
    }
 
    // if auth_info is recorded, this is true
    $('#mautic-auth').prop('disabled', !auth_info);
    $('#mautic-userstatus').text('Ready...');

    // handle the submit button being pushed
    $('#mautic-sync-form').submit(function() {
        // disable the submit button until it returns.
        $('#mautic-submit').attr('disabled', true);
	//alert('test submit');
        data = {
            'action': 'mautic_submit',
            'nonce-submit' : submit_nonce,
            'nonce-auth' : auth_nonce,
            'url' : url,
            'public_key' : public_key,
            'secret_key' : secret_key,
            'auth_info' : auth_info
        };
        console.log("data = ", data);
	$('#mautic-userstatus').html('Processing...');
        /*$.post(ajaxurl, data, function(response) {
            alert(response);
        });  */
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: MauticSyncAjax.ajaxurl,
            data: {
                'action': 'mautic_submit',
                'do' : 'something',
                'nonce-submit' : submit_nonce,
                'nonce-auth' : auth_nonce,
                'url' : url,
                'public_key' : public_key,
                'secret_key' : secret_key,
                'auth_info' : auth_info
            },
            success: function(data) {
                // re-enable the submit button
                console.log('Set auth_info = true');
                $('#mautic-submit').attr('disabled', false);
                console.log('Success: data: ', data)
            },
            failure: function() {
                console.log('Failure: data: ', data)
                $('#mautic-userstatus').text('error!');
            }           
        });

        return false;
    });


/*    //console.log('In jQuery .ready');

    var url = $.trim($('#mautic_url').val()),
        public_key = $.trim($('#mautic_public_key').val()),
        secret_key = $.trim($('#mautic_secret_key').val()),
        auth_info = $.trim($('#mautic_auth_info').val()),
        ajax_url = MauticSyncAjax.ajaxurl,
        submit_nonce = MauticSyncAjax.submitNonce,
        auth_nonce = MauticSyncAjax.authNonce;
    //console.log('auth_info:' + auth_info);
    if (auth_info == "") { 
	auth_info = false; 
	console.log('auth_info = ' + auth_info);
    }
 
    // if auth_info is recorded, this is true
    $('#mautic-auth').prop('disabled', !auth_info);

    console.log('MauticSyncAjax', MauticSyncAjax);  

    $('#mautic-sync-form').validate({
        submitHandler: function(form) {
            $(form).ajaxSubmit();
        },
        rules: {
            mautic_url: {
                required: true,
                url: true
            },
            mautic_public_key: {
                required: true,
                rangelength: [50, 50]
            },
            mautic_secret_key: {
                required: true,
                rangelength: [50, 50]
            }
        }
    });
    
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: MauticSyncAjax.ajaxurl,
        data: {
            'action' : 'MauticAction',
            'do' : 'something',
            'nonce-submit' : submit_nonce,
            'nonce-auth' : auth_nonce,
            'url' : url,
            'public_key' : public_key,
            'secret_key' : secret_key,
            'auth_info' : auth_info
        },
        success: function(data) {
            console.log('MauticSyncData', data);
        }
    });*/
});

