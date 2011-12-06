<script type="text/javascript">
    $().ready(function() {
    
        // Main Ajax posting function for this page.
        function post_account_action(task){
            
            //use ajax to run the check
            $.post("{SITE_URL}/ajax/account/{user.username}", { action : task },
                function(response){
                
                    // Parse the JSON response
                    var result = jQuery.parseJSON(response);
                    
                    switch(task)
                    {
                        case "unban-account":
                            if (result.success == true){
                                // UnBan was a success
                                $('#js_message').attr('class', 'alert success no-margin top').html( result.message );
                                $('#account-ban-button').html('Ban Account');
                                $('#account-ban-button').attr('name', 'ban');
                            }else{
                                // General error
                                $('#js_message').attr('class', 'alert error no-margin top').html( result.message );
                            }
                            // Update our status and display our message
                            post_account_action('account-status');
                            $('#js_message').delay(2000).slideUp(600);
                            break;
                            
                        case "lock-account":
                            if (result.success == true){
                                // Locking of account successful
                                $('#account-lock-button').html('Unlock Account');
                                $('#account-lock-button').attr('name', 'unlock');
                                $('#js_message').attr('class', 'alert success no-margin top').html( result.message );
                            }else{
                                // General error
                                $('#js_message').attr('class', 'alert error no-margin top').html( result.message );
                            }
                            
                            // Update our status and display our message
                            post_account_action('account-status');
                            $('#js_message').delay(2000).slideUp(600);
                            break;
                            
                        case "unlock-account":
                            if (result.success == true){
                                // Unlocking of account successful
                                $('#account-lock-button').html('Lock Account');
                                $('#account-lock-button').attr('name', 'lock');
                                $('#js_message').attr('class', 'alert success no-margin top').html( result.message );
                            }else{
                                // General error
                                $('#js_message').attr('class', 'alert error no-margin top').html( result.message );
                            }
                            
                            // Update our status and display our message
                            post_account_action('account-status');
                            $('#js_message').delay(2000).slideUp(600);
                            break;
                            
                        case "account-status":
                            $('#account_status').html( result.message );
                            break;
                            
                        case "delete-account":
                            if (result.success == true){
                                // Delete successful
                                alert( result.message );
                                window.location = "{SITE_URL}/admin/users";
                            }else{
                                // Error
                                alert( result.message );
                            }
                            break;
                    }
                })
        }

        // Callback function for the Ban ajaxForm 
        function ban_result(response, statusText, xhr, $form)  { 
            // Parse the JSON response
            var result = jQuery.parseJSON(response);
            if (result.success == true){
                // Show that there are updates
                $('#account-ban-button').html('UnBan Account');
                $('#account-ban-button').attr('name', 'unban');
                
                // Update our status
                post_account_action('account-status');
                $('#js_ban_message').attr('class', 'alert success').html( result.message );
            }else{
                $('#js_ban_message').attr('class', 'alert error').html( result.message );
            }
        }
        
        // Callback function for the update profile ajaxForm 
        function update_account_result(response, statusText, xhr, $form)  { 
            // Parse the JSON response
            var result = jQuery.parseJSON(response);
            if (result.success == true){
                // Account updated successfully
                $('#js_profile_message').attr('class', 'alert success').html(result.message);
                $('#password1').attr('value', '');
                $('#password2').attr('value', '');
            }else{
                // General errors
                $('#js_profile_message').attr('class', 'alert ' + result.type).html(result.message);
            }
            $('#js_profile_message').delay(4000).slideUp(600);
        }

        // Ban button
		$("#account-ban-button").click(function() {
            var current = $('#account-ban-button').attr('name');
            if (current == 'ban'){
                $('#ban-form').dialog({ modal: true, height: 460, width: 600 });
                $('#ban').attr('style', '');
                $('#js_ban_message').attr('style', 'display: none');
            }else{
                // Start our loading message
                $('#js_message').attr('class', 'alert loading no-margin top').html('Submitting action...').slideDown(400);
                post_account_action('unban-account');
            }
		});
        
        // Lock button
		$("#account-lock-button").click(function() {
            var current = $('#account-lock-button').attr('name');
            
            // Start our loading message
            $('#js_message').attr('class', 'alert loading no-margin top').html('Submitting action...').slideDown(400);
            if (current == 'lock'){
                post_account_action('lock-account');
            }else if (current == 'unlock'){
                post_account_action('unlock-account');
            }
		});
        
        // Delete button
		$("#account-delete-button").click(function() {
            if ( confirm('Are you sure you want to delete account: {user.username} (#{user.id})?') ){
                post_account_action('delete-account');
            }
		});

        // Validate Forms
        var banform = $("#ban").validate();
        var profileform = $("#profile").validate(
            {
              rules: {
                email: {
                    required: true,
                    email: true
                },
                password2: {
                  equalTo: "#password1"
                }
              }
            });
		$("#reset-profile").click(function() {
			profileform.resetForm();
		});
        
        // bind the Ban form using 'ajaxForm' 
        $('#ban').ajaxForm({
            beforeSubmit: function (arr, data, options){
                $('#ban').attr('style', 'display: none');
                $('#js_ban_message').attr('class', 'alert loading no-margin top').html('Submitting Form...').slideDown(300);
            },
            success: ban_result,
            timeout: 5000 
        });
        
        // bind the Profile form using 'ajaxForm' 
        $('#profile').ajaxForm({
            beforeSubmit: function (arr, data, options){
                var form = data[0];
                var level = '{session.user.group_id}';
                var is_super = '{session.user.is_super_admin}';
                
                // Do some simple validation checks
                if(form.group_id.value >= level && is_super != 1) {
                    $('#js_profile_message').attr('class', 'alert error').html('You cannot set this users account level higher / equivalent to yourself.');
                    $('#js_profile_message').slideDown(600).delay(4000).slideUp(600);
                    return false;
                }else{
                    $('#js_profile_message').attr('class', 'alert loading').html('Submitting Form').slideDown(300);
                    return true;
                }
            },
            success: update_account_result,
            timeout: 3000 
        });
        
        // Date picker for the ban form
		$( "#unbandate" ).datepicker();

    });
</script>