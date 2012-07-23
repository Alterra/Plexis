<?php
/* 
| --------------------------------------------------------------
| 
| Plexis
|
| --------------------------------------------------------------
|
| Author:       Tony Hudgins
| Copyright:    Copyright (c) 2012, Plexis Dev Team
| License:      GNU GPL v3
|
*/
namespace Wowlib;

// Include the iRealm Interface before loading the class
require_once  path( ROOT, 'third_party', 'wowlib', 'interfaces', 'iRealm.php');

class Realm implements iRealm
{

/*
| ---------------------------------------------------------------
| Constructor
| ---------------------------------------------------------------
|
*/
    public function __construct()
    {
        // Load our realm dataabase connetion
        $this->load = load_class('Loader');
        $this->DB = $this->load->database('RDB');
        
        // Include the iAccount Interface
        require_once path( ROOT, 'third_party', 'wowlib', 'interfaces', 'iAccount.php');
    }
    
/*
| ---------------------------------------------------------------
| Method: realmlist()
| ---------------------------------------------------------------
|
| This function gets the realmlist from the database
|
| @Return (Array) - Returns an array of realms and thier columns
|
*/
    public function realmlist()
    {
        // Grab Realms
        //$query = "SELECT * FROM `realmlist`";
        //return $this->DB->query( $query )->fetch_array();
        
        //This function doesn't return anything since ArcEmu doesn't store the realms in a data table.
        return array();
    }
    
/*
| ---------------------------------------------------------------
| Method: fetchRealm()
| ---------------------------------------------------------------
|
| This function gets the realm cols. from the realmlist table
|
| @Param: (Int) $id - The realm ID we are requesting the information from
| @Return (Array) - Returns an array of cols. for the realm id
|
*/
    public function fetchRealm($id)
    {
        // Grab Realms
        //$query = "SELECT * FROM `realmlist` WHERE `id`=?";
        //return $this->DB->query( $query, array($id) )->fetch_row();
        
        //Again, doesn't return anything.
        return array();
    }
    
/*
| ---------------------------------------------------------------
| Method: uptime()
| ---------------------------------------------------------------
|
| This function gets the realms $id uptime
|
| @Param: (Int) $id - The realm ID we are requesting the information from
| @Return (Int) Time string of FALSE if unavailable
|
*/
    public function uptime($id)
    {
        return FALSE;
    }
    
/*
| ---------------------------------------------------------------
| Method: createAccount()
| ---------------------------------------------------------------
|
| This function creates an account using the provided username
|   and password.
|
| @Param: (String) $username - The account username
| @Param: (String) $password - The new account (unencrypted) password
| @Param: (String) $email - The new account email
| @Param: (String) $ip - The Registeree's IP address
| @Return: Returns the new Account ID on success, FALSE otherwise
|
*/
    public function createAccount($username, $password, $email = NULL, $ip = '0.0.0.0')
    {
        // Make sure the username doesnt exist, just incase the script didnt check yet!
        if($this->accountExists($username)) return false;
        
        // SHA1 the password
        $user = strtoupper($username);
        $pass = strtoupper($password);
        $sha = sha1($user.':'.$pass);
        
        // Build our tables and values for Database insertion
        $data = array(
            'login' => $username, 
            'password' => $password, 
            'encrypted_password' => $sha,
            'email' => $email, 
            'lastip' => $ip
        );
        
        // Insert into the database
        $this->DB->insert("accounts", $data);
        
        // If we have an affected row, then we return TRUE
        return ($this->DB->num_rows() > 0) ? $this->DB->last_insert_id() : false;
    }
    
/*
| ---------------------------------------------------------------
| Method: validate()
| ---------------------------------------------------------------
|
| This function takes a username and password, and logins in with
|   that information. If the password matches the pasword in the
|   database, we return the account id. Else we return FALSE,
|
| @Param: (String) $username - The account username
| @Param: (String) $password - The account (unencrypted) password
| @Return (Mixed) - Returns account ID on success, FALSE otherwise
|
*/
    public function validate($username, $password)
    {
        // Load the users info from the Realm DB
        $query = "SELECT `acct`, `password` FROM `accounts` WHERE `login`=?";
        $result = $this->DB->query( $query, array($username) )->fetch_row();
        
        // Make sure the username exists!
        if(!is_array($result)) return false;
        
        // If the result was false, then username is no good. Also match passwords.
        return ( $result['password'] == $password ) ? $result['acct'] : false;
    }
    
/*
| ---------------------------------------------------------------
| Method: fetchAccount()
| ---------------------------------------------------------------
|
| This function queries the accounts table and pulls all the users
|   information into an object
|
| @Param: (Int) $id - The account ID we are loading
| @Return (Object) - returns the account object
|
*/
    public function fetchAccount($id)
    {
        try {
            $account = new Account($id, $this);
        }
        catch(\Exception $e) {
            $account = false;
        }
        return $account;
    }
    
/*
| ---------------------------------------------------------------
| Method: accountExists()
| ---------------------------------------------------------------
|
| This function queries the accounts table and finds if the given
|   account ID exists.
|
| @Param: (Int | String) $id - The account ID we are checking for,
|   or the account username
| @Return (Bool) - TRUE if the id exists, FALSE otherwise
|
*/
    public function accountExists($id)
    {
        // Check the Realm DB for this username / account ID
        if(is_numeric($id))
            $query = "SELECT `login` FROM `accounts` WHERE `id`=?";
        else
            $query = "SELECT `id` FROM `accounts` WHERE `username` LIKE ? LIMIT 1";

        // If the result is NOT false, we have a match, username is taken
        $res = $this->DB->query( $query, array($id) )->fetch_column();
        return ($res !== false);
    }
    
/*
| ---------------------------------------------------------------
| Method: emailExists()
| ---------------------------------------------------------------
|
| This function queries the accounts table and finds if the given
|   email exists.
|
| @Param: (String) $email - The email we are checking for
| @Return (Bool) - TRUE if the id exists, FALSE otherwise
|
*/
    public function emailExists($email)
    {
        // Check the Realm DB for this username
        $query = "SELECT `login` FROM `accounts` WHERE `email`=?";
        $res = $this->DB->query( $query, array($email) )->fetch_column();
        
        // If the result is NOT false, we have a match, username is taken
        return ($res !== FALSE);
    }

/*
| ---------------------------------------------------------------
| Function: accountBanned()
| ---------------------------------------------------------------
|
| Checks the realm database if the account is banned
|
| @Param: (Int) $account_id - The account id we are checking
| @Return (Bool) Returns TRUE if the account is banned
|
*/
    public function accountBanned($account_id)
    {
        $query = "SELECT COUNT(*) FROM `accounts` WHERE `banned` > 0 AND `acct` = ?;";
        $check = $this->DB->query( $query, array($account_id) )->fetch_column();
        return ($check !== FALSE && $check > 0) ? true : false;
    }

/*
| ---------------------------------------------------------------
| Function: ipBanned()
| ---------------------------------------------------------------
|
| Checks the realm database if the users IP is banned
|
| @Param: (String) $ip - The IP we are checking
| @Return (Bool) Returns TRUE if the account is banned
|
*/
    public function ipBanned($ip)
    {
        $query = "SELECT COUNT(*) FROM `ipbans` WHERE `ip`=?";
        $check = $this->DB->query( $query, array($ip) )->fetch_column();
        return ($check !== FALSE && $check > 0) ? true : false;
    }
    
/*
| ---------------------------------------------------------------
| Method: banAccount()
| ---------------------------------------------------------------
|
| Bans a user account
|
| @Param: (Int) $id - The account ID
| @Param: (String) $banreason - The reason user is being banned
| @Param: (String) $unbandate - The unban date timestamp
| @Param: (String) $banedby - Who is banning the user?
| @Param: (Bool) $banip - Ban ip as well?
| @Return (Bool) TRUE on success, FALSE on failure
|
*/ 
    public function banAccount($id, $banreason, $unbandate = NULL, $bannedby = 'Admin', $banip = FALSE)
    {
        // Check for account existance
        if(!$this->accountExists($id)) return false;

        // Make sure our unbandate is set, 1 year default
        if($unbandate == NULL) $unbandate = (time() + 31556926);
        $data = array(
            'banned' => $unbandate, 
            'banreason' => $banreason
        ); 
        $result = $this->DB->update('accounts', $data, "`acct` = '$id'");
        
        // Do we ban the IP as well?
        return ($banip == true) ? $this->banAccountIp($id, $banreason, $unbandate, $bannedby) : $result;
    }
    
/*
| ---------------------------------------------------------------
| Method: banAccountIp()
| ---------------------------------------------------------------
|
| Bans an accounts IP address
|
| @Param: (Int) $id - The account ID
| @Param: (String) $banreason - The reason user is being banned
| @Param: (String) $unbandate - The unban date timestamp
| @Param: (String) $banedby - Who is banning the user?
| @Return (Bool) TRUE on success, FALSE on failure
|
*/ 
    public function banAccountIp($id, $banreason, $unbandate = NULL, $bannedby = 'Admin')
    {
        // Check for account existance
        $query = "SELECT `lastip` FROM `accounts` WHERE `acct`=?";
        $ip = $this->DB->query( $query, array($id) )->fetch_column();
        if(!$ip) return false;
        
        // Check if the IP is already banned or not
        if( $this->ipBanned($ip) ) return true;

        // Make sure our unbandate is set, 1 year default
        if($unbandate == NULL) $unbandate = (time() + 31556926);
        $data = array(
            'ip' => $ip,
            'expire' => $unbandate,
            'banreason' => $banreason, 
        ); 
        return $this->DB->insert('ipbans', $data);
    }
    
/*
| ---------------------------------------------------------------
| Method: unbanAccount()
| ---------------------------------------------------------------
|
| Un-Bans a user account
|
| @Param: (Int) $id - The account ID
| @Return (Bool) TRUE on success, FALSE on failure
|
*/ 
    public function unbanAccount($id)
    {
        // Check if the account is not Banned
        if( !$this->accountBanned($id) ) return true;
        
        // Check for account existance
        return $this->DB->update("accounts", array('banned' => 0, 'banreason' => ''), "`acct`=".$id);
    }
    
/*
| ---------------------------------------------------------------
| Method: unbanAccountIp()
| ---------------------------------------------------------------
|
| Un-Bans a users account IP
|
| @Param: (Int) $id - The account ID
| @Return (Bool) TRUE on success, FALSE on failure
|
*/ 
    public function unbanAccountIp($id)
    {
        // Check for account existance
        $query = "SELECT `lastip` FROM `accounts` WHERE `acct`=?";
        $ip = $this->DB->query( $query, array($id) )->fetch_column();
        if(!$ip) return false;
        
        // Check if the IP is banned or not
        if( !$this->ipBanned($ip) ) return true;
        
        // Check for account existance
        return $this->DB->delete("ipbans", "`ip`=".$ip);
    }
    
/*
| ---------------------------------------------------------------
| Method: deleteAccount()
| ---------------------------------------------------------------
|
| Un-Bans a users account IP
|
| @Param: (Int) $id - The account ID
| @Return (Bool) TRUE on success, FALSE on failure
|
*/ 
    public function deleteAccount($id)
    {
        // Delete the account
        return $this->DB->delete("accounts", "`acct`=".$id);
    }
    
/*
| ---------------------------------------------------------------
| Function: expansions()
| ---------------------------------------------------------------
|
| Returns an array of supported expansions by this realm. Donot
| include expansions that arent supported in this array!
|
| @Return (Array)
|   0 => None, Base Game
|   1 => Burning Crusade
|   2 => WotLK
|   3 => Cata (If Supported)
|   4 => MoP (If Supported)
|
*/
    
