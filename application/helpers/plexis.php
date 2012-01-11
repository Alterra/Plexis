<?php
/*
| ---------------------------------------------------------------
| Function: output_message
| ---------------------------------------------------------------
|
| This method produces messages to be displayed in the template.
| These messages include success, warning, and error messages.
|
| @Param: (String) $level - The level of the message. There are 
|	4 levels, 'error', 'warning', 'info', and 'success'.
| @Param: (String) $message - The message in the box
| @Param: (Array) $args - An array for replacing %s' (sprints)
| @Param: (String) $file - The message file to be loaded
| @Return (None)
|
*/
    function output_message($level, $message, $args = NULL, $file = 'messages')
    {
        // Load language
        $lang = load_language_file( $file );
        $text = (isset($lang[$message])) ? $lang[$message] : $message;

        // do replacing
        if(is_array($args))
        {
            $text = vsprintf($text, $args);
        }
        
        // Set the global
        $GLOBALS['template_messages'][] = "<div class=\"alert ".$level."\">".$text."</div>";
        return;
    }
    
/*
| ---------------------------------------------------------------
| Function: selected_language
| ---------------------------------------------------------------
|
| This method returns the users selected language
|
| @Return (String) The language string
|
*/
    function selected_language()
    {
        // Load language
        $input = load_class('Input');
        if(isset($_COOKIE['language']))
        {
            $language = $input->cookie('language', TRUE);
        }
        else
        {
            $language = config('default_language');
            $input->set_cookie('language', $language);
        }
        
        // Return the language string
        return $language;	
    }

/*
| ---------------------------------------------------------------
| Function: load_language_file
| ---------------------------------------------------------------
|
| This method load a specific language file based on the users
| selected language.
|
| @Param: (String) $file - The language file we are loading
| @Return (Array) An array of all the language vars
|
*/
    function load_language_file($file)
    {
        // Load language
        $language = selected_language();
        
        // Init the language class
        $lang = load_class('Language');
        $lang->set_language( $language );
        $array = $lang->load($file, $language, TRUE );
        
        // If we got a false return from the language class, file was not found
        if(!$array)
        {
            // Try and load the default lanuage
            $lang->set_language( $file, config('default_language') );
            $array = $lang->load($file);
            
            // If another false return, could not locate language file
            if(!$array)
            {
                trigger_error('Unable to load the site default or requested language file: '.$language.'/'.$file);
            }
        }
        return $array;	
    }
    
/*
| ---------------------------------------------------------------
| Method: get_port_status()
| ---------------------------------------------------------------
|
| This function is used to get the port status of a domain/port
|
| @Return: (Bool) Returns TRUE if the domain us up, FALSE otherwise
|
*/
    function get_port_status($host, $port = 80, $timeout = 3)
    {
        // Load the debug class and disable error reporting
        $Debug = load_class('Debug');
        $Debug->error_reporting( FALSE );
        
        // OPen the connections
        $handle = fsockopen($host, $port, $errno, $errstr, $timeout);
        
        // Restore error reporting
        $Debug->error_reporting( TRUE );
        return $handle;
    }
    

/*
| ---------------------------------------------------------------
| Method: load_module_config()
| ---------------------------------------------------------------
|
| This function is used to load a modules config file, and add
| those config values to the site config.
|
| @Param: (String) $module - Name of the module
| @Param: (String) $filename - name of the file if not 'config.php'
| @Param: (String) $array - If the config vars are stored in an array, whats
|	the array variable name?
| @Return: (None)
|
*/
    function load_module_config($module, $filename = 'config.php', $array = FALSE)
    {	
        // Get our filename and use the load_config method
        $file = APP_PATH . DS .'modules' . DS . $module . DS . 'config' . DS . $filename;
        load_config($file, 'mod', $array);
    }

