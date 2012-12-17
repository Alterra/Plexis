<?php
// Bring some classes into scope So we dont have to specify namespaces with each class
use Core\Controller;
use Library\Template;
use Library\View;
use Library\ViewNotFoundException;

class Frontpage extends Controller
{
    public function __construct()
    {
        // Construct the parent controller, providing our class file path
        parent::__construct(__FILE__);
    }
    
    public function Index()
    {
        // Load our view using the Core\Controller::loadView method
        try {
            // First load our page view
            $view = $this->loadView("main");
            $view->Set('message', 'Module path: '. $this->modulePath .'<br />Module HTTP URI: '. $this->moduleUri);
            
            // Load a content box next, placing our module view as the contents of the box
            $box = $this->loadPartialView("contentbox");
            $box->Set('title', 'Test Title');
            $box->Set('contents', $view->Render());
            
            // Add the finished content box to the template
            Template::Add($box);
        }
        catch( ViewNotFoundException $e ) {
            echo "<br /><br />". $e->getMessage();
        }
    }
}