    public function expansions()
    {
        // Expansion ID => Expansion Name
        return array(
            0 => "Classic",
            1 => "The Burning Crusade",
            2 => "Wrath of the Lich King"
        );
    }
    
/*
| ---------------------------------------------------------------
| Function: expansionToText()
| ---------------------------------------------------------------
|
| Returns the expansion text name
|
| @Return (String) Returns false if the expansion doesnt exist
|
*/
    
    public function expansionToText($id = 0)
    {
        // return all expansions if no id is passed
        $exp = $this->expansions();
        return (isset($exp[$id])) ? $exp[$id] : false;
    }
    
/*
| ---------------------------------------------------------------
| Function: expansionToBit()
| ---------------------------------------------------------------
|
| Returns the Database ID of the given expansion
|
| @Return (Int)
|
*/
    
    public function expansionToBit($e)
    {
        switch($e)
        {
            case 0: // Base Game
                return 0;
            case 1: // Burning Crusade
                return 8;
            case 2: // WotLK
                return 24;
            default: // WotLK
                return 24;
        }
    }
    
/*
| ---------------------------------------------------------------
| Function: expansionToBit()
| ---------------------------------------------------------------
|
| Returns the expansion ID based off of the given Database ID of 
|   expansion. This only reflects Arcemu really...
|
| @Return (Int)
|
*/
    
