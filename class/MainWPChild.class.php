<?php
define('MAINWP_CHILD_NR_OF_COMMENTS', 50);
define('MAINWP_CHILD_NR_OF_PAGES', 50);

include_once(ABSPATH . '/wp-admin/includes/file.php');

class MainWPChild
{
    private $callableFunctions = array(
        'stats' => 'getSiteStats',
        'upgrade' => 'upgradeWP',
        'newpost' => 'newPost',
        'deactivate' => 'deactivate',
        'newuser' => 'newUser',
        'newadminpassword' => 'newAdminPassword',
        'installplugintheme' => 'installPluginTheme',
        'upgradeplugintheme' => 'upgradePluginTheme',
        'backup' => 'backup',
        'cloneinfo' => 'cloneinfo',
        'security' => 'getSecurityStats',
        'securityFix' => 'doSecurityFix',
        'post_action' => 'post_action',
        'get_all_posts' => 'get_all_posts',
        'comment_action' => 'comment_action',
        'comment_bulk_action' => 'comment_bulk_action',
        'get_all_comments' => 'get_all_comments',
        'get_all_themes' => 'get_all_themes',
        'theme_action' => 'theme_action',
        'get_all_plugins' => 'get_all_plugins',
        'plugin_action' => 'plugin_action',
        'get_all_pages' => 'get_all_pages',
        'get_all_users' => 'get_all_users',
        'user_action' => 'user_action',
        'search_users' => 'search_users',
        'get_terms' => 'get_terms',
        'set_terms' => 'set_terms',
        'insert_comment' => 'insert_comment',
        'get_post_meta' => 'get_post_meta',
        'get_total_ezine_post' => 'get_total_ezine_post',
        'get_next_time_to_post' => 'get_next_time_to_post'
    );

    private $FTP_ERROR = 'Failed, please add FTP details for automatic upgrades.';

    private $callableFunctionsNoAuth = array(
        'stats' => 'getSiteStatsNoAuth'
    );

    private $posts_where_suffix;
    private $comments_and_clauses;
    private $plugin_slug;
    private $plugin_dir;
    private $slug;
    private $maxHistory = 5;

