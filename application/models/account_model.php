<?php
class Account_Model extends Application\Core\Model 
{

/*
| ---------------------------------------------------------------
| Constructer
| ---------------------------------------------------------------
|
*/
    function __construct()
    {
        parent::__construct();
    }

/*
| ---------------------------------------------------------------
| Function: create_key
| ---------------------------------------------------------------
|
| Creates a verification key for an account
|
*/    
    public function create_key($username)
    {
        // Create a really random string and length it to 20 characters
        $str = microtime(1);
        $key = sha1(base64_encode(pack("H*", md5(utf8_encode($str)))));
        $key = substr($key, 0, 20);

        // Insert the key into the DB
        $result = $this->DB->update('pcms_accounts', array('_activation_code' => $key), "`username`='".$username."'");
        if($result == TRUE) return $key;
        
        // If we are here, there was a DB error
        return FALSE; 
    }
    
/*
| ---------------------------------------------------------------
| Function: verify_key
| ---------------------------------------------------------------
|
| Verifies a verification key for an account
|
*/    
    public function verify_key($key)
    {
        // make sure this key doesnt already exist
        $result = $this->DB->query("SELECT `username` FROM `pcms_accounts` WHERE `_activation_code`=?", array($key))->fetch_column();
        if($result !== FALSE)
        {
            // Return the username
            return $result;
        }
        // If we are here, there the key didnt exist
        return FALSE; 
    }

/*
| ---------------------------------------------------------------
| Function: process_recovery
| ---------------------------------------------------------------
|
| The main process of recovering an account by changing the password
|
*/     
    public function process_recovery($id, $username, $email)
    {
        // Generate a new password, 8 characters long
        $str = microtime(1);
        $key = sha1(base64_encode(pack("H*", md5(utf8_encode($str)))));
        $password = substr($key, 0, 8);
        
        // Tell the realm to change the pass
        $result = $this->realm->change_password($id, $password);
        
        // Check the result, if FALSE then we failed to change the password :O
        if($result == FALSE)
        {
            output_message('error', 'account_recover_pass_failed');
            return FALSE;
        }
        
        // Check if the admin wants the user to get the new password in email
        if( config('account_recover_email_pass') == TRUE)
        {
            // Email password
            // Setup our variables and load our extensions
            $site_title = config('site_title');
            $site_email = config('site_email');
            $lang = load_language_file('emails');
            $this->email = $this->load->library('email');
            
            // Create out email message
            $message = vsprintf( $lang['email_recovery_message'], array( $username, $site_title, $password ) );
            
            // Build the email
            $this->email->to( $email, $username );
            $this->email->from( $site_email, $site_title );
            $this->email->subject( $lang['email_recovery_subject'] );
            $this->email->message( $message );
            $sent = $this->email->send();
            
            // Check if our email sent correctly
            if($sent == TRUE)
            {
                output_message('success', 'account_recover_pass_success_email');
                return TRUE;
            }
            else
            {
                // We arent going to let a failed email prevent our user from recovering his account
                output_message('success', 'account_recover_pass_success', array($password));
                return TRUE;
            }
        }
        
        // Send a success message and include the new password there
        output_message('success', 'account_recover_pass_success', array($password));
        return TRUE;
    }
}
// EOF