    public function bitToExpansion($e)
    {
        switch($e)
        {
            case 0: // Base Game
                return 0;
            case 8: // Burning Crusade
                return 1;
            case 16: // WotLK (no BC)
            case 24: // WotLK
                return 2;
            case 36:
                return 3;
            default: // WotLK
                return 2;
        }
    }
    
/*
| ---------------------------------------------------------------
| Function: numAccounts()
| ---------------------------------------------------------------
|
| This methods returns the number of accounts in the accounts table.
|
| @Return (Int) The number of accounts
|
*/
    
    public function numAccounts()
    {
        return $this->DB->query("SELECT COUNT(`acct`) FROM `accounts`")->fetch_column();
    }
    
/*
| ---------------------------------------------------------------
| Function: numBannedAccounts()
| ---------------------------------------------------------------
|
| This methods returns the number of accounts in the accounts table.
|
| @Return (Int) The number of accounts
|
*/
    
    public function numBannedAccounts()
    {
        return $this->DB->query("SELECT COUNT(`acct`) FROM `accounts` WHERE `banned` > 0")->fetch_column();
    }
    
/*
| ---------------------------------------------------------------
| Function: numInactiveAccounts()
| ---------------------------------------------------------------
|
| This methods returns the number of accounts that havent logged
|   in withing the last 3 months
|
| @Return (Int) The number of accounts
|
*/
    