/*
| ---------------------------------------------------------------
| Method: get_realm_cookie()
| ---------------------------------------------------------------
|
| This function returns the users selected realm from his cookie
|
| @Return: (AInt) The Realm ID
|
*/    
    function get_realm_cookie()
    {
        // Load the input class
        $input = load_class('Input');
        $return = $input->cookie('realm_id', TRUE);
        if($return != FALSE)
        {
            // We need to make sure the realm is still installed
            $DB = load_class('Loader')->database( 'DB' );
            $query = "SELECT `name` FROM `pcms_realms` WHERE `id`=?";
            $result = $DB->query( $query, array($return) )->fetch_column();
            
            //If false, we set a new cookie with the dealt ID
            if($result == FALSE)
            {
                // Hard set the cookie to default
                $return = config('default_realm_id');
                $input->set_cookie('realm_id', $return);
                $_COOKIE['realm_id'] = $return;
            } 
        }
        else
        {
            // Hard set the cookie to default
            $return = config('default_realm_id');
            $input->set_cookie('realm_id', $return);
            $_COOKIE['realm_id'] = $return;
        }
        return $return;
    }

/*
| ---------------------------------------------------------------
| Method: get_installed_realms()
| ---------------------------------------------------------------
|
| This function is used to return an array of site installed realms.
|
| @Return: (Array) Array of installed realms
|
*/
    function get_installed_realms()
    {
        $load = load_class('Loader');
        $DB = $load->database( 'DB' );
        
        // Build our query
        $query = "SELECT * FROM `pcms_realms`";
        return $DB->query( $query )->fetch_array();
    }
    
/*
| ---------------------------------------------------------------
| Method: realm_installed()
| ---------------------------------------------------------------
|
| This function is used to find out if a realm is installed based on ID
|
| @Return: (Bool) True if the realm is installed, FALSE otherwise
|
*/
    function realm_installed($id)
    {
        $load = load_class('Loader');
        $DB = $load->database( 'DB' );
        
        // Build our query
        $query = "SELECT `name` FROM `pcms_realms` WHERE `id`=?";
        $result = $DB->query( $query, array($id) )->fetch_column();
        
        // Make our return
        if($result == FALSE)
        {
            return FALSE;
        }
        return TRUE;
    }
    
/*
| ---------------------------------------------------------------
| Method: get_realm()
| ---------------------------------------------------------------
|
| This function is used to fetch the realm info based on ID
|
| @Return: (Array) Array of Realm information, FALSE otherwise
|
*/
    function get_realm($id)
    {
        $load = load_class('Loader');
        $DB = $load->database( 'DB' );
        
        // Build our query
        $query = "SELECT * FROM `pcms_realms` WHERE `id`=?";
        $result = $DB->query( $query, array($id) )->fetch_row();
        
        // Make our return
        if($result == FALSE)
        {
            return FALSE;
        }
        return $result;
    }
    
/*
| ---------------------------------------------------------------
| Method: get_realm_status()
| ---------------------------------------------------------------
|
| This function is used to return an array of site installed realms.
|
| @Param: (int) $id: The ID of the realm, 0 for an array or all
| @Return: (Array) Array of installed realms
|
*/
    function get_realm_status($id = 0)
    {
        // Check the cache to see if we recently got the results
        $load = load_class('Loader');
        $Cache = $load->library('Cache');
        
        // See if we have cached results
        $result = $Cache->get('realm_status_'.$id);
        if($result == FALSE)
        {
            // If we are here, then the cache results were expired
            $Debug = load_class('Debug');
            $DB = $load->database( 'DB' );
            
            // All realms?
            if($id == 0)
            {
                // Build our query
                $query = "SELECT `id`, `name`, `address`, `port` FROM `pcms_realms`";
            }
            else
            {
                $query = "SELECT `id`, `name`, `address`, `port` FROM `pcms_realms` WHERE `id`=?";
            }
            
            // fetch the array of realms
            $realms = $DB->query( $query )->fetch_array();
            
            // Dont log errors
            $Debug->error_reporting(false);
            
            // Loop through each realm, and get its status
            foreach($realms as $key => $realm)
            {
                $handle = fsockopen($realm['address'], $realm['port'], $errno, $errstr, 1);
                if(!$handle)
                {
                    $realms[$key]['status'] = 0;
                }
                else
                {
                    $realms[$key]['status'] = 1;
                }
            }
            
            // Re-enable errors, and Cache the results for 5 minutes
            $Debug->error_reporting(true);
            $Cache->save('realm_status_'.$id, $realms, 300);
            return $realms;
        }
        return $result;
    }
    
