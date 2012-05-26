<?php
/* 
| --------------------------------------------------------------
| 
| Frostbite Framework
|
| --------------------------------------------------------------
|
| Author:       Steven Wilson
| Copyright:    Copyright (c) 2011, Steven Wilson
| License:      GNU GPL v3
|
| ---------------------------------------------------------------
| Class: Language
| ---------------------------------------------------------------
|
| This class is used to load language files and return lang vars.
| 
*/
namespace Application\Core;

class Language
{
    // Array of ur language variables
    protected $language_vars = array();

    // An array of loaded language files
    protected $loaded_files = array();
    
    // Array of found languages
    protected $found_languages = array();
    
    // Our default language
    protected $default_language;
    
    // Our file system class
    protected $filesystem;
    
    // Our selected language
    public $language = null;

/*
| ---------------------------------------------------------------
| Constructer
| ---------------------------------------------------------------
*/
    public function __construct()
    {
        // Load the input and filesystem class
        $this->Input = load_class('Input');
        $this->filesystem = load_class('Filesystem', 'Library');
        
        // Load our languages
        $this->scan_language_dirs();
        
        // Set the default language
        $this->default_language = config('default_language');

        // Set the default Language
        $this->selected_language();
    }
    
/*
| ---------------------------------------------------------------
| Method: selected_language()
| ---------------------------------------------------------------
|
| Returns the users selected language, or figures it our manually
| if not already set.
|
| @Return (String) Language name
|
*/
    public function selected_language() 
    {
        // return the selected language if its set already
        if(!empty($this->language)) return $this->language;
   
        // Load language cookie
        $this->language = $this->Input->cookie('language', true);
        
        // Get the users prefered language
        $prefered = null;
        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $prefered = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
        
        //Load the default language if the user hasnt selected a language yet
        if($this->language == false)
        {
            // Check and make sure the language is installed
            $this->language = (!in_array($prefered, $this->found_languages)) ? $this->default_language() : $prefered;
            
            // Update the language cookie
            $this->Input->set_cookie('language', $this->language);
        }
        else
        {
            // Check and make sure the language is installed
            if(!in_array($this->language, $this->found_languages))
            {
                // See id the users prefered language is installed
                $this->language = (!in_array($prefered, $this->found_languages)) ? $this->default_language() : $prefered;
                
                // Update the language cookie
                $this->Input->set_cookie('language', $this->language);
            }
        }

        // Set globals
        return $this->language;
    }

/*
| ---------------------------------------------------------------
| method: set_language()
| ---------------------------------------------------------------
|
| Sets the langauge. Does not reload already loaded files
|
| @Param: (String) $lang - Name of the language we are loading
| @Return (None)
|
*/
    public function set_language($lang)
    {
        // Check if the language exists
        $lang = strtolower($lang);
        if(in_array($lang, $this->found_languages))
        {
            $this->language = $lang;
            return TRUE;
        }

        // If we are here, then langauge doesnt exist! set whatever we can
        return FALSE;
    }
    
/*
| ---------------------------------------------------------------
| Method: default_language()
| ---------------------------------------------------------------
|
| Return the default language, after making sure it exists
|
| @Return (String) Language name
|
*/
    public function default_language($type = 'application')
    {
        // Check if the language exists
        return (in_array($this->default_language, $this->found_languages)) ? $this->default_language : 'en';
    }
    
/*
| ---------------------------------------------------------------
| method: language_exists()
| ---------------------------------------------------------------
|
| Returns if the language exists or not
|
| @Return (Bool)
|
*/
    public function exists($lang)
    {
        // Return if the language exists
        $lang = strtolower($lang);
        return (in_array($lang, $this->found_languages)) ? true : false;
    }

/*
| ---------------------------------------------------------------
| Method: load()
| ---------------------------------------------------------------
|
| Loads the lanugage file
|
| @Param: (String) $file - Name of the language file, without the extension
| @Param: (Bool) $return - Set to TRUE to return the $lang array, FALSE
|       to just save the variables here.
| @Return (Mixed) Depends on the $return variable
|
*/
    public function load($file)
    {
        // Set the language if specified
        $lang = $this->language;
        
        // Add the extension, and create our tag
        $key = $file .'_'. $lang;
        $file_ext = $file . '.php';

        // Make sure we havent loaded this already
        if(isset($this->language_vars[$key]))
        {
            return $this->language_vars[$key];
        }
        
        // Init our empty variable arrays
        $vars = array();

        // Next we load the application file, allows overriding of the core one
        if(file_exists(APP_PATH . DS .'language' . DS . $lang . DS . $file_ext))
        {
            $vars = include(APP_PATH . DS .'language' . DS . $lang . DS . $file_ext);
            if(!is_array($vars)) return FALSE;
        }

        // Without a return, we need to store what we have here.
        $this->loaded_files[] = $file;
        $this->language_vars[$key] = $vars;

        // Init the return
        return ( !empty($vars) ) ? $vars : FALSE;
    }

/*
| ---------------------------------------------------------------
| Method: get()
| ---------------------------------------------------------------
|
| Returns the variable from the config array
|
| @Param: (String) $var - the key of the lang array value
| @Param: (String) $file - The filename the var belongs in (no Ext)
| @Return (Mixed) FALSE if the var is unset, or the string otherwise
|
*/
    public function get($var, $file = null)
    {
        // Load the filename if we need
        if($file != null && !in_array($file, $this->loaded_files))
        {
            $this->load($file);
        }
        
        // Make sure we have variables to return at all
        if(empty( $this->language_vars ))
        {
            return FALSE;
        }
        
        // Determine our language variable filename if not givin
        if($file == null) $file = end( $this->loaded_files );

        // Build out lang var key
        $key = $file .'_'. $this->language;
        
        // check to see if our var is set... if not, try to load it first
        if( !isset($this->language_vars[$key]) ) $this->load($file);

        // Attempt to load the actual language var now
        if(isset($this->language_vars[$key][$var]))
        {
            return $this->language_vars[$key][$var];
        }
        
        // We tried everything :(
        return FALSE;
    }

/*
| ---------------------------------------------------------------
| Method: get_languages()
| ---------------------------------------------------------------
|
| Returns an array of found langauges in the language folders
|
| @Param: (String) $type - system, or application? NULL for both
| @Return (Array) An array of found languages
|
*/    
    public function get_languages()
    {
        return $this->found_languages;
    }

/*
| ---------------------------------------------------------------
| Method: scan_language_dirs()
| ---------------------------------------------------------------
|
| Scans and finds all installed languages
|
*/
    protected function scan_language_dirs()
    {
        // Finally, Load app languages
        $path = APP_PATH . DS . 'language';
        $this->found_languages = $this->filesystem->list_folders($path);
    }
}
// EOF