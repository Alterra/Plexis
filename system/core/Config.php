<?php
/* 
| --------------------------------------------------------------
| Plexis Core
| --------------------------------------------------------------
| Author:       Steven Wilson
| Copyright:    Copyright (c) 2011, Steven Wilson
| License:      GNU GPL v3
| ---------------------------------------------------------------
| Class: Config
| ---------------------------------------------------------------
|
| Main Config class. used to load, set, and save variables used
| in the config file.
|
*/
namespace Core;

class Config
{
    // An array of all out stored containers / variables
    protected static $data = array();

    // A list of our loaded config files
    protected static $files = array();


/*
| ---------------------------------------------------------------
| Method: GetVar()
| ---------------------------------------------------------------
|
| Returns the variable ($key) value in the config file.
|
| @Param: (String) $key - variable name. Value is returned
| @Param: (Mixed) $name - config variable container name
| @Return: (Mixed) May return NULL if the var is not set
|
*/
    public static function GetVar($key, $name) 
    {
        // Lowercase the type
        $name = strtolower($name);
        
        // Check if the variable exists
        return (isset(self::$data[$name][$key])) ? self::$data[$name][$key] : NULL;
    }
    
/*
| ---------------------------------------------------------------
| Method: FetchVars()
| ---------------------------------------------------------------
|
| Returns all variables in an array from the the config file.
|
| @Param: (Mixed) $name - config variable container name
| @Return: (Array) May return NULL if the var is not set
|
*/
    public static function FetchVars($name) 
    {
        // Lowercase the type
        $name = strtolower($name);
        
        // Check if the variable exists
        return (isset(self::$data[$name])) ? self::$data[$name] : NULL;
    }

/*
| ---------------------------------------------------------------
| Method: SetVar()
| ---------------------------------------------------------------
|
| Sets the variable ($key) value. If not saved, default value
| will be returned as soon as page is re-loaded / changed.
|
| @Param: (String or Array) $key - variable name to be set
| @Param: (Mixed) $value - new value of the variable
| @Param: (Mixed) $name - The container name for the $key variable
| @Return: (Bool) Returns false if the config file denies set perms
|
*/
    public static function SetVar($key, $val = false, $name) 
    {
        // Lowercase the $name
        $name = strtolower($name);
        
        // Make sure this config has set permissions
        if(!self::$files[$name]['allow_set'])
            return false;
        
        // If we have array, loop through and set each
        if(is_array($key))
        {
            foreach($key as $k => $v)
            {
                self::$data[$name][$k] = $v;
            }
        }
        else
        {
            self::$data[$name][$key] = $val;
        }
        
        return true;
    }

/*
| ---------------------------------------------------------------
| Method: Load()
| ---------------------------------------------------------------
|
| Load a config file, and adds its defined variables to the $data
|   array
|
| @Param: (String) $_Cfile - Full path to the config file, includeing name
| @Param: (String) $_Cname - The container name we are storing this configs
|   variables to.
| @Param: (String) $_Carray - If all of the config vars are stored in an array, 
|   whats the array variable name? Default is false
| @Param: (Bool) $_CallowSet - If set to false, config values are readonly, and cannot
|   be set via the 'SetVar' method. 
| @Param: (Bool) $_CallowSave - If set to true, the config file cannot be written
|   to by the 'Save' method. Also, if $_CallowSet is false, this value is
|   false as well, no matter the actual set value.
| @Return: (Bool)
|
*/
    public static function Load($_Cfile, $_Cname, $_Carray = false, $_CallowSet = true, $_CallowSave = true) 
    {
        // Lowercase the $name
        $_Cname = strtolower($_Cname);
        
        // Donot load the config twice!
        if(array_key_exists($_Cname, self::$files))
            return true;
        
        // Add trace for debugging
        // \Debug::trace('Loading config "'. $_name .'" from: '. $_file, __FILE__, __LINE__);
        
        // Include file and add it to the $files array
        if(!file_exists($_Cfile)) 
            return false;
        include( $_Cfile );
        
        // Set config file flags
        self::$files[$_Cname]['file_path'] = $_Cfile;
        self::$files[$_Cname]['config_key'] = $_Carray;
        self::$files[$_Cname]['allow_set'] = $_CallowSet;
        self::$files[$_Cname]['allow_save'] = $_CallowSave;
        
        // Get defined variables
        $vars = get_defined_vars();
        if($_Carray != false) 
            $vars = $vars[$_Carray];
        else
            // Unset the passes vars
            unset($vars['_Cfile'], $vars['_Cname'], $vars['_Carray'], $vars['_CallowSet'], $vars['_CallowSave']);
        
        // Add the variables to the $data[$name] array
        if(count($vars) > 0)
        {
            foreach( $vars as $key => $val ) 
            {
                if($key != 'this' && $key != 'data') 
                {
                    self::$data[$_Cname][$key] = $val;
                }
            }
        }
        
        return true;
    }
	
/*
| ---------------------------------------------------------------
| Method: UnLoad()
| ---------------------------------------------------------------
|
| This method is used to unload a config
|
| @Param: (String) $name - Name of the container holding the variables
| @Return: (None)
|
*/
    public static function UnLoad($name) 
    {
        unset(self::$data[$name]);
    }

/*
| ---------------------------------------------------------------
| Method: Save()
| ---------------------------------------------------------------
|
| Saves all set config variables to the config file, and makes 
| a backup of the current config file
|
| @Param: (String) $name - Name of the container holding the variables
| @Param: (Bool) $useRegex - If enabled, Regex will be used to set
|   variables. This preserves comments, but is slower
| @Return: (Bool) true on success, false otherwise
|
*/
    public static function Save($name) 
    {
        // Lowercase the $name
        $name = strtolower($name);
        
        // Add trace for debugging
        // \Debug::trace('Saving config: '. $name, __FILE__, __LINE__);
        
        // Check to see if we need to put this in an array
        $ckey = self::$files[$name]['config_key'];
        if($ckey != false)
        {
            $Old_Data = self::$data[$name];
            self::$data[$name] = array("$ckey" => self::$data[$name]);
        }

        // Create our new file content
        $cfg  = "<?php\n";

        // Loop through each var and write it
        foreach( self::$data[$name] as $key => $val )
        {
            switch( gettype($val) )
            {
                case "boolean":
                    $val = ($val == true) ? 'true' : 'false';
                    // donot break
                case "integer":
                case "double":
                case "float":
                    $cfg .= "\$$key = " . $val . ";\n";
                    break;
                case "array":
                    $val = var_export($val, true);
                    $cfg .= "\$$key = " . $val . ";\n";
                    break;
                case "NULL":
                    $cfg .= "\$$key = null;\n";
                    break;
                case "string":
                    $cfg .= (is_numeric($val)) ? "\$$key = " . $val . ";\n" : "\$$key = '" . addslashes( $val ) . "';\n";
                    break;
                default: break;
            }
        }

        // Close the php tag
        $cfg .= "?>";
        
        // Add the back to non array if we did put it in one
        if($ckey != false) self::$data[$name] = $Old_Data;
        
        // Copy the current config file for backup, 
        // and write the new config values to the new config
        copy(self::$files[$name]['file_path'], self::$files[$name]['file_path'].'.bak');
        if(file_put_contents( self::$files[$name]['file_path'], $cfg )) 
        {
            // Add trace for debugging
            // \Debug::trace('Successfully Saved config: '. $name, __FILE__, __LINE__);
            return true;
        } 
        else 
        {
            // Add trace for debugging
            // \Debug::trace('Failed to save config: '. $name, __FILE__, __LINE__);
            return false;
        }
    }
    
/*
| ---------------------------------------------------------------
| Method: Restore()
| ---------------------------------------------------------------
|
| This method is used to undo the last Save. .bak file must be
|   in the config folder
|
| @Param: (String) $name - Name of the container holding the variables
| @Return: (Bool) true on success, false otherwise
|
*/
    public static function Restore($name) 
    {
        // Copy the backup config file nd write the config values to the current config
        return copy(self::$files[$name]['file_path'].'bak', self::$files[$name]['file_path']);
    }
}
// EOF