/*
| ---------------------------------------------------------------
| Method: get_wowlib_drivers()
| ---------------------------------------------------------------
|
| This function is used to return an array of WoWLib drivers
|
| @Return: (Array) Array of drivers
|
*/
    function get_wowlib_drivers()
    {
        $reallist = FALSE;
        $list = scandir(APP_PATH . DS . 'library' . DS . 'wowlib');
        foreach($list as $file)
        {
            if($file == "." || $file == ".." || $file == "index.html") continue;
            $reallist[] = str_replace(".php", '', $file);
        }
        return $reallist;
    }

/*
| ---------------------------------------------------------------
| Method: get_modules()
| ---------------------------------------------------------------
|
| This function is used to return an array of modules in the modules
| folder
|
| @Return: (Array) Array of drivers
|
*/
    function get_modules()
    {
        $reallist = array();
        $list = scandir(APP_PATH . DS . 'modules');
        foreach($list as $file)
        {
            if($file == "." || $file == ".." || !is_dir($file)) continue;
            $reallist[] = $file;
        }
        return $reallist;
    }

/*
| ---------------------------------------------------------------
| Method: get_installed_modules()
| ---------------------------------------------------------------
|
| This function is used to return an array of site installed modules.
|
| @Return: (Array) Array of installed realms
|
*/
    function get_installed_modules()
    {
        $load = load_class('Loader');
        $DB = $load->database( 'DB' );
        
        // Build our query
        $query = "SELECT * FROM `pcms_modules`";
        return $DB->query( $query )->fetch_array();
    }
    
/*
| ---------------------------------------------------------------
| Method: module_installed()
| ---------------------------------------------------------------
|
| This function is used to find out if a module is installed based 
| on the module name
|
| @Return: (Bool) True if the realm is installed, FALSE otherwise
|
*/
    function module_installed($name)
    {
        $load = load_class('Loader');
        $DB = $load->database( 'DB' );
        
        // Build our query
        $query = "SELECT `uri` FROM `pcms_realms` WHERE `name`=?";
        $result = $DB->query( $query, array($name) )->fetch_column();
        
        // Make our return
        if($result == FALSE)
        {
            return FALSE;
        }
        return TRUE;
    }

/*
| ---------------------------------------------------------------
| Method: get_secret_questions()
| ---------------------------------------------------------------
|
| This function is used to return an array of WoWLib drivers
|
| @Param: (String) $type: Either return as an 'array' or 'string'
| @Param: (Bool) $notags: Return without option tags?
| @Return: (Array / String) Array or String of quertions wrapped in option tags
|
*/    
    function get_secret_questions($type = 'string', $notags = FALSE)
    {
        // create our return array and load the secret questions language file
        ($type == 'string') ? $return = '' : $return = array();
        $vars = load_language_file('secret_questions');
        
        // Add option tags
        foreach($vars as $key => $q)
        {
            if($type == 'string')
            {
                $return .= "<option value='".$key."'>".$q."</option>\n";
            }
            else
            {
                if($notags == TRUE)
                {
                    $return[$key] = $q;
                }
                else
                {
                    $return[$key] = "<option value='".$key."'>".$q."</option>";
                }
            }
        }
        
        // Return the questions
        return $return;
    }
	
/*
| ---------------------------------------------------------------
| Method: uri_segment
| ---------------------------------------------------------------
|
| This function is used to exctract a specific piece of the url.
|
| @Param: (String) $index: The zero-based index of the url part to return.
| @Return: (String / Null) String containing the specified url part, 
|   null if the index it out of bounds of the array.
|
*/

    function uri_segment($index)
    {
        $Router = load_class('Router');
        return $Router->get_uri_segement($index);
    }
// EOF