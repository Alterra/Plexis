<?php
class Admin extends Application\Core\Controller 
{
    public function __construct()
    {
        // Build the Core Controller
        parent::__construct();
        
        // Init a session var
        $this->user = $this->Session->get('user');
        
        // Make sure the user has admin access'
        if( !$this->Auth->has_permission('admin_access') )
        {
            redirect( SITE_URL );
            die();
        }
    }
 
/*
| ---------------------------------------------------------------
| Method: check_access
| ---------------------------------------------------------------
|
| Displays a 403 if the user doesnt have access to this page
| @Param: (Bool) $s403 - Show 403?
|
*/ 
    protected function check_access($perm, $s403 = TRUE)
    {
        if( !$this->Auth->has_permission($perm))
        {
            if($s403) $this->show_403();
            return FALSE;
        }
        return TRUE;
    }
    
/*
| ---------------------------------------------------------------
| 403 Page
| ---------------------------------------------------------------
|
*/ 
    protected function show_403()
    {
        // Set our page title and desc
        $data['page_title'] = "Access Denied";
        $data['page_desc'] = "Your account does not have sufficient rights to view this page.";
        
        // Load the page, and we are done :)
        $this->load->view('blank', $data);
    }

/*
| ---------------------------------------------------------------
| Dashboard
| ---------------------------------------------------------------
|
*/
    public function index() 
    {
        // Get our PHP and DB versions
        $info = $this->DB->server_info();
        $rewrite = (isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On') ? 'On' : 'Off';
        
        // Set our page data
        $data = array(
            'page_title' => "Dashboard",
            'page_desc' => "Here you have a quick overview of some features",
            'driver' => ucfirst( $info['driver'] ),
            'php_version' => phpversion(),
            'mod_rewrite' => $rewrite,
            'database_version' => $info['version'],
            'CMS_VERSION' => CMS_VERSION,
            'CMS_BUILD' => CMS_BUILD,
            'CMS_DB_VERSION' => CMS_DB_VERSION
        );
        
        // Load the page, and we are done :)
        $this->load->view('dashboard', $data);
    }

/*
| ---------------------------------------------------------------
| PHPinfo Page
| ---------------------------------------------------------------
|
*/ 
    public function phpinfo($plain = FALSE) 
    {
        if($plain == 'html')
        {
            echo phpinfo();
        }
        else
        {
            // Set our page title and desc
            $data['page_title'] = "Php Info";
            $data['page_desc'] = "You are viewing this servers phpinfo";
            
            // Load the page, and we are done :)
            $this->load->view('phpinfo', $data);
        }
    }

/*
| ---------------------------------------------------------------
| News Managment Page
| ---------------------------------------------------------------
|
*/ 
    public function news($action = NULL, $id = NULL)
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_news')) return;

        // Load the News Model
        $this->load->model('News_Model');
  
        // No action? then load index screen
        if($action == NULL)
        {
            // Build our page variable data
            $data = array(
                'page_title' => "Manage News",
                'page_desc' => "From here, you can Edit, Delete, or create a new news post."
            );
            
            // Load the view
            $this->load->view('news_index', $data);
        }
        else
        {
            // Get our post
            $post = $this->News_Model->get_news_post($id);
            $post['body'] = stripslashes($post['body']);
            
            // Build our page variable data
            $data = array(
                'page_title' => "Edit News",
                'page_desc' => "Editing news post.",
                'id' => $post['id'],
                'title' => $post['title'],
                'body' => $post['body']
            );
            
            // Load the view
            $this->load->view('news_edit', $data); 
        }
    }

/*
| ---------------------------------------------------------------
| Manage Users
| ---------------------------------------------------------------
|
*/    
    public function users($username = NULL)
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_users')) return;

        // No Username, Build the index page
        if($username == NULL)
        {
            // Build our page title / desc, then load the view
            $data = array(
                'page_title' => "Manage Users",
                'page_desc' => "Here you can manage the account of all your users."
            );
            $this->load->view('users_index', $data);
        }
        