    public function __construct($plugin_file)
    {
        $this->plugin_dir = dirname($plugin_file);
        $this->plugin_slug = plugin_basename($plugin_file);
        list ($t1, $t2) = explode('/', $this->plugin_slug);
        $this->slug = str_replace('.php', '', $t2);

        $this->posts_where_suffix = '';
        $this->comments_and_clauses = '';
        add_action('init', array(&$this, 'parse_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'admin_init'));
        $this->checkOtherAuth();

        MainWPClone::init();
    }

    function checkOtherAuth()
    {
        $auths = get_option('mainwp_child_auth');

        if (!$auths)
        {
            $auths = array();
        }

        if (!isset($auths['last']) || $auths['last'] < mktime(0, 0, 0, date("m"), date("d"), date("Y")))
        {
            //Generate code for today..
            for ($i = 0; $i < $this->maxHistory; $i++)
            {
                if (!isset($auths[$i + 1])) continue;

                $auths[$i] = $auths[$i + 1];
            }
            $newI = $this->maxHistory + 1;
            while (isset($auths[$newI])) unset($auths[$newI++]);
            $auths[$this->maxHistory] = md5(MainWPHelper::randString(14));
            $auths['last'] = time();
            update_option('mainwp_child_auth', $auths);
        }
    }

    function isValidAuth($key)
    {
        $auths = get_option('mainwp_child_auth');
        if (!$auths) return false;
        for ($i = 0; $i <= $this->maxHistory; $i++)
        {
            if (isset($auths[$i]) && ($auths[$i] == $key)) return true;
        }

        return false;
    }

    function admin_init()
    {
        global $wp_version;

        if (version_compare('3.6', $wp_version, '>'))
        {
            wp_enqueue_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js', array('jquery'));
            wp_enqueue_style('jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/smoothness/jquery-ui.css');
        }
        else
        {
            wp_enqueue_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', array('jquery'));
            wp_enqueue_style('jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.css');
        }
//        wp_enqueue_script('jquery-ui-core');
//        wp_enqueue_script('jquery-ui-dialog');
    }

    function admin_menu()
    {
        add_options_page('MainWPSettings', 'MainWP Settings', 'manage_options', 'MainWPSettings', array(&$this, 'settings'));
        $sitesToClone = get_option('mainwp_child_clone_sites');
        if ($sitesToClone != '0') add_options_page('MainWPClone', 'MainWP Clone', 'manage_options', 'MainWPClone', array('MainWPClone', 'render'));
    }

    function settings()
    {
        if (isset($_POST['submit']))
        {
            if (isset($_POST['requireUniqueSecurityId']))
            {
                update_option('mainwp_child_uniqueId', MainWPHelper::randString(8));
            }
            else
            {
                update_option('mainwp_child_uniqueId', '');
            }
        }
        ?>
    <div id="icon-options-general" class="icon32"><br></div><h2>MainWP Settings</h2>
    <form method="post" action="">
        <br/>

        <h3>Connection Settings</h3>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><input name="requireUniqueSecurityId" type="checkbox"
                                       id="requireUniqueSecurityId" <?php if (get_option('mainwp_child_uniqueId') != '')
                    {
                        echo 'checked';
                    } ?> /> <label for="requireUniqueSecurityId">Require Unique Security ID</label></th>
                <td><?php if (get_option('mainwp_child_uniqueId') != '')
                {
                    echo '<i><strong>Your Unique Security ID is: ' . get_option('mainwp_child_uniqueId') . '</strong></i>';
                } ?></td>
            </tr>
            <tr>
                <td colspan="2"><span class="howto">The Unique Security ID adds additional protection between the Child plugin and your<br/>Main Dashboard. The Unique Security ID will need to match when being added to <br/>the Main Dashboard. This is additional security and should not be needed in most situations.</span>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                 value="Save Changes"></p></form>
    <?php
    }

    function mod_rewrite_rules($pRules)
    {

        $home_root = parse_url(home_url());
        if (isset($home_root['path']))
            $home_root = trailingslashit($home_root['path']);
        else
            $home_root = '/';

        $rules = "<IfModule mod_rewrite.c>\n";
        $rules .= "RewriteEngine On\n";
        $rules .= "RewriteBase $home_root\n";

        //add in the rules that don't redirect to WP's index.php (and thus shouldn't be handled by WP at all)
        foreach ($pRules as $match => $query)
        {
            // Apache 1.3 does not support the reluctant (non-greedy) modifier.
            $match = str_replace('.+?', '.+', $match);

            $rules .= 'RewriteRule ^' . $match . ' ' . $home_root . $query . " [QSA,L]\n";
        }

        $rules .= "</IfModule>\n";

        return $rules;
    }

    function update_htaccess($hard = false)
    {
        if ((get_option('mainwp_child_pluginDir') == 'hidden') && ($hard || (get_option('mainwp_child_htaccess_set') != 'yes')))
        {
            include_once(ABSPATH . '/wp-admin/includes/misc.php');

            $snPluginDir = basename($this->plugin_dir);

            $rules = null;
            if (get_option('heatMapEnabled') !== '0')
            {
                //Heatmap enabled
                //Make the plugin invisible, except heatmap
                $rules = $this->mod_rewrite_rules(array('wp-content/plugins/' . $snPluginDir . '/([^js\/]*)$' => 'wp-content/plugins/THIS_PLUGIN_DOES_NOT_EXIST'));
            }
            else
            {
                //Make the plugin invisible
                $rules = $this->mod_rewrite_rules(array('wp-content/plugins/' . $snPluginDir . '/(.*)$' => 'wp-content/plugins/THIS_PLUGIN_DOES_NOT_EXIST'));
            }

            $home_path = get_home_path();
            $htaccess_file = $home_path . '.htaccess';
            if (function_exists('save_mod_rewrite_rules'))
            {
                $rules = explode("\n", $rules);
                insert_with_markers($htaccess_file, 'MainWP', $rules);
            }

            update_option('mainwp_child_htaccess_set', 'yes');
        }
        else if ($hard)
        {
            include_once(ABSPATH . '/wp-admin/includes/misc.php');

            $home_path = get_home_path();
            $htaccess_file = $home_path . '.htaccess';
            if (function_exists('save_mod_rewrite_rules'))
            {
                $rules = explode("\n", '');
                insert_with_markers($htaccess_file, 'MainWP', $rules);
            }
        }
    }

    function parse_init()
    {
        if (isset($_POST['cloneFunc']))
        {
            if (!isset($_POST['key'])) return;
            if (!isset($_POST['file']) || ($_POST['file'] == '')) return;
            if (!$this->isValidAuth($_POST['key'])) return;

            if ($_POST['cloneFunc'] == 'createCloneBackupPoll')
            {
                $dirs = MainWPHelper::getMainWPDir('backup');
                $backupdir = $dirs[0];
                $result = glob($backupdir . 'backup-'.$_POST['file'].'-*.zip');
                if (count($result) == 0) return;

                MainWPHelper::write(array('size' => filesize($result[0])));
            }
            else if ($_POST['cloneFunc'] == 'createCloneBackup')
            {
                MainWPHelper::endSession();
                $wpversion = $_POST['wpversion'];
                global $wp_version;
                $includeCoreFiles = ($wpversion != $wp_version);
                $excludes = (isset($_POST['exclude']) ? explode(',', $_POST['exclude']) : array());
                $excludes[] = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/uploads/mainwp';
                $excludes[] = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/object-cache.php';
                if (!ini_get('safe_mode')) set_time_limit(600);

                $newExcludes = array();
                foreach ($excludes as $exclude)
                {
                    $newExcludes[] = rtrim($exclude, '/');
                }

                $res = MainWPBackup::get()->createFullBackup($newExcludes, $_POST['file'], true, $includeCoreFiles);
                if (!$res)
                {
                    $information['backup'] = false;
                }
                else
                {
                    $information['backup'] = $res['file'];
                    $information['size'] = $res['filesize'];
                }

                MainWPHelper::write($information);
            }
        }

        global $wp_rewrite;
        $snPluginDir = basename($this->plugin_dir);
        if (isset($wp_rewrite->non_wp_rules['wp-content/plugins/' . $snPluginDir . '/([^js\/]*)$']))
        {
            unset($wp_rewrite->non_wp_rules['wp-content/plugins/' . $snPluginDir . '/([^js\/]*)$']);
        }

        if (isset($wp_rewrite->non_wp_rules['wp-content/plugins/' . $snPluginDir . '/(.*)$']))
        {
            unset($wp_rewrite->non_wp_rules['wp-content/plugins/' . $snPluginDir . '/(.*)$']);
        }

        if (get_option('mainwp_child_fix_htaccess') === false)
        {
            include_once(ABSPATH . '/wp-admin/includes/misc.php');

            $wp_rewrite->flush_rules();
            update_option('mainwp_child_fix_htaccess', 'yes');
        }

        $this->update_htaccess();

        global $current_user; //wp variable

        //Login the user
        if (isset($_REQUEST['login_required']) && ($_REQUEST['login_required'] == 1) && isset($_REQUEST['user']))
        {
            if (!is_user_logged_in() || $_REQUEST['user'] != $current_user->user_login)
            {
                $auth = $this->auth($_REQUEST['mainwpsignature'], $_REQUEST['where'], $_REQUEST['nonce'], $_REQUEST['nossl']);
                if (!$auth) return;
                if (!$this->login($_REQUEST['user']))
                {
                    return;
                }
            }
            wp_safe_redirect(admin_url($_REQUEST['where']));
            exit();
        }


        remove_action('admin_init', 'send_frame_options_header');
        remove_action('login_init', 'send_frame_options_header');

        // Call Heatmap
        if (get_option('heatMapEnabled') !== '0') new MainWPHeatmapTracker();

        /**
         * Security
         */
        MainWPSecurity::fixAll();

        if (isset($_GET['test']))
        {
            error_reporting(E_ALL);
            ini_set('display_errors', TRUE);
            ini_set('display_startup_errors', TRUE);
            echo '<pre>';
            $excludes = (isset($_POST['exclude']) ? explode(',', $_POST['exclude']) : array());
            $excludes[] = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/uploads/mainwp';
            $excludes[] = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/object-cache.php';
            if (!ini_get('safe_mode')) set_time_limit(600);

            $newExcludes = array();
            foreach ($excludes as $exclude)
            {
                $newExcludes[] = rtrim($exclude, '/');
            }

            $res = MainWPBackup::get()->createFullBackup($newExcludes);
            die('</pre>');
        }

        //Register does not require auth, so we register here..
        if (isset($_POST['function']) && $_POST['function'] == 'register')
        {
            $this->registerSite();
        }

        $auth = $this->auth($_POST['mainwpsignature'], $_POST['function'], $_POST['nonce'], $_POST['nossl']);

        if (!$auth && isset($_POST['mainwpsignature']))
        {
            MainWPHelper::error('Authentication failed. Reinstall MainWP plugin please');
        }

        //Check if the user exists & is an administrator
        if (isset($_POST['function']) && isset($_POST['user']))
        {
            $user = get_user_by('login', $_POST['user']);
            if (!$user)
            {
                MainWPHelper::error('No such user');
            }

            if ($user->wp_user_level != 10 && (!isset($user->user_level) || $user->user_level != 10))
            {
                MainWPHelper::error('User is not an administrator');
            }
        }

        if (isset($_POST['function']) && $_POST['function'] == 'visitPermalink')
        {
            if ($auth)
            {
                if ($this->login($_POST['user'], true))
                {
                    return;
                }
                else
                {
                    exit();
                }
            }
        }

        //Redirect to the admin part if needed
        if ($auth && isset($_POST['admin']) && $_POST['admin'] == 1)
        {
            wp_redirect(get_option('siteurl') . '/wp-admin/');
            die();
        }

        //Call the function required
        if (isset($_POST['function']) && isset($this->callableFunctions[$_POST['function']]))
        {
            call_user_func(array($this, ($auth ? $this->callableFunctions[$_POST['function']]
                    : $this->callableFunctionsNoAuth[$_POST['function']])));
        }
    }

    function auth($signature, $func, $nonce, $pNossl)
    {
        if (!isset($signature) || !isset($func) || (!get_option('mainwp_child_pubkey') && !get_option('mainwp_child_nossl_key')))
        {
            $auth = false;
        }
        else
        {
            $nossl = get_option('mainwp_child_nossl');
            $serverNoSsl = (isset($pNossl) && $pNossl == 1);

            if (($nossl == 1) || $serverNoSsl)
            {
                $auth = (md5($func . $nonce . get_option('mainwp_child_nossl_key')) == base64_decode($signature));
            }
            else
            {
                $auth = openssl_verify($func . $nonce, base64_decode($signature), base64_decode(get_option('mainwp_child_pubkey')));
            }
        }

        return $auth;
    }

    //Login..
    function login($username, $doAction = false)
    {
        global $current_user;

        //Logout if required
        if (isset($current_user->user_login))
            do_action('wp_logout');

        $user = get_user_by('login', $username);
        if ($user)
        { //If user exists, login
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);

            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            if ($doAction) do_action('wp_login', $user->user_login);
            return (is_user_logged_in() && $current_user->user_login == $username);
        }
        return false;
    }

    /**
     * Functions to support core functionality
     */
    function installPluginTheme()
    {
        $wp_filesystem = $this->getWPFilesystem();

        if (!isset($_POST['type']) || !isset($_POST['url']) || ($_POST['type'] != 'plugin' && $_POST['type'] != 'theme') || $_POST['url'] == '')
        {
            MainWPHelper::error('Bad request.');
        }
        if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
        include_once(ABSPATH . '/wp-admin/includes/template.php');
        include_once(ABSPATH . '/wp-admin/includes/misc.php');
        include_once(ABSPATH . '/wp-admin/includes/class-wp-upgrader.php');
        include_once(ABSPATH . '/wp-admin/includes/plugin.php');

        $urlgot = json_decode(stripslashes($_POST['url']));
        $urls = array();
        if (!is_array($urlgot))
        {
            $urls[] = $urlgot;
        }
        else
        {
            $urls = $urlgot;
        }

        $result = array();
        foreach ($urls as $url)
        {
            $installer = new WP_Upgrader();
            //@see wp-admin/includes/class-wp-upgrader.php
            $result = $installer->run(array(
                'package' => $url,
                'destination' => ($_POST['type'] == 'plugin' ? WP_PLUGIN_DIR
                        : WP_CONTENT_DIR . '/themes'),
                'clear_destination' => false, //Do not overwrite files.
                'clear_working' => true,
                'hook_extra' => array()
            ));
            if (is_wp_error($result))
            {
                $error = $result->get_error_codes();
                if (is_array($error))
                {
                    MainWPHelper::error(implode(', ', $error));
                }
                else
                {
                    MainWPHelper::error($error);
                }
            }
            if ($_POST['type'] == 'plugin' && isset($_POST['activatePlugin']) && $_POST['activatePlugin'] == 'yes')
            {
                $path = $result['destination'];
                foreach ($result['source_files'] as $srcFile)
                {
                    $thePlugin = get_plugin_data($path . $srcFile);
                    if ($thePlugin != null && $thePlugin != '' && $thePlugin['Name'] != '')
                    {
                        activate_plugin($path . $srcFile);
                        break;
                    }
                }
            }
        }
        $information['installation'] = 'SUCCESS';
        $information['destination_name'] = $result['destination_name'];
        MainWPHelper::write($information);
    }

    //This will upgrade WP
    function upgradeWP()
    {
        global $wp_version;
        $wp_filesystem = $this->getWPFilesystem();

        $information = array();

        include_once(ABSPATH . '/wp-admin/includes/update.php');
        include_once(ABSPATH . '/wp-admin/includes/class-wp-upgrader.php');
        if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
        if (file_exists(ABSPATH . '/wp-admin/includes/template.php')) include_once(ABSPATH . '/wp-admin/includes/template.php');
        include_once(ABSPATH . '/wp-admin/includes/file.php');
        include_once(ABSPATH . '/wp-admin/includes/misc.php');

        //Check for new versions
        @wp_version_check();

        $core_updates = get_core_updates();
        if (count($core_updates) > 0)
        {
            foreach ($core_updates as $core_update)
            {
                if ($core_update->response == 'latest')
                {
                    $information['upgrade'] = 'SUCCESS';
                }
                else if ($core_update->response == 'upgrade' && $core_update->locale == get_locale() && version_compare($wp_version, $core_update->current, '<'))
                {
                    //Upgrade!
                    $upgrade = false;
                    if (class_exists('Core_Upgrader'))
                    {
                        $core = new Core_Upgrader();
                        $upgrade = $core->upgrade($core_update);
                    }
                    //If this does not work - add code from /wp-admin/includes/class-wp-upgrader.php in the newer versions
                    //So users can upgrade older versions too.
                    //3rd option: 'wp_update_core'

                    if (!is_wp_error($upgrade))
                    {
                        $information['upgrade'] = 'SUCCESS';
                    }
                    else
                    {
                        $information['upgrade'] = 'WPERROR';
                    }
                    break;
                }
            }
            if (!isset($information['upgrade']))
            {
                $information['upgrade'] = 'LOCALIZATION';
            }
        }
        else
        {
            $information['upgrade'] = 'NORESPONSE';
        }
        MainWPHelper::write($information);
    }

    /**
     * Expects $_POST['type'] == plugin/theme
     * $_POST['list'] == 'theme1,theme2' or 'plugin1,plugin2'
     */
    function upgradePluginTheme()
    {
        $wp_filesystem = $this->getWPFilesystem();

        include_once(ABSPATH . '/wp-admin/includes/class-wp-upgrader.php');
        if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
        if (file_exists(ABSPATH . '/wp-admin/includes/template.php')) include_once(ABSPATH . '/wp-admin/includes/template.php');
        include_once(ABSPATH . '/wp-admin/includes/file.php');
        include_once(ABSPATH . '/wp-admin/includes/plugin.php');

        $information = array();
        $information['upgrades'] = array();
        if (isset($_POST['type']) && $_POST['type'] == 'plugin')
        {
            include_once(ABSPATH . '/wp-admin/includes/update.php');
            @wp_update_plugins();
            $information['plugin_updates'] = get_plugin_updates();

            $plugins = explode(',', urldecode($_POST['list']));
            if (count($plugins) > 0)
            {
                //@see wp-admin/update.php
                $upgrader = new Plugin_Upgrader(new Bulk_Plugin_Upgrader_Skin(compact('nonce', 'url')));
                $result = $upgrader->bulk_upgrade($plugins);
                if (!empty($result))
                {
                    foreach ($result as $plugin => $info)
                    {
                        if (empty($info))
                        {
                            $information['upgrades'][$plugin] = false;
                        }
                        else
                        {
                            $information['upgrades'][$plugin] = true;
                        }
                    }
                }
                else
                {
                    MainWPHelper::error('Bad request');
                }
            }
            else
            {
                MainWPHelper::error('Bad request');
            }
        }
        else if (isset($_POST['type']) && $_POST['type'] == 'theme')
        {
            include_once(ABSPATH . '/wp-admin/includes/update.php');
            @wp_update_themes();
            include_once(ABSPATH . '/wp-admin/includes/theme.php');
            $information['theme_updates'] = $this->upgrade_get_theme_updates();

            $themes = explode(',', $_POST['list']);
            if (count($themes) > 0)
            {
                //@see wp-admin/update.php
                $upgrader = new Theme_Upgrader(new Bulk_Theme_Upgrader_Skin(compact('nonce', 'url')));
                $result = $upgrader->bulk_upgrade($themes);
                if (!empty($result))
                {
                    foreach ($result as $theme => $info)
                    {
                        if (empty($info))
                        {
                            $information['upgrades'][$theme] = false;
                        }
                        else
                        {
                            $information['upgrades'][$theme] = true;
                        }
                    }
                }
                else
                {
                    MainWPHelper::error('Bad request');
                }
            }
            else
            {
                MainWPHelper::error('Bad request');
            }
        }
        else
        {
            MainWPHelper::error('Bad request');
        }
        $information['sync'] = $this->getSiteStats(array(), false);
        MainWPHelper::write($information);
    }

    //This will register the current wp - thus generating the public key etc..
    function registerSite()
    {
        global $current_user;

        $information = array();
        //Check if the user is valid & login
        if (!isset($_POST['user']) || !isset($_POST['pubkey']))
        {
            MainWPHelper::error('Invalid request');
        }

        //Already added - can't readd. Deactivate plugin..
        if (get_option('mainwp_child_pubkey'))
        {
            MainWPHelper::error('Public key already set, reset the MainWP plugin on your site and try again.');
        }

        if (get_option('mainwp_child_uniqueId') != '')
        {
            if (!isset($_POST['uniqueId']) || ($_POST['uniqueId'] == ''))
            {
                MainWPHelper::error('This Child Site is set to require a Unique Security ID - Please Enter It before connection can be established.');
            }
            else if (get_option('mainwp_child_uniqueId') != $_POST['uniqueId'])
            {
                MainWPHelper::error('The Unique Security ID you have entered does not match Child Security ID - Please Correct It before connection can be established.');
            }
        }

        //Login
        if (isset($_POST['user']))
        {
            if (!$this->login($_POST['user']))
            {
                MainWPHelper::error('No such user');
            }
            if ($current_user->wp_user_level != 10 && (!isset($current_user->user_level) || $current_user->user_level != 10))
            {
                MainWPHelper::error('User is not an administrator');
            }
        }

        update_option('mainwp_child_pubkey', base64_encode($_POST['pubkey'])); //Save the public key
        update_option('mainwp_child_server', $_POST['server']); //Save the public key
        update_option('mainwp_child_nonce', 0); //Save the nonce

        update_option('mainwp_child_nossl', ($_POST['pubkey'] == '-1' || !function_exists('openssl_verify') ? 1 : 0));
        $information['nossl'] = ($_POST['pubkey'] == '-1' || !function_exists('openssl_verify') ? 1 : 0);
        $nossl_key = uniqid('', true);
        update_option('mainwp_child_nossl_key', $nossl_key);
        $information['nosslkey'] = $nossl_key;

        $information['register'] = 'OK';
        $information['user'] = $_POST['user'];
        $this->getSiteStats($information);
    }

    function newPost()
    {
        //Read form data
        $new_post = unserialize(base64_decode($_POST['new_post']));
        $post_custom = unserialize(base64_decode($_POST['post_custom']));
        $post_category = (isset($_POST['post_category']) ? base64_decode($_POST['post_category']) : null);
        $post_featured_image = base64_decode($_POST['post_featured_image']);
        $upload_dir = unserialize(base64_decode($_POST['mainwp_upload_dir']));
        $new_post['_ezin_post_category'] = unserialize(base64_decode($_POST['_ezin_post_category']));

        $res = MainWPHelper::createPost($new_post, $post_custom, $post_category, $post_featured_image, $upload_dir);
        $created = $res['success'];
        if ($created != true)
        {
            MainWPHelper::error($created);
        }

        $information['added'] = true;
        $information['added_id'] = $res['added_id'];
        $information['link'] = $res['link'];

        MainWPHelper::write($information);
    }

    function post_action()
    {
        //Read form data
        $action = $_POST['action'];
        $postId = $_POST['id'];

        if ($action == 'publish')
        {
            wp_publish_post($postId);
        }
        else if ($action == 'unpublish')
        {
            $my_post = array();
            $my_post['ID'] = $postId;
            $my_post['post_status'] = 'draft';
            wp_update_post($my_post);
        }
        else if ($action == 'trash')
        {
            wp_trash_post($postId);
        }
        else if ($action == 'delete')
        {
            wp_delete_post($postId);
        }
        else if ($action == 'restore')
        {
            wp_untrash_post($postId);
        }
        else if ($action == 'update_meta')
        {
            $values = unserialize(base64_decode($_POST['values']));
            $meta_key = $values['meta_key'];
            $meta_value = $values['meta_value'];
            $check_prev = $values['check_prev'];

            foreach ($meta_key as $i => $key)
            {
                if (intval($check_prev[$i]) == 1)
                    update_post_meta($postId, $key, get_post_meta($postId, $key, true) ? get_post_meta($postId, $key, true) : $meta_value[$i]);
                else
                    update_post_meta($postId, $key, $meta_value[$i]);
            }
        }
        else
        {
            $information['status'] = 'FAIL';
        }

        if (!isset($information['status'])) $information['status'] = 'SUCCESS';
        MainWPHelper::write($information);
    }

    function user_action()
    {
        //Read form data
        $action = $_POST['action'];
        $extra = $_POST['extra'];
        $userId = $_POST['id'];
        $user_pass = $_POST['user_pass'];

        if ($action == 'delete')
        {
            include_once(ABSPATH . '/wp-admin/includes/user.php');
            wp_delete_user($userId);
        }
        else if ($action == 'changeRole')
        {
            $my_user = array();
            $my_user['ID'] = $userId;
            $my_user['role'] = $extra;
            wp_update_user($my_user);
        }
        else if ($action == 'update_password')
        {
            $my_user = array();
            $my_user['ID'] = $userId;
            $my_user['user_pass'] = $user_pass;
            wp_update_user($my_user);
        }
        else
        {
            $information['status'] = 'FAIL';
        }

        if (!isset($information['status'])) $information['status'] = 'SUCCESS';
        MainWPHelper::write($information);
    }

    //todo: backwards compatible: wp_set_comment_status ?
    function comment_action()
    {
        //Read form data
        $action = $_POST['action'];
        $commentId = $_POST['id'];

        if ($action == 'approve')
        {
            wp_set_comment_status($commentId, 'approve');
        }
        else if ($action == 'unapprove')
        {
            wp_set_comment_status($commentId, 'hold');
        }
        else if ($action == 'spam')
        {
            wp_spam_comment($commentId);
        }
        else if ($action == 'unspam')
        {
            wp_unspam_comment($commentId);
        }
        else if ($action == 'trash')
        {
            wp_trash_comment($commentId);
        }
        else if ($action == 'restore')
        {
            wp_untrash_comment($commentId);
        }
        else if ($action == 'delete')
        {
            wp_delete_comment($commentId);
        }
        else
        {
            $information['status'] = 'FAIL';
        }

        if (!isset($information['status'])) $information['status'] = 'SUCCESS';
        MainWPHelper::write($information);
    }

    //todo: backwards compatible: wp_set_comment_status ?
    function comment_bulk_action()
    {
        //Read form data
        $action = $_POST['action'];
        $commentIds = explode(',', $_POST['ids']);
        $information['success'] = 0;
        foreach ($commentIds as $commentId)
        {
            if ($commentId)
            {
                $information['success']++;
                if ($action == 'approve')
                {
                    wp_set_comment_status($commentId, 'approve');
                }
                else if ($action == 'unapprove')
                {
                    wp_set_comment_status($commentId, 'hold');
                }
                else if ($action == 'spam')
                {
                    wp_spam_comment($commentId);
                }
                else if ($action == 'unspam')
                {
                    wp_unspam_comment($commentId);
                }
                else if ($action == 'trash')
                {
                    wp_trash_comment($commentId);
                }
                else if ($action == 'restore')
                {
                    wp_untrash_comment($commentId);
                }
                else if ($action == 'delete')
                {
                    wp_delete_comment($commentId);
                }
                else
                {
                    $information['success']--;
                }


            }
        }
        MainWPHelper::write($information);
    }


    function newAdminPassword()
    {
        //Read form data
        $new_password = unserialize(base64_decode($_POST['new_password']));
        $user = get_user_by('login', $_POST['user']);
        require_once(ABSPATH . WPINC . '/registration.php');

        $id = wp_update_user(array('ID' => $user->ID, 'user_pass' => $new_password['user_pass']));
        if ($id != $user->ID)
        {
            if (is_wp_error($id))
            {
                MainWPHelper::error($id->get_error_message());
            }
            else
            {
                MainWPHelper::error('Could not change the admin password.');
            }
        }

        $information['added'] = true;
        MainWPHelper::write($information);
    }

    function newUser()
    {
        //Read form data
        $new_user = unserialize(base64_decode($_POST['new_user']));
        $send_password = $_POST['send_password'];

        $new_user_id = wp_insert_user($new_user);

        if (is_wp_error($new_user_id))
        {
            MainWPHelper::error($new_user_id->get_error_message());
        }
        if ($new_user_id == 0)
        {
            MainWPHelper::error('Undefined error');
        }

        if ($send_password)
        {
            $user = new WP_User($new_user_id);

            $user_login = stripslashes($user->user_login);
            $user_email = stripslashes($user->user_email);

            // The blogname option is escaped with esc_html on the way into the database in sanitize_option
            // we want to reverse this for the plain text arena of emails.
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

            $message = sprintf(__('Username: %s'), $user_login) . "\r\n";
            $message .= sprintf(__('Password: %s'), $new_user['user_pass']) . "\r\n";
            $message .= wp_login_url() . "\r\n";

            wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);
        }
        $information['added'] = true;
        MainWPHelper::write($information);
    }

