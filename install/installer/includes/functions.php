<?php

/*
| ---------------------------------------------------------------
| Function: show_error()
| ---------------------------------------------------------------
|
| This function is used to simplify the showing of errors
|
*/	
    function show_error($message = 'none')
    {
        echo "<div class=\"alert error\">".$message."</div>";
    }
    
/*
| ---------------------------------------------------------------
| Function: output_message()
| ---------------------------------------------------------------
|
| This function is used to simplify the showing message
|
*/
    function output_message($type, $text)
    {
        echo "<div class=\"alert ".$type."\">".$text."</div>";
    }
    
/*
| ---------------------------------------------------------------
| Function: sha_password()
| ---------------------------------------------------------------
|
| Encrpypts the password
|
*/
    function sha_password($user, $pass)
    {
        $user = strtoupper($user);
        $pass = strtoupper($pass);
        return SHA1($user.':'.$pass);
    }

/*
| ---------------------------------------------------------------
| Function: get_real_phpversion()
| ---------------------------------------------------------------
|
| A function that returns the absolute php version
|
*/    
    function get_real_phpversion()
    {
        if(strpos(phpversion(), "-"))
        {
            return substr(phpversion(), 0, strpos(phpversion(), '-'));
        }
        else
        {
            return phpversion();
        }
    }
 
/*
| ---------------------------------------------------------------
| Function: get_database_connections()
| ---------------------------------------------------------------
|
| Easy way to connect to the databases after step 2
|
*/ 
    function get_database_connections($show)
    {
        // Check if provided info is correct
        $Realm = new Database();
        $r_info = array(
            'driver'	   => 'mysql',
            'host'         => $_POST['rdb_host'],
            'port'         => $_POST['rdb_port'],
            'username'     => $_POST['rdb_username'],
            'password'     => $_POST['rdb_password'],
            'database'     => $_POST['rdb_name']
        );

        // Attempt to connect
		$connect = $Realm->connect($r_info);
        if($connect !== true)
        {
            show_error('Counld Not connect to the Realm database!<br /><br />Error: '. $connect);
            die();
        }
        else
        {
            if($show) output_message('success', 'Successfully Connected to Realm DB.');
        }
        
        // Plexis DB
        $DB = new Database();
        $info = array(
            'driver'	   => 'mysql',
            'host'         => $_POST['db_host'],
            'port'         => $_POST['db_port'],
            'username'     => $_POST['db_username'],
            'password'     => $_POST['db_password'],
            'database'     => $_POST['db_name']
        );
        
        // Attempt to connect
		$connect = $DB->connect($info);
        if($connect !== true)
        {
            // Something went wrong, try opening information_schema instead.
            $DB = new Database();
            $info["database"] = "information_schema";
            
            // If we can connect to the information schema, then the database doesnt exist, so we create it
			$connect = $DB->connect( $info );
            if($connect === true)
            {
                //Create our database if it doesn't exist.
                $query = "CREATE DATABASE `" . $_POST["db_name"] . "`;";
                if( $DB->exec( $query ) )
                {
                    // Select the newly created database
                    $DB->exec("use ". $_POST['db_name'] .";");
                    goto Success; //Skip the error message below.
                }
            }
            
            show_error('Counld Not select Plexis database!<br /><br />Error: '. $connect);
            die();
        }
        else
        {
            Success:
				if($show) output_message('success', 'Successfully Connected to Plexis DB.');
        }
        
        return array('plexis' => $DB, 'realm' => $Realm);
    }
// EOF