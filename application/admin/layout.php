<?php
    // First we setup the menu ID to false
    $a = $b = $c = $d = $e = $f = $g = FALSE;
    
    // Determine our action to highlight our navigation pane
    switch($GLOBALS['action'])
    {
        case "index":
            $a = TRUE;
            break;
        case "users":
            $b = TRUE;
            break;
        case "settings":
        case "registration":
        case "language":
            $c = TRUE;
            break;
        case "news":
        case "groups":
        case "modules":
        case "templates":
        case "vote":
        case "donate":
        case "shop":
        case "support":
            $d = TRUE;
            break;
        case "realms":
            $e = TRUE;
            break;
        case "characters":
            $f = TRUE;
            break;
        case "console":
        case "update":
        case "logs":
        case "statistics":
            $g = TRUE;
            break;
    }
?>
<!DOCTYPE html>
<head>
    <pcms::head />
</head>
<body id="top">

    <!-- Begin of #container -->
    <div id="container">
    
        <!-- Begin of #header -->
        <div id="header-surround">
            <header id="header">
            
                <!-- Logo -->
                <img src="{TEMPLATE_URL}/img/logo.png" alt="Plexis" class="logo">
                
                <!-- Divider between info-button and the toolbar-icons -->
                <div class="divider-header divider-vertical"></div>
                
                <!-- Info-Button -->
                <a href="javascript:void(0);" onclick="$('#info-dialog').dialog({ modal: true, width: 500 });"><span class="btn-info"></span></a>
                <div id="info-dialog" title="About" style="display: none;">
                    <p>Plexis is a powerful CMS / Server administration tool for WoW Private Servers. </p>
                    <p>Page loaded in {ELAPSED_TIME} seconds, Using {MEMORY_USAGE}</p>
                    <p>Plexis &copy; 2011, ArchDev Team</p>
                </div>
                
                <!-- Begin of #user-info -->
                <div id="user-info">
                    <p>
                        <span class="messages">Hello <a href="javascript:void(0);"><?php echo ucfirst( strtolower('{session.user.username}')); ?></a></span>
                        <a href="{SITE_URL}" class="button">Return To Site</a> <a href="{SITE_URL}/account/logout" class="button red">Logout</a>
                    </p>
                </div> 
                <!--! end of #user-info -->
            
            </header>
        </div> 
        <!--! end of #header -->
        
        <div class="fix-shadow-bottom-height"></div>
        
        <!-- Begin of Sidebar -->
        <aside id="sidebar">
            
            <!-- Search -->
            <div id="search-bar">
                <form id="search-form" name="search-form" action="search.php" method="post">
                    <input type="text" id="query" name="query" value="" autocomplete="off" placeholder="Search">
                </form>
            </div> 
            <!--! end of #search-bar -->
            
            <!-- Begin of #login-details -->
            <section id="login-details">
                <img class="img-left framed" src="{TEMPLATE_URL}/img/misc/avatar_small.png" alt="Hello Admin">
                <h3>Logged in as</h3>
                <h2><a class="user-button" href="javascript:void(0);"><?php echo ucfirst( strtolower('{session.user.username}')); ?>&nbsp;<span class="arrow-link-down"></span></a></h2>
                <ul class="dropdown-username-menu">
                    <li><a href="{SITE_URL}/account">Manage Account</a></li>
                    <li><a href="{SITE_URL}/account/logout">Logout</a></li>
                </ul>
                
                <div class="clearfix"></div>
            </section> 
            <!--! end of #login-details -->
            
            <!-- Begin of Navigation -->
            <nav id="nav">
                <ul class="menu collapsible shadow-bottom">
                    <li>
                        <a href="{SITE_URL}/admin" <?php if($a == TRUE) echo 'class="current"';?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/dashboard.png">Dashboard</a>
                    </li>
                    <li>
                        <a href="{SITE_URL}/admin/users" <?php if($b == TRUE) echo 'class="current"'; ?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/user.png">Manage Users</a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" <?php if($c == TRUE) echo 'class="current"'; ?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/config.png">Configuration</a>
                        <ul class="sub">
                            <li><a href="{SITE_URL}/admin/settings">Site Settings</a></li>
                            <li><a href="{SITE_URL}/admin/registration">Registration Settings</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);" <?php if($d == TRUE) echo 'class="current"'; ?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/clipboard-list.png">Site Managment</a>
                        <ul class="sub">
                            <li><a href="{SITE_URL}/admin/news">News Posts</a></li>
                            <li><a href="{SITE_URL}/admin/groups">User Groups</a></li>
                            <li><a href="{SITE_URL}/admin/modules">Modules</a></li>
                            <li><a href="{SITE_URL}/admin/templates">Templates</a></li>
                            <li><a href="{SITE_URL}/admin/vote">Vote Sites</a></li>
                            <li><a href="{SITE_URL}/admin/donate">Donation Packages</a></li>
                            <li><a href="{SITE_URL}/admin/shop">Shop System Items</a></li>
                            <li><a href="{SITE_URL}/admin/support">Site Support</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="{SITE_URL}/admin/realms" <?php if($e == TRUE) echo 'class="current"'; ?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/chart.png">Realm Managment</a>
                    </li>
                    <li>
                        <a href="{SITE_URL}/admin/characters" <?php if($f == TRUE) echo 'class="current"'; ?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/pencil.png">Character Editor</a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" <?php if($g == TRUE) echo 'class="current"'; ?>>
                        <img src="{TEMPLATE_URL}/img/icons/small/system.png">System</a>
                        <ul class="sub">
                            <li><a href="{SITE_URL}/admin/statistics">View Site Statistics</a></li>
                            <li><a href="{SITE_URL}/admin/console">Console</a></li>
                            <li><a href="{SITE_URL}/admin/update">Check For Updates</a></li>
                            <li><a href="{SITE_URL}/admin/logs">View Logs</a></li>
                        </ul>
                    </li>
                </ul>
            </nav> 
        </aside> 
        <!--! end of #sidebar -->
        
        <!-- Begin of #main -->
        <div id="main" role="main">
            
            <!-- Begin of titlebar/breadcrumbs -->
            <div id="title-bar">
                <ul id="breadcrumbs">
                    <li><a href="{SITE_URL}/admin" title="Home"><span id="bc-home"></span></a></li>
                    <?php
                        // Instead of making a custom one in every page, we make an automatic one here
                        if($GLOBALS['action'] == 'index'): 
                            echo '<li class="no-hover">Dashboard</li>';
                        elseif($GLOBALS['action'] !== 'index' && isset($GLOBALS['queryString'][0])): 
                            echo '<li><a href="{SITE_URL}/'.$GLOBALS['controller'].'/'.$GLOBALS['action'].'">'.ucfirst($GLOBALS['action']).'</a></li>';
                            echo '<li class="no-hover">'.ucfirst($GLOBALS['queryString'][0]).'</li>';
                        else:
                            echo '<li class="no-hover">'.ucfirst($GLOBALS['action']).'</li>';
                        endif;
                    ?>
                </ul>
            </div> 
            <!--! end of #title-bar -->
            
            <div class="shadow-bottom shadow-titlebar"></div>
            
            <!-- Begin of #main-content -->
            <div id="main-content">
                <div class="container_12">
                     <!-- Page Description -->
                    <div class="grid_12">
                        <h1>{page_title}</h1>
                        <p>{page_desc}</p>
                    </div>
                    
                    <!-- Global Messages -->
                    <div class="grid_12">
                        <pcms::global_messages />
                    </div>
                
                    <!-- Contents -->
                    <pcms::page_contents />
                </div> 
                <div class="clear height-fix"></div>
            </div>
            <!--! end of #main-content -->
            
        </div> 
        <!--! end of #main -->

        <!-- Footer -->
        <footer id="footer">
            <div class="container_12">
                <div class="grid_12">
                    <div class="footer-icon align-center"><a class="top" href="#top"></a></div>
                </div>
            </div>
        </footer>

    </div> 
    <!--! end of #container -->
    
    <!-- JavaScript at the bottom for fast page loading -->
    <!-- scripts concatenated and minified via ant build script-->
    <script src="{TEMPLATE_URL}/js/plugins.js"></script> <!-- lightweight wrapper for consolelog, optional -->
    <script src="{TEMPLATE_URL}/js/mylibs/jquery-ui-1.8.15.custom.min.js"></script> <!-- jQuery UI -->
    <script src="{TEMPLATE_URL}/js/mylibs/jquery.uniform.min.js"></script> <!-- Uniform (Look & Feel from forms) -->
    <script src="{TEMPLATE_URL}/js/mylibs/jquery.form.js"></script> <!-- Forms & Ajax submission -->
    <script src="{TEMPLATE_URL}/js/mylibs/jquery.tipsy.js"></script> <!-- Tooltips -->
    <script src="{TEMPLATE_URL}/js/tiny_mce/jquery.tinymce.js"></script><!-- Load TinyMCE -->
    <script src="{TEMPLATE_URL}/js/common.js"></script> <!-- Generic functions -->
    <script src="{TEMPLATE_URL}/js/script.js"></script> <!-- Generic scripts -->
    <!-- End scripts -->
</body>
</html>