    function cloneinfo()
    {
        global $table_prefix;
        $information['dbCharset'] = DB_CHARSET;
        $information['dbCollate'] = DB_COLLATE;
        $information['table_prefix'] = $table_prefix;
        $information['site_url'] = get_option('site_url');
        $information['home'] = get_option('home');

        MainWPHelper::write($information);
    }

    function backup()
    {
        if ($_POST['type'] == 'full')
        {
            $excludes = (isset($_POST['exclude']) ? explode(',', $_POST['exclude']) : array());
            $excludes[] = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/uploads/mainwp';
            $excludes[] = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/object-cache.php';
            if (!ini_get('safe_mode')) set_time_limit(600);

            $newExcludes = array();
            foreach ($excludes as $exclude)
            {
                $newExcludes[] = rtrim($exclude, '/');
            }

            $res = MainWPBackup::get()->createFullBackup($newExcludes);
            if (!$res)
            {
                $information['full'] = false;
            }
            else
            {
                $information['full'] = $res['file'];
                $information['size'] = $res['filesize'];
            }
            $information['db'] = false;
        }
        else if ($_POST['type'] == 'db')
        {
            $res = $this->backupDB();
            if (!$res)
            {
                $information['db'] = false;
            }
            else
            {
                $information['db'] = $res['file'];
                $information['size'] = $res['filesize'];
            }
            $information['full'] = false;
        }
        else
        {
            $information['full'] = false;
            $information['db'] = false;
        }
        MainWPHelper::write($information);
    }