    public function numInactiveAccounts()
    {
        // 90 days or older
        $time = time() - 7776000;
        $query = "SELECT COUNT(`acct`) FROM `accounts` WHERE UNIX_TIMESTAMP(`lastlogin`) <  $time";
        return $this->DB->query( $query )->fetch_column();
    }
    
/*
| ---------------------------------------------------------------
| Function: numActiveAccounts()
| ---------------------------------------------------------------
|
| This methods returns the number of accounts that have logged
|   in withing the last 24 hours
|
| @Return (Int) The number of accounts
|
*/
    
    public function numActiveAccounts()
    {
        // 90 days or older
        $time = date("Y-m-d H:i:s", time() - 86400);
        $query = "SELECT COUNT(`acct`) FROM `accounts` WHERE `lastlogin` BETWEEN  '$time' AND NOW()";
        return $this->DB->query( $query )->fetch_column();
    }
}


/* 
| -------------------------------------------------------------- 
| Account Object
| --------------------------------------------------------------
|
| Author:       Steven Wilson
| Copyright:    Copyright (c) 2012, Plexis Dev Team
| License:      GNU GPL v3
|
*/
class Account implements iAccount
{
    // Our Parent wowlib class and Database connection
    protected $DB;
    protected $parent;
    
    // Have we changed our username? If so, we must have set a password!
    protected $changed = false;
    
    // Our temporary password when the setPassword method is called
    protected $password;
    