        // We have a username, Load the user
        else
        {
            // Get users information. We can use GET because the queries second param will be cleaned
            // by the PDO class when bound to the "?".
            $query = "SELECT * FROM `pcms_accounts` INNER JOIN `pcms_account_groups` ON 
                pcms_accounts.group_id = pcms_account_groups.group_id WHERE `username` = ?";
            $user = $this->DB->query( $query, array($username) )->fetch_row();
            
            // If $user isnt an array, we failed to load the user
            if(!is_array($user))
            {
                // Load the page, and we are done :)
                output_message('error', 'user_not_found_1');
                
                // Build our page title / desc, then load the view
                $data = array(
                    'page_title' => "Loading",
                    'page_desc' => "Please wait while we redirect you..."
                );
                redirect('admin/users', 5);
                $this->load->view('redirect', $data);
            }
            else
            {
                // Make sure we have our realm loaded
                (!isset($this->realm)) ? $this->load->realm() : '';
				
                // Use the realm database to grab user information first
                $user2 = $this->realm->fetch_account($user['id']);
				$data['expansion_data'] = $this->realm->get_expansion_info();
                
                // Use the additional inforamation from the realm DB
                if($user2 !== FALSE)
                {
                    // Determine out Account status
                    $status = $this->realm->account_banned($user['id']);
                    if($status == FALSE)
                    {
                        // Set ban status to Ban
                        $data['account_ban_button'] = "ban";
                        $data['account_ban_button_text'] = "Ban Account";
                        
                        // Load lock status
                        if($user2['locked'] == FALSE)
                        {
                            $user['status'] = 'Active';
                            $data['account_lock_button'] = "lock";
                            $data['account_lock_button_text'] = "Lock Account";
                        }
                        else
                        {
                            $user['status'] = 'Locked';
                            $data['account_lock_button'] = "unlock";
                            $data['account_lock_button_text'] = "UnLock Account";
                        }
                    }
                    else
                    {
                        $user['status'] = 'Banned';
                        $data['account_ban_button'] = "unban";
                        $data['account_ban_button_text'] = "UnBan Account";
                        $data['account_lock_button'] = "lock";
                        $data['account_lock_button_text'] = "Lock Account";
                    }
                    $user = array_merge($user2, $user);
                    
                    // Finish Building our data array
                    $data['page_title'] = ucfirst( strtolower($username) )." (Account ID: ".$user['id'].")";
                    $data['page_desc'] = "Here you can manage the account of all your users.";
                    $data['user'] = $user;
                    $data['groups'] = $this->DB->query("SELECT * FROM `pcms_account_groups`")->fetch_array();
                    
                    // Load the view
                    $this->load->view('user_manage', $data);
                }
                else
                {
                    // Load the page, and we are done :)
                    output_message('error', 'user_not_found_2');
                    
                    // Build our page title / desc, then load the view
                    $data = array('page_title' => "", 'page_desc' => "");
                    
                    // Load the error page, no redirect
                    $this->load->view('redirect', $data);
                    return;
                }
            }
        }
    }
    
/*
| ---------------------------------------------------------------
| Site Settings
| ---------------------------------------------------------------
|
*/    
    public function settings()
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_site_config')) return;

        // Load our config class
        $Config = load_class('Config');
        
        // Use admin model to process and make our "select option" fields
        $this->load->model('Admin_model', 'model');
        $options = $this->model->site_settings_options();
        
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Site Settings",
            'page_desc' => "Here you can manage the account of all your users.",
            'config' => $Config->get_all('app'),
            'options' => $options
        );
        $this->load->view('site_settings', $data);
    }

/*
| ---------------------------------------------------------------
| User Groups and Permissions
| ---------------------------------------------------------------
|
*/     
    public function groups($sub1 = NULL, $id = NULL)
    {
        // Load our config & Input class
        $Config = load_class('Config');
        $Input = load_class('Input');

        // Make sure user is super admin for ajax
        if($this->user['is_super_admin'] != 1)
        {
            $this->show_403();
            return;
        }
        
        if($sub1 != NULL && $id != NULL)
        {
            // Clean the ID
            $id = $Input->clean($id);
            switch($sub1)
            {
                case "permissions":
                    
                    // Load the perms for this group
                    $query = "SELECT * FROM `pcms_account_groups` WHERE `group_id`=?";
                    $group = $this->DB->query( $query, array($id) )->fetch_row();
                    $perms = unserialize($group['permissions']);
                    unset($group['permissions']); 
                    if($perms == FALSE) $perms = array();
                    
                    // Get all permissions
                    $query = "SELECT * FROM `pcms_permissions`";
                    $array = $this->DB->query( $query, array($id) )->fetch_array();
                    $changed = FALSE;
                    $list = array();
                    foreach($array as $key => $p)
                    {
                        if( !isset($perms[$p['key']]) )
                        {
                            $changed = TRUE;
                            $perms[$p['key']] = 0;
                        }
                        $list[$p['key']] = $p;
                    }
                    unset($array);
                    
                    // For ordering purposes
                    $permissions = array('admin' => array(), 'core' => array());
                    $sections = array('admin', 'core');
                    foreach($perms as $key => $p)
                    {
                        if(!isset($list[$key]))
                        {
                            $changed = TRUE;
                            unset($perms[$key]); 
                            continue;
                        }
                        $g = $list[$key]['module'];
                        $permissions[$g][$key] = $p;
                        if(!in_array($g, $sections)) $sections[] = $g;
                    }
                    
                    // Update as need be
                    if($changed == TRUE)
                    {
                        // Only insert values of 1
                        $update = array();
                        foreach($perms as $key => $value)
                        {
                            if($value == 1) $update[$key] = $value;
                        }
                        $i['permissions'] = serialize($update);
                        $this->DB->update('pcms_account_groups', $i, "`group_id`=$id");
                    }
                    
                    // Build our page title / desc, then load the view
                    $data = array(
                        'page_title' => "Group Permissions",
                        'page_desc' => "Editting Permissions",
                        'group' => $group,
                        'permissions' => $permissions,
                        'list' => $list,
                        'sections' => $sections
                    );
                    $this->load->view('group_permissions', $data);
                break;
            }
            return;
        }

        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "User Groups & Permissions",
            'page_desc' => "On this page, you can Create / Delete user groups and ajust site permission on a group basis."
        );
        $this->load->view('groups', $data);
    }