    protected function backupDB()
    {
        $dirs = MainWPHelper::getMainWPDir('backup');
        $dir = $dirs[0];
        $timestamp = time();
        $filepath = $dir . 'dbBackup-' . $timestamp . '.sql';

        if ($dh = opendir($dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                if ($file != '.' && $file != '..' && preg_match('/dbBackup-(.*).sql$/', $file))
                {
                    @unlink($dir . $file);
                }
            }
            closedir($dh);
        }

        if (file_exists($filepath))
        {
            @unlink($filepath);
        }


        $success = MainWPBackup::get()->createBackupDB($filepath);

        return ($success) ? array(
            'timestamp' => $timestamp,
            'file' => $dirs[1] . basename($filepath),
            'filesize' => filesize($filepath)
        ) : false;
    }

    function doSecurityFix()
    {
        if ($_POST['feature'] == 'all')
        {
            //fix all
        }
        $information = array();
        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'listing')
        {
            MainWPSecurity::prevent_listing();
            $information['listing'] = (!MainWPSecurity::prevent_listing_ok() ? 'N' : 'Y');
        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'wp_version')
        {
            update_option('mainwp_child_remove_wp_version', 'T');
            MainWPSecurity::remove_wp_version();
            $information['wp_version'] = (!MainWPSecurity::remove_wp_version_ok() ? 'N' : 'Y');
        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'rsd')
        {
            update_option('mainwp_child_remove_rsd', 'T');
            MainWPSecurity::remove_rsd();
            $information['rsd'] = (!MainWPSecurity::remove_rsd_ok() ? 'N' : 'Y');
        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'wlw')
        {
            update_option('mainwp_child_remove_wlw', 'T');
            MainWPSecurity::remove_wlw();
            $information['wlw'] = (!MainWPSecurity::remove_wlw_ok() ? 'N' : 'Y');
        }

//        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'core_updates')
//        {
//            update_option('mainwp_child_remove_core_updates', 'T');
//            MainWPSecurity::remove_core_update();
//            $information['core_updates'] = (!MainWPSecurity::remove_core_update_ok() ? 'N' : 'Y');
//        }

//        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'plugin_updates')
//        {
//            update_option('mainwp_child_remove_plugin_updates', 'T');
//            MainWPSecurity::remove_plugin_update();
//            $information['plugin_updates'] = (!MainWPSecurity::remove_plugin_update_ok() ? 'N' : 'Y');
//        }

//        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'theme_updates')
//        {
//            update_option('mainwp_child_remove_theme_updates', 'T');
//            MainWPSecurity::remove_theme_update();
//            $information['theme_updates'] = (!MainWPSecurity::remove_theme_update_ok() ? 'N' : 'Y');
//        }

//        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'file_perms')
//        {
//            MainWPSecurity::fix_file_permissions();
//            $information['file_perms'] = (!MainWPSecurity::fix_file_permissions_ok() ? 'N' : 'Y');
//            if ($information['file_perms'] == 'N')
//            {
//                $information['file_perms'] = 'Could not change all the file permissions';
//            }
//        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'db_reporting')
        {
            MainWPSecurity::remove_database_reporting();
            $information['db_reporting'] = (!MainWPSecurity::remove_database_reporting_ok() ? 'N' : 'Y');
        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'php_reporting')
        {
            update_option('mainwp_child_remove_php_reporting', 'T');
            MainWPSecurity::remove_php_reporting();
            $information['php_reporting'] = (!MainWPSecurity::remove_php_reporting_ok() ? 'N' : 'Y');
        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'versions')
        {
            update_option('mainwp_child_remove_scripts_version', 'T');
            update_option('mainwp_child_remove_styles_version', 'T');
            MainWPSecurity::remove_scripts_version();
            MainWPSecurity::remove_styles_version();
            $information['versions'] = (!MainWPSecurity::remove_scripts_version_ok() || !MainWPSecurity::remove_styles_version_ok()
                    ? 'N' : 'Y');
        }

