<?php
/* 
| --------------------------------------------------------------
| Plexis
| --------------------------------------------------------------
| Author:       Steven Wilson 
| Author:       Tony (Syke)
| Copyright:    Copyright (c) 2011-2012, Plexis
| License:      GNU GPL v3
|
*/
namespace Core;

class Plexis
{
    protected $Router;
    protected $load;
    protected $DB;
    protected $dispatch;
    protected $controller;
    protected $action;
    protected $queryString;

/*
| ---------------------------------------------------------------
| Method: Init()
| ---------------------------------------------------------------
|
| This is the function that runs the whole show!
|
| @Return: (None)
|
*/
    public function Init()
    {
        // Add trace for debugging
        \Debug::trace('Initializing Plexis bootstrap class...', __FILE__, __LINE__);
        
        // Initialize the router
        $this->Router = load_class('Router');
        $this->load = load_class('Loader');
        $this->EventHandler = load_class('Events');
        
        // Tell the router to process the URL for us
        $routes = $this->Router->get_url_info();
        
        // Initialize some important routing variables
        $this->controller  = $routes['controller'];
        $this->action      = $routes['action'];
        $this->queryString = $GLOBALS['querystring'] = $routes['querystring'];
        
        // Define our Base url
        define('BASE_URL', $routes['site_url']);
        
        // Determine if HTTP_MOD_REWRITE is enabled, and Define our site url
        if( !array_key_exists('HTTP_MOD_REWRITE', $_SERVER) ) $_SERVER['HTTP_MOD_REWRITE'] = 'Off';
        ($_SERVER['HTTP_MOD_REWRITE'] == 'On') ? define('SITE_URL', $routes['site_url']) : define('SITE_URL', $routes['site_url'] .'/?url=');
        
        // Add trace for debugging
        \Debug::trace('Loading plugins...', __FILE__, __LINE__);
        
        // Load Plugins config file
        $i = 0;
        include( SYSTEM_PATH . DS .'config'. DS .'plugins.php' );
        foreach($Plugins as $p)
        {
            ++$i;
            $this->load->plugin($p);
        }
        
        // Add trace for debugging
        \Debug::trace("Successfully loaded {$i} plugins", __FILE__, __LINE__);

        // Load the application controller, and determine if this request is to a module		
        if( !$this->findRoute() ) show_404();
        
        // Trigger the Pre controller Event
        $this->EventHandler->trigger('pre_controller');
        
        // Here we init the actual controller / action into a variable.
        $this->dispatch = new $this->controller();
        
        // Trigger the Post controller constructEvent
        $this->EventHandler->trigger('post_controller_init');
        
        // After loading the controller, make sure the method exists, or we have a 404
        if(method_exists($this->controller, $this->action)) 
        {
            // Call the beforeAction method in the controller.
            $this->performAction('_beforeAction');
            
            // HERE is where the magic begins... call the Main APP Controller and method
            $this->performAction($this->action);
            
            // Call the afterAction method in the controller.
            $this->performAction('_afterAction');
        } 
        else
        {
            // If the method didnt exist, then we have a 404
            show_404();
        }
        
        // Trigger the Post controller Event
        $this->EventHandler->trigger('post_controller');
    }

/*
| ---------------------------------------------------------------
| Method: performAction()
| ---------------------------------------------------------------
|
| @Param: (String) $action - Action method being used in the controller
| @Param: (String) $queryString - The query string, basically params for the Action
| @Return: (Object) - Returns the method
|
*/

    protected function performAction($action, $queryString = null) 
    {
        return call_user_func_array(array($this->dispatch, $action), $this->queryString);
    }

/*
| ---------------------------------------------------------------
| Method: findRoute()
| ---------------------------------------------------------------
|
| This method processes module URL overriding. If a module has
| overwritten this URL, it will get loaded rather then the default
| controller / action
|
| @Return: (Bool) - Returns FALSE if the route cant be determined
|
*/
    protected function findRoute()
    {
        // Make this a bit easier
        $name = strtolower($this->controller);
        
        // Add trace for debugging
        \Debug::trace("Loading routes for url...", __FILE__, __LINE__);
        
        // Load database
        $this->DB = $this->load->database('DB');
        
        // Build our array to get out current URI's module if one exists
        $uri1 = $name .'/*';
        $uri2 = $name .'/'. $this->action;
        
        // Check to see if the URI belongs to a module
        $query = "SELECT * FROM `pcms_modules` WHERE `uri`=? OR `uri`=?";
        $result = $this->DB->query( $query, array($uri1, $uri2) )->fetch_row();
        
        // If our result is an array, Then we load it as a module
        if(is_array($result))
        {
            // Handle the method, if method is an astricks, then the module will handle all requests
            if($result['method'] == '*')
            {
                $result['method'] = $this->action;
            }
            
            // If the method is an array (imploded with a comma), we see if the action is in the array
            elseif(strpos($result['method'], ',') !== FALSE)
            {
                // Remove any spaces, and convert to an array
                $array = str_replace(' ', '', explode(',', $result['method']));
                if(!in_array($this->action, $array))
                {
                    // The action IS NOT in the array, load default controller
                    goto Skip;
                }
                else
                {
                    // The action is in the array, so we set it as that
                    $result['method'] = $this->action;
                }
            }

            // Define out globals and this controller/action
            $GLOBALS['is_module'] = TRUE;
            $this->controller  = $GLOBALS['controller'] = ucfirst($result['name']);
            $this->action = $GLOBALS['action'] = $result['method'];
            
            // Add trace for debugging
            \Debug::trace("Found module route in database. Using {$this->controller} as controller, and {$this->action} as method", __FILE__, __LINE__);
            
            // Include the module controller file
            include path(ROOT, 'third_party', 'modules', $this->controller, 'controller.php');
            return TRUE;
        }
        
        // Check the App controllers folder
        else
        {
            Skip:
            {
                $path = path(SYSTEM_PATH, 'controllers', $name .'.php');
                if(file_exists($path))
                {
                    // Define out globals and this controller/action
                    $GLOBALS['is_module'] = FALSE;
                    $GLOBALS['controller'] = $this->controller;
                    $GLOBALS['action'] = $this->action;
                    
                    // Add trace for debugging
                    \Debug::trace("No module found for url route, using default contoller from the system/controllers folder", __FILE__, __LINE__);
                    
                    // Include the controller file
                    include $path;
                    return TRUE;
                }
            }
        }
        
        // Neither exists, then no controller found.
        return FALSE;
    }
}
// EOF