/*
| ---------------------------------------------------------------
| Registration Settigns
| ---------------------------------------------------------------
|
*/    
    public function registration()
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_site_config')) return;

        // Load our config class
        $Config = load_class('Config');
        
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Registration Settings",
            'page_desc' => "Here you can set the Registration requirements and settings for new accounts. You are also able to generate Invite keys here.",
            'config' => $Config->get_all('App')
        );
        $this->load->view('registration', $data);
    }

/*
| ---------------------------------------------------------------
| Realm Managment
| ---------------------------------------------------------------
|
*/    
    public function realms($subpage = 'index', $id = NULL)
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_realms')) return;

        // Process our page
        switch($subpage)
        {
            case "index":
                // Build our page title / desc, then load the view
                $data = array(
                    'page_title' => "Realm Managment",
                    'page_desc' => "Here you can Manage your realms, setup Remote Access, and send console commands to your server.",
                );
                $this->load->view('realms_index', $data);
                break;
            
            // EDITING
            case "edit":
                // Make sure we have an id!
                if($id === NULL || !is_numeric($id)) redirect('admin/realms');

                // Load installed drivers
                $drivers = get_wowlib_drivers();
                if($drivers == FALSE) $drivers = array();
                
                // Load our installed realm info
                $realm = $this->DB->query("SELECT * FROM `pcms_realms` WHERE `id`=?", array($id))->fetch_row();

                // Redirect if this realm doesnt exist / isnt installed
                if($realm == FALSE) redirect('admin/realms');
                
                // Unserialize our DB realms connection information
                $realm['cdb'] = unserialize($realm['char_db']);
                $realm['wdb'] = unserialize($realm['world_db']);
                $realm['ra'] = unserialize($realm['ra_info']);
                
                // Build our page title / desc, then load the view
                $data = array(
                    'page_title' => "Edit Realm",
                    'page_desc' => "Here you can change the DB settings for your realm, as well as the driver.",
                    'realm' => $realm,
                    'drivers' => $drivers
                );
                $this->load->view('realms_edit', $data);
                break;
            
            // INSTALL
            case "install":
                // Load installed drivers
                $drivers = get_wowlib_drivers();
                if($drivers == FALSE) $drivers = array();
                
                // Build our page title / desc
                $data = array(
                    'page_title' => "Realm Installation",
                    'page_desc' => "On this page you will be able to install a new realm for use on the site. Installing a realm allows you as well as users to 
                        see statistics about the realm, view online characters, and user character tools such as Character Rename.",
                    'drivers' => $drivers
                );
                
                // check for an existing install
                if($id != NULL)
                {
                    // Make sure the realm isnt already installed
                    $installed = get_installed_realms();
                    $irealms = array();
                    
                    // Build an array of installed IDs
                    foreach($installed as $realm)
                    {
                        $irealms[] = $realm['id'];
                    }
                    if(in_array($id, $irealms)) redirect('admin/realms/edit/'.$id);
                    
                    // Get realm information
                    $realm = $this->realm->fetch_realm($id);
                    
                    // Load the view
                    $data = $data + array('realm' => $realm);
                    $this->load->view('realms_install', $data);
                }
                else
                {
                    $this->load->view('realms_install_manual', $data);
                }
                break;
                
            default:
                redirect('admin/realms');
                break;
        }
    }
    