    // Account ID and User data array
    protected $id;
    protected $data = array();
/*
| ---------------------------------------------------------------
| Constructor
| ---------------------------------------------------------------
|
*/
    public function __construct($acct, $parent)
    {
        // Load the realm database connection
        $this->load = load_class('Loader');
        $this->DB = $this->load->database('RDB');
        
        // Setup local user variables
        $this->parent = $parent;
        
        // Prepare the column name for the WHERE statement based off of $acct type
        $col = (is_numeric($acct)) ? 'acct' : 'login';
        
        // Load the user
        // Check the Realm DB for this username
        $query = "SELECT
            `acct`,
            `login`,
            `password`,
            `encrypted_password`,
            `banned`,
            `email`,
            `lastip`,
            `locked`,
            `lastlogin`,
            `flags`
            FROM `accounts` WHERE `{$col}`= ?";
        $this->data = $this->DB->query( $query, array($acct) )->fetch_row();
        
        // If the result is NOT false, we have a match, username is taken
        if(!is_array($this->data)) throw new \Exception('User Doesnt Exist');
    }
    
/*
| ---------------------------------------------------------------
| Method: save()
| ---------------------------------------------------------------
|
| This method saves the current account data in the database
|
| @Retrun: (Bool): If the save is successful, returns TRUE
|
*/ 
    public function save()
    {
        // First we have to check if the username was changed
        if($this->changed)
        {
            if(empty($this->password)) return false;
            
            // Make sure the sha hash is set correctly
            $this->setPassword($this->password);
        }
        
        return ($this->DB->update('accounts', $this->data, "`acct`= $this->id") !== false);
    }
    
/*
| ---------------------------------------------------------------
| Method: getId()
| ---------------------------------------------------------------
|
| This method returns the account id
|
| @Return (Int)
|
*/
    public function getId()
    {
        return (int) $this->data['acct'];
    }
    
/*
| ---------------------------------------------------------------
| Method: getUsername()
| ---------------------------------------------------------------
|
| This method returns the account login
|
| @Return (String)
|
*/
    public function getUsername()
    {
        return $this->data['login'];
    }
    
/*
| ---------------------------------------------------------------
| Method: getEmail()
| ---------------------------------------------------------------
|
| This method returns the account email address
|
| @Return (String)
|
*/
    public function getEmail()
    {
        return $this->data['email'];
    }
    
/*
| ---------------------------------------------------------------
| Method: joinDate()
| ---------------------------------------------------------------
|
| This method returns the joindate for this account
|
| @Return (mixed)
|
*/
    public function joinDate($asTimestamp = false)
    {
        // Arcemu does not support this
        return false;
    }
    
/*
| ---------------------------------------------------------------
| Method: lastLogin()
| ---------------------------------------------------------------
|
| This method returns the last login date / time for this account
|
| @Return (Mixed)
|
*/
    public function lastLogin($asTimestamp = false)
    {
        return ($asTimestamp == true) ? strtotime($this->data['lastlogin']) : $this->data['lastlogin'];
    }
    
/*
| ---------------------------------------------------------------
| Method: getLastIp()
| ---------------------------------------------------------------
|
| This method returns the accounts last seen IP
|
| @Return (String)
|
*/
    public function getLastIp()
    {
        return $this->data['lastip'];
    }
    
/*
| ---------------------------------------------------------------
| Method: isLocked()
| ---------------------------------------------------------------
|
| This method returns if the account is locked
|
| @Return (Bool)
|
*/
    public function isLocked()
    {
        return (bool) $this->data['banned'];
    }
    
/*
| ---------------------------------------------------------------
| Method: getExpansion()
| ---------------------------------------------------------------
|
| This method returns the accounts expansion ID
|
| @Return (Int)
|
*/
    public function getExpansion($asText = false)
    {
        $id = $this->bitToExpansion($this->data['flags']);
        return ($asText == true) ? $this->parent->expansionToText($id) : $id;
    }
    
/*
| ---------------------------------------------------------------
| Method: setPassword()
| ---------------------------------------------------------------
|
| This method sets the password to the account.
|
| @Param: (String) $password - The new account (unencrypted) password
| @Return (Bool) - Returns false only if password is less then 3 chars.
|
*/
    public function setPassword($password)
    {
        // Remove whitespace in password
        $password = trim($password);
        if(strlen($password) < 3) return false;
        
        // Set our passwords
        $this->password = $password;
        $this->data['encrypted_password'] = sha1( strtoupper($this->data['login'] .':'. $password) );
        $this->data['password'] = $password;
        return true;
    }
    
/*
| ---------------------------------------------------------------
| Method: setUsername()
| ---------------------------------------------------------------
|
| This method sets the username to the account.
|
| @Param: (String) $username - The new account username / login
| @Return (Bool) - Returns false only if username is less then 3 chars.
|
*/
    public function setUsername($username)
    {
        // Remove whitespace
        $username = trim($username);
        if(strlen($username) < 3) return false;
        
        // Set our username if its not the same as before
        if($username != $this->data['login'])
        {
            $this->changed = true;
            $this->data['login'] = $username;
            return true;
        }
    }
    
/*
| ---------------------------------------------------------------
| Method: setEmail()
| ---------------------------------------------------------------
|
| This method sets an accounts email address
|
| @Return (None)
|
*/
    public function setEmail($email)
    {
        $this->data['email'] = $email;
    }
    
/*
| ---------------------------------------------------------------
| Method: setExpansion()
| ---------------------------------------------------------------
|
| This method sets the expansion to the account.
|
| @Param: (Int) $e - Sets the expansion level of the account
|   0 => None, Base Game
|   1 => Burning Crusade
|   2 => WotLK
|   3 => Cata (If Supported)
|   4 => MoP (If Supported)
| @Return (None)
|
*/
    public function setExpansion($e)
    {
        $this->data['flags'] = $this->parent->expansionToBit($e);
    }
    
/*
| ---------------------------------------------------------------
| Method: setLocked()
| ---------------------------------------------------------------
|
| This method sets the locked status of an account
|
| @Return (None)
|
*/
    public function setLocked($locked)
    {
        // Arcemu doesnt support this
        return false;
    }
}
// EOF