        if ($_POST['feature'] == 'all' || $_POST['feature'] == 'admin')
        {
            $information['admin'] = (!MainWPSecurity::admin_user_ok() ? 'N' : 'Y');
        }

        MainWPHelper::write($information);
    }

    function getSecurityStats()
    {
        $information = array();

        $information['listing'] = (!MainWPSecurity::prevent_listing_ok() ? 'N' : 'Y');
        $information['wp_version'] = (!MainWPSecurity::remove_wp_version_ok() ? 'N' : 'Y');
        $information['rsd'] = (!MainWPSecurity::remove_rsd_ok() ? 'N' : 'Y');
        $information['wlw'] = (!MainWPSecurity::remove_wlw_ok() ? 'N' : 'Y');
//        $information['core_updates'] = (!MainWPSecurity::remove_core_update_ok() ? 'N' : 'Y');
//        $information['plugin_updates'] = (!MainWPSecurity::remove_plugin_update_ok() ? 'N' : 'Y');
//        $information['theme_updates'] = (!MainWPSecurity::remove_theme_update_ok() ? 'N' : 'Y');
//        $information['file_perms'] = (!MainWPSecurity::fix_file_permissions_ok() ? 'N' : 'Y');
        $information['db_reporting'] = (!MainWPSecurity::remove_database_reporting_ok() ? 'N' : 'Y');
        $information['php_reporting'] = (!MainWPSecurity::remove_php_reporting_ok() ? 'N' : 'Y');
        $information['versions'] = (!MainWPSecurity::remove_scripts_version_ok() || !MainWPSecurity::remove_styles_version_ok()
                ? 'N' : 'Y');
        $information['admin'] = (!MainWPSecurity::admin_user_ok() ? 'N' : 'Y');

        MainWPHelper::write($information);
    }

    function updateExternalSettings()
    {
        $update_htaccess = false;

        if (isset($_POST['heatMap']))
        {
            if ($_POST['heatMap'] == '0')
            {
                if (get_option('heatMapEnabled') != '0') $update_htaccess = true;
                update_option('heatMapEnabled', '0');
            }
            else
            {
                if (get_option('heatMapEnabled') != '1') $update_htaccess = true;
                update_option('heatMapEnabled', '1');
            }
        }

        if (isset($_POST['cloneSites']))
        {
            if ($_POST['cloneSites'] != '0')
            {
                $arr = @json_decode(urldecode($_POST['cloneSites']), 1);
                update_option('mainwp_child_clone_sites', (!is_array($arr) ? array() : $arr));
            }
            else
            {
                update_option('mainwp_child_clone_sites', '0');
            }
        }

        if (isset($_POST['pluginDir']))
        {
            if (get_option('mainwp_child_pluginDir') != $_POST['pluginDir'])
            {
                update_option('mainwp_child_pluginDir', $_POST['pluginDir']);
                $update_htaccess = true;
            }
        }
        else if (get_option('mainwp_child_pluginDir') != false)
        {
            delete_option('mainwp_child_pluginDir');
            $update_htaccess = true;
        }

        if ($update_htaccess)
        {
            $this->update_htaccess(true);
        }
    }

    //Show stats
    function getSiteStats($information = array(), $exit = true)
    {
        global $wp_version;

        $this->updateExternalSettings();

        $information['wpversion'] = $wp_version;
        $information['siteurl'] = get_option('siteurl');
        $information['nossl'] = (get_option('mainwp_child_nossl') == 1 ? 1 : 0);

        include_once(ABSPATH . '/wp-admin/includes/update.php');

        //Check for new versions
        @wp_version_check();
        $core_updates = get_core_updates();
        if (count($core_updates) > 0)
        {
            foreach ($core_updates as $core_update)
            {
                if ($core_update->response == 'latest')
                {
                    break;
                }
                if ($core_update->response == 'upgrade' && version_compare($wp_version, $core_update->current, '<'))
                {
                    $information['wp_updates'] = $core_update->current;
                }
            }
        }
        if (!isset($information['wp_updates']))
        {
            $information['wp_updates'] = null;
        }

        @wp_update_plugins();
        include_once(ABSPATH . '/wp-admin/includes/plugin.php');
        $information['plugin_updates'] = get_plugin_updates();

        @wp_update_themes();
        include_once(ABSPATH . '/wp-admin/includes/theme.php');
        $information['theme_updates'] = $this->upgrade_get_theme_updates();

        $information['recent_comments'] = $this->get_recent_comments(array('approve', 'hold'), 5);
        $information['recent_posts'] = $this->get_recent_posts(array('publish', 'draft', 'pending'), 5);

        $securityIssuess = 0;
        if (!MainWPSecurity::prevent_listing_ok()) $securityIssuess++;
        if (!MainWPSecurity::remove_wp_version_ok()) $securityIssuess++;
        if (!MainWPSecurity::remove_rsd_ok()) $securityIssuess++;
        if (!MainWPSecurity::remove_wlw_ok()) $securityIssuess++;
//        if (!MainWPSecurity::remove_core_update_ok()) $securityIssuess++;
//        if (!MainWPSecurity::remove_plugin_update_ok()) $securityIssuess++;
//        if (!MainWPSecurity::remove_theme_update_ok()) $securityIssuess++;
//        if (!MainWPSecurity::fix_file_permissions_ok()) $securityIssuess++;
        if (!MainWPSecurity::remove_database_reporting_ok()) $securityIssuess++;
        if (!MainWPSecurity::remove_php_reporting_ok()) $securityIssuess++;
        if (!MainWPSecurity::remove_scripts_version_ok() || !MainWPSecurity::remove_styles_version_ok()) $securityIssuess++;
        if (!MainWPSecurity::admin_user_ok()) $securityIssuess++;

        $information['securityIssues'] = $securityIssuess;

        //Directory listings!
        $information['directories'] = $this->scanDir(ABSPATH, 3);
        $cats = get_categories(array('hide_empty' => 0, 'name' => 'select_name', 'hierarchical' => true));
        $categories = array();
        foreach ($cats as $cat)
        {
            $categories[] = $cat->name;
        }
        $information['categories'] = $categories;
        $information['totalsize'] = $this->getTotalFileSize();
        $auths = get_option('mainwp_child_auth');
        $information['extauth'] = ($auths && isset($auths[$this->maxHistory]) ? $auths[$this->maxHistory] : null);

        $plugins = false;
        $themes = false;
        if (isset($_POST['optimize']) && ($_POST['optimize'] == 1))
        {
            $plugins = $this->get_all_plugins_int(false);
            $information['plugins'] = $plugins;
            $themes = $this->get_all_themes_int(false);
            $information['themes'] = $themes;
            $information['users'] = $this->get_all_users_int();
        }

        if (isset($_POST['pluginConflicts']) && ($_POST['pluginConflicts'] != false))
        {
            $pluginConflicts = json_decode(stripslashes($_POST['pluginConflicts']), true);
            $conflicts = array();
            if (count($pluginConflicts) > 0)
            {
                if ($plugins == false) $plugins = $this->get_all_plugins_int(false);
                foreach ($plugins as $plugin)
                {
                    foreach ($pluginConflicts as $pluginConflict)
                    {
                       if (($plugin['active'] == 1) && (($plugin['name'] == $pluginConflict) || ($plugin['slug'] == $pluginConflict)))
                       {
                           $conflicts[] = $plugin['name'];
                       }
                    }
                }
            }
            if (count($conflicts) > 0) $information['pluginConflicts'] = $conflicts;
        }

        if (isset($_POST['themeConflicts']) && ($_POST['themeConflicts'] != false))
        {
            $themeConflicts = json_decode(stripslashes($_POST['themeConflicts']), true);
            $conflicts = array();
            if (count($themeConflicts) > 0)
            {
                $theme = get_current_theme();
                foreach ($themeConflicts as $themeConflict)
                {
                   if ($theme == $themeConflict)
                   {
                       $conflicts[] = $theme;
                   }
                }
            }
            if (count($conflicts) > 0) $information['themeConflicts'] = $conflicts;
        }

        if ($exit) MainWPHelper::write($information);

        return $information;
    }

    function scanDir($pDir, $pLvl)
    {
        $output = array();
        if (file_exists($pDir) && is_dir($pDir))
        {
            if ($pLvl == 0) return $output;

            if ($files = @scandir($pDir))
            {
                foreach ($files as $file)
                {
                    if (($file == '.') || ($file == '..')) continue;
                    $newDir = $pDir . $file . DIRECTORY_SEPARATOR;
                    if (@is_dir($newDir))
                    {
                        $output[$file] = $this->scanDir($newDir, $pLvl - 1);
                    }
                }
            }
        }
        return $output;
    }

    function upgrade_get_theme_updates()
    {
        $themeUpdates = get_theme_updates();
        $newThemeUpdates = array();
        if (is_array($themeUpdates))
        {
            foreach ($themeUpdates as $slug => $themeUpdate)
            {
                $newThemeUpdate = array();
                $newThemeUpdate['update'] = $themeUpdate->update;
                $newThemeUpdate['Name'] = MainWPHelper::search($themeUpdate, 'Name');
                $newThemeUpdate['Version'] = MainWPHelper::search($themeUpdate, 'Version');
                $newThemeUpdates[$slug] = $newThemeUpdate;
            }
        }

        return $newThemeUpdates;
    }

    function get_recent_posts($pAllowedStatuses, $pCount, $type = 'post')
    {
        $allPosts = array();
        if ($pAllowedStatuses != null)
        {
            foreach ($pAllowedStatuses as $status)
            {
                $this->get_recent_posts_int($status, $pCount, $type, $allPosts);
            }
        }
        else
        {
            $this->get_recent_posts_int('any', $pCount, $type, $allPosts);
        }
        return $allPosts;
    }

    function get_recent_posts_int($status, $pCount, $type = 'post', &$allPosts)
    {
        $args = array('numberposts' => $pCount,
            'post_status' => $status,
            'suppress_filters' => false,
            'post_type' => $type);
        $posts = get_posts($args);
        if (is_array($posts))
        {
            foreach ($posts as $post)
            {
                $outPost = array();
                $outPost['id'] = $post->ID;
                $outPost['status'] = $post->post_status;
                $outPost['title'] = $post->post_title;
                $outPost['comment_count'] = $post->comment_count;
                $outPost['dts'] = $post->post_modified;
                $usr = get_user_by('id', $post->post_author);
                $outPost['author'] = $usr->user_nicename;
                $categoryObjects = get_the_category($post->ID);
                $categories = "";
                foreach ($categoryObjects as $cat)
                {
                    if ($categories != "") $categories .= ", ";
                    $categories .= $cat->name;
                }
                $outPost['categories'] = $categories;

                $tagObjects = get_the_tags($post->ID);
                $tags = "";
                if (is_array($tagObjects))
                {
                    foreach ($tagObjects as $tag)
                    {
                        if ($tags != "") $tags .= ", ";
                        $tags .= $tag->name;
                    }
                }
                $outPost['tags'] = $tags;
                $allPosts[] = $outPost;
            }
        }
    }

    function posts_where($where)
    {
        if ($this->posts_where_suffix) $where .= ' ' . $this->posts_where_suffix;
        return $where;
    }

    function get_all_posts()
    {
        $this->get_all_posts_by_type('post');
    }

    function get_terms()
    {
        $taxonomy = base64_decode($_POST['taxonomy']);
        $rslt = get_terms(taxonomy_exists($taxonomy) ? $taxonomy : 'category', 'hide_empty=0');
        MainWPHelper::write($rslt);
    }

    function set_terms()
    {
        $id = base64_decode($_POST['id']);
        $terms = base64_decode($_POST['terms']);
        $taxonomy = base64_decode($_POST['taxonomy']);

        if (trim($terms) != '')
        {
            $terms = explode(',', $terms);
            if (count($terms) > 0)
            {
                wp_set_object_terms($id, array_map('intval', $terms), taxonomy_exists($taxonomy) ? $taxonomy : 'category');
            }
        }
    }

    function insert_comment()
    {
        $postId = $_POST['id'];
        $comments = unserialize(base64_decode($_POST['comments']));
        $ids = array();
        foreach ($comments as $comment)
        {
            $ids[] = wp_insert_comment(array(
                'comment_post_ID' => $postId,
                'comment_author' => $comment['author'],
                'comment_content' => $comment['content'],
                'comment_date' => $comment['date']
            ));
        }
        MainWPHelper::write($ids);
    }

    function get_post_meta()
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        $postId = $_POST['id'];
        $keys = base64_decode(unserialize($_POST['keys']));
        $meta_value = $_POST['value'];

        $where = '';
        if (!empty($postId))
            $where .= " AND `post_id` = $postId ";
        if (!empty($keys))
        {
            $str_keys = '\'' . implode('\',\'', $keys) . '\'';
            $where .= " AND `meta_key` IN = $str_keys ";
        }
        if (!empty($meta_value))
            $where .= " AND `meta_value` = $meta_value ";


        $results = $wpdb->get_results(sprintf("SELECT * FROM %s WHERE 1 = 1 $where ", $wpdb->postmeta));
        MainWPHelper::write($results);
    }

    function get_total_ezine_post()
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        $start_date = base64_decode($_POST['start_date']);
        $end_date = base64_decode($_POST['end_date']);
        $keyword_meta = base64_decode($_POST['keyword_meta']);
        $where = " WHERE ";
        if (!empty($start_date) && !empty($end_date))
            $where .= "  p.post_date>='$start_date' AND p.post_date<='$end_date' AND ";
        else if (!empty($start_date) && empty($end_date))
        {
            $where .= "  p.post_date='$start_date' AND ";
        }
        $where .= " ( p.post_status='publish' OR p.post_status='future' OR p.post_status='draft' ) 
                                AND  (pm.meta_key='_ezine_keyword' AND pm.meta_value='$keyword_meta')";
        $total = $wpdb->get_var("SELECT COUNT(*)
                                                         FROM $wpdb->posts p JOIN $wpdb->postmeta pm ON p.ID=pm.post_id 
                                                         $where  ");
        MainWPHelper::write($total);
    }

    function get_next_time_to_post()
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        $ct = current_time('mysql');
        $next_post = $wpdb->get_row("
            SELECT *
            FROM $wpdb->posts p JOIN $wpdb->postmeta pm ON p.ID=pm.post_id
            WHERE
                pm.meta_key='_ezine_keyword' AND
                p.post_status='future' AND
                p.post_date>'$ct'
            ORDER BY p.post_date
            LIMIT 1
        ");
        if (!$next_post)
            return;
       $information['next_post_date'] =  $next_post->post_date;
       $information['next_post_id'] =  $next_post->ID;
        MainWPHelper::write($information);
    }

    function get_all_pages()
    {
        $this->get_all_posts_by_type('page');
    }

    function get_all_pages_int()
    {
        $rslt = $this->get_recent_posts(null, -1, 'page');
        return $rslt;
    }

    function get_all_posts_by_type($type)
    {
        global $wpdb;

        add_filter('posts_where', array(&$this, 'posts_where'));

        if (isset($_POST['keyword']))
        {
            $this->posts_where_suffix .= " AND $wpdb->posts.post_content LIKE '%" . $_POST['keyword'] . "%'";
        }
        if (isset($_POST['dtsstart']) && $_POST['dtsstart'] != '')
        {
            $this->posts_where_suffix .= " AND $wpdb->posts.post_modified > '" . $_POST['dtsstart'] . "'";
        }
        if (isset($_POST['dtsstop']) && $_POST['dtsstop'] != '')
        {
            $this->posts_where_suffix .= " AND $wpdb->posts.post_modified < '" . $_POST['dtsstop'] . "'";
        }

        $rslt = $this->get_recent_posts(explode(',', $_POST['status']), MAINWP_CHILD_NR_OF_PAGES, $type);
        $this->posts_where_suffix = '';

        MainWPHelper::write($rslt);
    }

    function comments_clauses($clauses)
    {
        if ($this->comments_and_clauses) $clauses['where'] .= ' ' . $this->comments_and_clauses;
        return $clauses;
    }

    function get_all_comments()
    {
        global $wpdb;

        add_filter('comments_clauses', array(&$this, 'comments_clauses'));

        if (isset($_POST['keyword']))
        {
            $this->comments_and_clauses .= " AND $wpdb->comments.comment_content LIKE '%" . $_POST['keyword'] . "%'";
        }
        if (isset($_POST['dtsstart']) && $_POST['dtsstart'] != '')
        {
            $this->comments_and_clauses .= " AND $wpdb->comments.comment_date > '" . $_POST['dtsstart'] . "'";
        }
        if (isset($_POST['dtsstop']) && $_POST['dtsstop'] != '')
        {
            $this->comments_and_clauses .= " AND $wpdb->comments.comment_date < '" . $_POST['dtsstop'] . "'";
        }

        $rslt = $this->get_recent_comments(explode(',', $_POST['status']), MAINWP_CHILD_NR_OF_COMMENTS);
        $this->comments_and_clauses = '';

        MainWPHelper::write($rslt);
    }

    function get_recent_comments($pAllowedStatuses, $pCount)
    {
        $allComments = array();

        foreach ($pAllowedStatuses as $status)
        {
            $comments = get_comments(array('number' => $pCount, 'status' => $status));
            if (is_array($comments))
            {
                foreach ($comments as $comment)
                {
                    $outComment = array();
                    $outComment['id'] = $comment->comment_ID;
                    $outComment['status'] = wp_get_comment_status($comment->comment_ID);
                    $outComment['author'] = $comment->comment_author;
                    $outComment['postId'] = $comment->comment_post_ID;
                    $outComment['postName'] = get_post($comment->comment_post_ID)->post_title;
                    $outComment['content'] = $comment->comment_content;
                    $outComment['dts'] = $comment->comment_date;
                    $allComments[] = $outComment;
                }
            }
        }
        return $allComments;
    }

    function theme_action()
    {
        //Read form data
        $action = $_POST['action'];
        $theme = $_POST['theme'];

        if ($action == 'activate')
        {
            include_once(ABSPATH . '/wp-admin/includes/theme.php');
            $theTheme = get_theme($theme);
            if ($theTheme != null && $theTheme != '') switch_theme($theTheme['Template'], $theTheme['Stylesheet']);
        }
        else if ($action == 'delete')
        {
            include_once(ABSPATH . '/wp-admin/includes/theme.php');
            if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
            include_once(ABSPATH . '/wp-admin/includes/file.php');
            include_once(ABSPATH . '/wp-admin/includes/template.php');
            include_once(ABSPATH . '/wp-admin/includes/misc.php');
            include_once(ABSPATH . '/wp-admin/includes/class-wp-upgrader.php');
            include_once(ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php');
            include_once(ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php');

            $wp_filesystem = $this->getWPFilesystem();
            if (empty($wp_filesystem)) $wp_filesystem = new WP_Filesystem_Direct(null);
            $themeUpgrader = new Theme_Upgrader();

            $theme_name = get_current_theme();
            $themes = explode('||', $theme);

            foreach ($themes as $idx => $themeToDelete)
            {
                if ($themeToDelete != $theme_name)
                {
                    $theTheme = get_theme($themeToDelete);
                    if ($theTheme != null && $theTheme != '')
                    {
                        $tmp['theme'] = $theTheme['Template'];
                        $themeUpgrader->delete_old_theme(null, null, null, $tmp);
                    }
                }
            }
        }
        else
        {
            $information['status'] = 'FAIL';
        }

        if (!isset($information['status'])) $information['status'] = 'SUCCESS';
        $information['sync'] = $this->getSiteStats(array(), false);
        MainWPHelper::write($information);
    }

    function get_all_themes()
    {
        $keyword = $_POST['keyword'];
        $status = $_POST['status'];
        $rslt = $this->get_all_themes_int(true, $keyword, $status);

        MainWPHelper::write($rslt);
    }

    function get_all_themes_int($filter, $keyword = '', $status = '')
    {
        $rslt = array();
        $themes = get_themes(); //todo: deprecated, use wp_get_themes
        if (is_array($themes))
        {
            $theme_name = get_current_theme();

            foreach ($themes as $theme)
            {
                $out = array();
                $out['name'] = $theme['Name'];
                $out['title'] = $theme['Title'];
                $out['description'] = $theme['Description'];
                $out['version'] = $theme['Version'];
                $out['active'] = ($theme['Name'] == $theme_name) ? 1 : 0;
                $out['slug'] = $theme['Stylesheet'];
                if (!$filter)
                {
                    $rslt[] = $out;
                }
                else if ($out['active'] == (($status == 'active') ? 1 : 0))
                {
                    if ($keyword == '' || stristr($out['title'], $keyword)) $rslt[] = $out;
                }
            }
        }

        return $rslt;
    }

    function plugin_action()
    {
        //Read form data
        $action = $_POST['action'];
        $plugins = explode('||', $_POST['plugin']);

        if ($action == 'activate')
        {
            include_once(ABSPATH . '/wp-admin/includes/plugin.php');

            foreach ($plugins as $idx => $plugin)
            {
                if ($plugin != $this->plugin_slug)
                {
                    $thePlugin = get_plugin_data($plugin);
                    if ($thePlugin != null && $thePlugin != '') activate_plugin($plugin);
                }
            }
        }
        else if ($action == 'deactivate')
        {
            include_once(ABSPATH . '/wp-admin/includes/plugin.php');

            foreach ($plugins as $idx => $plugin)
            {
                if ($plugin != $this->plugin_slug)
                {
                    $thePlugin = get_plugin_data($plugin);
                    if ($thePlugin != null && $thePlugin != '') deactivate_plugins($plugin);
                }
            }
        }
        else if ($action == 'delete')
        {
            include_once(ABSPATH . '/wp-admin/includes/plugin.php');
            if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
            include_once(ABSPATH . '/wp-admin/includes/file.php');
            include_once(ABSPATH . '/wp-admin/includes/template.php');
            include_once(ABSPATH . '/wp-admin/includes/misc.php');
            include_once(ABSPATH . '/wp-admin/includes/class-wp-upgrader.php');
            include_once(ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php');
            include_once(ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php');

            $wp_filesystem = $this->getWPFilesystem();
            if ($wp_filesystem == null) $wp_filesystem = new WP_Filesystem_Direct(null);
            $pluginUpgrader = new Plugin_Upgrader();

            foreach ($plugins as $idx => $plugin)
            {
                if ($plugin != $this->plugin_slug)
                {
                    $thePlugin = get_plugin_data($plugin);
                    if ($thePlugin != null && $thePlugin != '')
                    {
                        $tmp['plugin'] = $plugin;
                        $pluginUpgrader->delete_old_plugin(null, null, null, $tmp);
                    }
                }
            }
        }
        else
        {
            $information['status'] = 'FAIL';
        }

        if (!isset($information['status'])) $information['status'] = 'SUCCESS';
        $information['sync'] = $this->getSiteStats(array(), false);
        MainWPHelper::write($information);
    }

    function get_all_plugins()
    {
        $keyword = $_POST['keyword'];
        $status = $_POST['status'];
        $rslt = $this->get_all_plugins_int(true, $keyword, $status);

        MainWPHelper::write($rslt);
    }

    function get_all_plugins_int($filter, $keyword = '', $status = '')
    {
        if (!function_exists('get_plugins'))
        {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $rslt = array();
        $plugins = get_plugins(); //todo: deprecated, use wp_get_plugins
        if (is_array($plugins))
        {
            $active_plugins = get_option('active_plugins');

            foreach ($plugins as $pluginslug => $plugin)
            {
                if ($pluginslug == $this->plugin_slug) continue;

                $out = array();
                $out['name'] = $plugin['Name'];
                $out['slug'] = $pluginslug;
                $out['description'] = $plugin['Description'];
                $out['version'] = $plugin['Version'];
                $out['active'] = (is_array($active_plugins) && in_array($pluginslug, $active_plugins)) ? 1 : 0;
                if (!$filter)
                {
                    $rslt[] = $out;
                }
                else if ($out['active'] == (($status == 'active') ? 1 : 0))
                {
                    if ($keyword == '' || stristr($out['name'], $keyword)) $rslt[] = $out;
                }
            }
        }

        return $rslt;
    }

    function get_all_users()
    {
        $roles = explode(',', $_POST['role']);
        $allusers = array();
        if (is_array($roles))
        {
            foreach ($roles as $role)
            {
                $new_users = get_users('role=' . $role);
                //            $allusers[$role] = array();
                foreach ($new_users as $new_user)
                {
                    $usr = array();
                    $usr['id'] = $new_user->ID;
                    $usr['login'] = $new_user->user_login;
                    $usr['nicename'] = $new_user->user_nicename;
                    $usr['email'] = $new_user->user_email;
                    $usr['registered'] = $new_user->user_registered;
                    $usr['status'] = $new_user->user_status;
                    $usr['display_name'] = $new_user->display_name;
                    $usr['role'] = $role;
                    $usr['post_count'] = count_user_posts($new_user->ID);
                    $usr['avatar'] = get_avatar($new_user->ID, 32);
                    $allusers[] = $usr;
                }
            }
        }

        MainWPHelper::write($allusers);
    }

    function get_all_users_int()
    {
        $allusers = array();

        $new_users = get_users();
        foreach ($new_users as $new_user)
        {
            $usr = array();
            $usr['id'] = $new_user->ID;
            $usr['login'] = $new_user->user_login;
            $usr['nicename'] = $new_user->user_nicename;
            $usr['email'] = $new_user->user_email;
            $usr['registered'] = $new_user->user_registered;
            $usr['status'] = $new_user->user_status;
            $usr['display_name'] = $new_user->display_name;
            $userdata = get_userdata($new_user->ID);
            $user_roles = $userdata->roles;
            $user_role = array_shift($user_roles);
            $usr['role'] = $user_role;
            $usr['post_count'] = count_user_posts($new_user->ID);
            $allusers[] = $usr;
        }

        return $allusers;
    }


    function search_users()
    {
        $columns = explode(',', $_POST['search_columns']);
        $allusers = array();
        $exclude = array();

        foreach ($columns as $col)
        {
            if (empty($col))
                continue;

            $user_query = new WP_User_Query(array('search' => $_POST['search'],
                'fields' => 'all_with_meta',
                'search_columns' => array($col),
                'query_orderby' => array($col),
                'exclude' => $exclude));
            foreach ($user_query->results as $new_user)
            {
                $exclude[] = $new_user->ID;
                $usr = array();
                $usr['id'] = $new_user->ID;
                $usr['login'] = $new_user->user_login;
                $usr['nicename'] = $new_user->user_nicename;
                $usr['email'] = $new_user->user_email;
                $usr['registered'] = $new_user->user_registered;
                $usr['status'] = $new_user->user_status;
                $usr['display_name'] = $new_user->display_name;
                $usr['role'] = $new_user->wp_capabilities;
                $usr['post_count'] = count_user_posts($new_user->ID);
                $allusers[] = $usr;
            }

        }

        MainWPHelper::write($allusers);
    }

//Show stats without login - only allowed while no account is added yet
    function getSiteStatsNoAuth($information = array())
    {
        if (get_option('mainwp_child_pubkey'))
        {
            MainWPHelper::error('This site already contains a link - please disable and enable the MainWP plugin.');
        }

        global $wp_version;
        $information['wpversion'] = $wp_version;
        MainWPHelper::write($information);
    }

    //Deactivating the plugin
    function deactivate()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        deactivate_plugins($this->plugin_slug, true);
        $information = array();
        if (is_plugin_active($this->plugin_slug))
        {
            MainWPHelper::error('Plugin still active');
        }
        $information['deactivated'] = true;
        MainWPHelper::write($information);
    }

    function activation()
    {
        if (get_option('_sicknetwork_pubkey') !== false)
        {
            $options = array('sicknetwork_auth' => 'mainwp_child_auth',
                'sicknetwork_clone_sites' => 'mainwp_child_clone_sites',
                '_sicknetwork_uniqueId' => 'mainwp_child_uniqueId',
                '_sicknetwork_pluginDir' => 'mainwp_child_pluginDir',
                '_sicknetwork_htaccess_set' => 'mainwp_child_htaccess_set',
                '_sicknetwork_fix_htaccess' => 'mainwp_child_fix_htaccess',
                '_sicknetwork_pubkey' => 'mainwp_child_pubkey',
                '_sicknetwork_server' => 'mainwp_child_server',
                '_sicknetwork_nonce' => 'mainwp_child_nonce',
                '_sicknetwork_nossl' => 'mainwp_child_nossl',
                '_sicknetwork_nossl_key' => 'mainwp_child_nossl_key',
                '_sicknetwork_remove_wp_version' => 'mainwp_child_remove_wp_version',
                '_sicknetwork_remove_rsd' => 'mainwp_child_remove_rsd',
                '_sicknetwork_remove_wlw' => 'mainwp_child_remove_wlw',
                '_sicknetwork_remove_core_updates' => 'mainwp_child_remove_core_updates',
                '_sicknetwork_remove_plugin_updates' => 'mainwp_child_remove_plugin_updates',
                '_sicknetwork_remove_theme_updates' => 'mainwp_child_remove_theme_updates',
                '_sicknetwork_remove_php_reporting' => 'mainwp_child_remove_php_reporting',
                '_sicknetwork_remove_scripts_version' => 'mainwp_child_remove_scripts_version',
                '_sicknetwork_remove_styles_version' => 'mainwp_child_remove_styles_version',
                '_sicknetwork_clone_permalink' => 'mainwp_child_clone_permalink',
                '_sicknetwork_click_data' => 'mainwp_child_click_data');

            foreach ($options as $old => $new)
            {
                if (get_option($old) !== false) update_option($new, get_option($old));
            }
        }
        else
        {
            $to_delete = array('mainwp_child_pubkey', 'mainwp_child_nonce', 'mainwp_child_nossl', 'mainwp_child_nossl_key', 'mainwp_child_uniqueId');
            foreach ($to_delete as $delete)
            {
                if (get_option($delete))
                {
                    delete_option($delete);
                }
            }
        }
    }

    function deactivation()
    {
        $to_delete = array('mainwp_child_pubkey', 'mainwp_child_nonce', 'mainwp_child_nossl', 'mainwp_child_nossl_key', 'mainwp_child_remove_styles_version', 'mainwp_child_remove_scripts_version', 'mainwp_child_remove_php_reporting', 'mainwp_child_remove_theme_updates', 'mainwp_child_remove_plugin_updates', 'mainwp_child_remove_core_updates', 'mainwp_child_remove_wlw', 'mainwp_child_remove_rsd', 'mainwp_child_remove_wp_version', 'mainwp_child_server');
        foreach ($to_delete as $delete)
        {
            if (get_option($delete))
            {
                delete_option($delete);
            }
        }
    }

    function getWPFilesystem()
    {
        global $wp_filesystem;

        if (empty($wp_filesystem))
        {
            ob_start();
            if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
            if (file_exists(ABSPATH . '/wp-admin/includes/template.php')) include_once(ABSPATH . '/wp-admin/includes/template.php');
            $creds = request_filesystem_credentials('test', $type = '', $error = false, $context = false, $extra_fields = null);
            ob_end_clean();
            WP_Filesystem($creds);
        }

        if (empty($wp_filesystem))
        {
            MainWPHelper::error($this->FTP_ERROR);
        }
        else if (is_wp_error($wp_filesystem->errors))
        {
            $errorCodes = $wp_filesystem->errors->get_error_codes();
            if (!empty($errorCodes))
            {
                MainWPHelper::error('Wordpress Filesystem error: ' . $wp_filesystem->errors->get_error_message());
            }
        }

        return $wp_filesystem;
    }

    function getTotalFileSize($directory = WP_CONTENT_DIR)
    {
        if (false && function_exists('popen'))
        {
            $popenHandle = @popen('du -s ' . $directory . ' --exclude "' . str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/uploads/mainwp"', 'r');
            if (gettype($popenHandle) == 'resource')
            {
                $size = @fread($popenHandle, 1024);
                @pclose($popenHandle);
                $size = substr($size, 0, strpos($size, "\t"));
                if (ctype_digit($size))
                {
                    return $size / 1024;
                }
            }
        }
        if (function_exists('shell_exec'))
        {
            $size = @shell_exec('du -s ' . $directory . ' --exclude "' . str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/uploads/mainwp"', 'r');
            if ($size != NULL)
            {
                $size = substr($size, 0, strpos($size, "\t"));
                if (ctype_digit($size))
                {
                    return $size / 1024;
                }
            }
        }
        if (class_exists('COM'))
        {
            $obj = new COM ('scripting.filesystemobject');

            if (is_object($obj))
            {
                $ref = $obj->getfolder($directory);

                $size = $ref->size;

                $obj = null;
                if (ctype_digit($size))
                {
                    return $size / 1024;
                }
            }
        }

        function dirsize($dir)
        {
            $dirs = array($dir);
            $size = 0;
            while (isset ($dirs[0]))
            {
                $path = array_shift($dirs);
                if (stristr($path, WP_CONTENT_DIR . '/uploads/mainwp')) continue;
                foreach (glob($path . '/*') AS $next)
                {
                    if (is_dir($next))
                    {
                        $dirs[] = $next;
                    }
                    else
                    {
                        $fs = filesize($next);
                        $size += $fs;
                    }
                }
            }
            return $size / 1024 / 1024;
        }

        return dirsize($directory);
    }
}

?>