/*
| ---------------------------------------------------------------
| Vote
| ---------------------------------------------------------------
|
*/
    public function vote($action = NULL, $id = NULL)
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_votesites')) return;

        // Load the News Model
        $this->load->model('Vote_Model', 'model');
  
        // No action? then load index screen
        if($action == NULL)
        {
            // Build our page variable data
            $data = array(
                'page_title' => "Manage Vote Sites",
                'page_desc' => "Create, Edit, or Delete vote sites that your users will use to vote for your server."
            );
            
            // Load the view
            $this->load->view('vote_index', $data);
        }
        else
        {
            
            // Build our page variable data
            $data = $this->model->get_vote_site($id);
            if($data == FALSE) redirect('admin/vote');
            
            // Add page Title and Desc
            $data['page_title'] = "Edit Vote Site";
            $data['page_desc'] = "Editing vote site.";

            // Load the view
            $this->load->view('vote_edit', $data); 
        }
    }

/*
| ---------------------------------------------------------------
| Modules
| ---------------------------------------------------------------
|
*/    
    public function modules()
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_modules')) return;

        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Module Managment",
            'page_desc' => "On this page, you can install and manage your installed modules. You may also edit module config files here.",
        );
        $this->load->view('module_index', $data);
    }

/*
| ---------------------------------------------------------------
| Templates
| ---------------------------------------------------------------
|
*/     
    public function templates()
    {
        // Make sure the user can view this page
        if( !$this->check_access('manage_templates')) return;

        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Template Manager",
            'page_desc' => "This page allows you to manage your templates, which includes uploading, installation, and un-installation.",
        );
        
        // Get installed templates
        $query = "SELECT * FROM `pcms_templates` WHERE `type`='site'";
        $templates = $this->DB->query( $query )->fetch_array();
        foreach($templates as $t)
        {
            $aa[] = $t['name'];
        }
        
        // Scan and get a list of all templates
        $list = scandir(APP_PATH . DS . 'templates');
        foreach($list as $file)
        {
            if($file[0] == "." || $file == "index.html") continue;
            if(!in_array($file, $aa))
            {
                $xml = APP_PATH . DS . 'templates' . DS . $file . DS .'template.xml';
                if(file_exists($xml))
                {
                    $xml = simplexml_load_file($xml);
                    $insert = array(
                        'name' => $file,
                        'type' => 'site',
                        'author' => $xml->info->author,
                        'status' => 0
                    );
                    $this->DB->insert('pcms_templates', $insert);
                }
            }
        }
        
        $this->load->view('templates', $data);
    }
    
/*
| ---------------------------------------------------------------
| Console
| ---------------------------------------------------------------
|
*/    
    public function console()
    {
        // Make sure the user can view this page
        if( !$this->check_access('send_console_commands')) return;

        $realms = get_installed_realms();
        $selector = "<select id=\"realm\" name=\"realm\">\n";
        if( !empty($realms) )
        {
            foreach($realms as $realm)
            {
                $selector .= "\t<option value='". $realm['id'] ."'>". $realm['name'] ."</option>\n";
            }
        }
        else
        {
            $selector .= "\t<option value='0'>No Realms Installed</option>\n";
        }
        $selector .= "</select>\n";

        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Console",
            'page_desc' => "This page allows you to use a PHP / Ajax Command line console in which you can send commands to your server, or realms.",
            'realm_selector' => $selector
        );
        $this->load->view('console', $data);
    }
 
/*
| ---------------------------------------------------------------
| UNFINISHED PAGES
| ---------------------------------------------------------------
|
*/
    
    function characters()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Character Editor",
            'page_desc' => "This page allows you to edit character information, and preform actions such as transfering to another realm.",
        );
        $this->load->view('under_construction', $data);
    }
    
    function shop()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Shop Managment",
            'page_desc' => "This page llows you to manage your shop settings and packages for players to spend thier Web Points on.",
        );
        $this->load->view('under_construction', $data);
    }
    
    function donate()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Donation Managment",
            'page_desc' => "This page allows you to see all the donations your server has earned, as well as create and edit donation packages for users to buy.",
        );
        $this->load->view('under_construction', $data);
    }
    
    function statistics()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Statistics",
            'page_desc' => "This page allows you to see various statistics about page views and user accounts.",
        );
        $this->load->view('under_construction', $data);
    }
    
    function support()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Support Settings",
            'page_desc' => "Here you can manage the support page as well as the FAQ's",
        );
        $this->load->view('under_construction', $data);
    }
    
    function update()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "Remote Updater",
            'page_desc' => "This script allows you to update your CMS with just a click of a button.",
        );
        $this->load->view('update', $data);
    }
    
    function logs()
    {
        // Build our page title / desc, then load the view
        $data = array(
            'page_title' => "View Logs",
            'page_desc' => "Here you can view debug and error logs generated by the cms.",
        );
        $this->load->view('under_construction', $data);
    }
}
// EOF