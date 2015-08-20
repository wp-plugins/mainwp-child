<?php

class MainWPChildServerInformation
{
    public static function init()
    {
        add_action('wp_ajax_mainwp-child_dismiss_warnings', array('MainWPChildServerInformation', 'dismissWarnings'));
    }

    public static function dismissWarnings()
    {
        if (isset($_POST['what']))
        {
            $dismissWarnings = get_option('mainwp_child_dismiss_warnings');
            if (!is_array($dismissWarnings)) $dismissWarnings = array();

            if ($_POST['what'] == 'conflict')
            {
                $dismissWarnings['conflicts'] = self::getConflicts();
            }
            else if ($_POST['what'] == 'warning')
            {
                $dismissWarnings['warnings'] = self::getWarnings();
            }

            MainWPHelper::update_option('mainwp_child_dismiss_warnings', $dismissWarnings);
        }
    }

    public static function showWarnings()
    {
        if (stristr($_SERVER["REQUEST_URI"], 'MainWPChildServerInformation')) return;

        $conflicts = self::getConflicts();
        $warnings = self::getWarnings();

        $dismissWarnings = get_option('mainwp_child_dismiss_warnings');
        if (!is_array($dismissWarnings)) $dismissWarnings = array();

        if (isset($dismissWarnings['warnings']) && $dismissWarnings['warnings'] >= $warnings) $warnings = 0;
        if (isset($dismissWarnings['conflicts']) && MainWPHelper::containsAll($dismissWarnings['conflicts'], $conflicts)) $conflicts = array();

        if ($warnings == 0 && count($conflicts) == 0) return;

        if ($warnings > 0)
        {
            $dismissWarnings['warnings'] = 0;
        }

        if (count($conflicts) > 0)
        {
            $dismissWarnings['conflicts'] = array();
        }
        MainWPHelper::update_option('mainwp_child_dismiss_warnings', $dismissWarnings);

        $itheme_ext_activated = (get_option('mainwp_ithemes_ext_activated') == 'Y') ? true : false;
        if ($itheme_ext_activated) {
            foreach($conflicts as $key => $cf) {
                if ($cf === "iThemes Security") {
                    unset($conflicts[$key]);
                }
            }
            if ($warnings == 0 && count($conflicts) == 0) return;
        }

?>
    <script language="javascript">
        dismiss_warnings = function(pElement, pAction) {
            var table = jQuery(pElement.parents('table')[0]);
            pElement.parents('tr')[0].remove();
            if (table.find('tr').length == 0)
            {
                jQuery('#mainwp-child_server_warnings').hide();
            }

            var data = {
                action:'mainwp-child_dismiss_warnings',
                what: pAction
            };

            jQuery.ajax({
                type:"POST",
                url: ajaxurl,
                data: data,
                success: function(resp) { },
                error: function() { },
                dataType: 'json'});

            return false;
        };
        jQuery(document).on('click', '#mainwp-child-connect-warning-dismiss', function() { return dismiss_warnings(jQuery(this), 'warning'); });
        jQuery(document).on('click', '#mainwp-child-all-pages-warning-dismiss', function() { return dismiss_warnings(jQuery(this), 'conflict'); });
    </script>
    <style type="text/css">
    .mainwp-child_info-box-red-warning {
    background-color: rgba(187, 114, 57, 0.2) !important;
    border-bottom: 4px solid #bb7239 !important;
    border-top: 1px solid #bb7239 !important;
    border-left: 1px solid #bb7239 !important;
    border-right: 1px solid #bb7239 !important;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
    margin: 1em 0 !important;

    background-image: url('<?php echo plugins_url('images/mainwp-icon-orange.png', dirname(__FILE__)); ?>') !important;
    background-position: 1.5em 50% !important;
    background-repeat: no-repeat !important;
    background-size: 30px !important;
    }
    .mainwp-child_info-box-red-warning table {
        background-color: rgba(187, 114, 57, 0) !important;
        border: 0px;
        padding-left: 4.5em;
        background-position: 1.5em 50% !important;
        background-repeat: no-repeat !important;
        background-size: 30px !important;
    }
     </style>

        <div class="updated mainwp-child_info-box-red-warning" id="mainwp-child_server_warnings">
            <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
                    <tbody id="the-sites-list" class="list:sites">
            <?php
            $warning = '';

            if ($warnings > 0)
            {
                $warning .= '<tr><td colspan="2">This site may not connect to your dashboard or may have other issues. Check your <a href="admin.php?page=MainWPChildServerInformation">MainWP Server Information page</a> to review and <a href="http://docs.mainwp.com/child-site-issues/">check here for more information on possible fixes</a></td><td style="text-align: right;"><a href="#" id="mainwp-child-connect-warning-dismiss">Dismiss</a></td></tr>';
            }

            if (count($conflicts) > 0) {
                $warning .= '<tr><td colspan="2">';
                if (count($conflicts) == 1)
                {
                    $warning .= '"' . $conflicts[0] . '" is';
                }
                else
                {
                    $warning .= '"' . join('", "', $conflicts) . '" are';
                }
                $warning .= ' installed on this site. This is known to have a potential conflict with MainWP functions. <a href="http://docs.mainwp.com/known-plugin-conflicts/">Please click this link for possible solutions</a></td><td style="text-align: right;"><a href="#" id="mainwp-child-all-pages-warning-dismiss">Dismiss</a></td></tr>';
            }

            echo $warning;
            ?>
                </tbody>
            </table>
          </div>
              <?php
    }

    public static function renderPage()
    {
        ?><div class="wrap">
        <h2><?php _e('Plugin Conflicts'); ?></h2>
        <br/><?php
        MainWPChildServerInformation::renderConflicts();
        ?><h2><?php _e('Server Information'); ?></h2><?php
        MainWPChildServerInformation::render();
        ?><h2><?php _e('Cron Schedules'); ?></h2><?php
        MainWPChildServerInformation::renderCron();
        ?><h2><?php _e('Error Log'); ?></h2><?php
        MainWPChildServerInformation::renderErrorLogPage();
        ?>
        </div>
        <?php
    }

    public static function getWarnings()
    {
        $i = 0;

        if (!self::check('>=', '3.4', 'getWordpressVersion')) $i++;
        if (!self::check('>=', '5.2.4', 'getPHPVersion')) $i++;
        if (!self::check('>=', '5.0', 'getMySQLVersion')) $i++;
        if (!self::check('>=', '30', 'getMaxExecutionTime', '=', '0')) $i++;
        if (!self::check('>=', '2M', 'getUploadMaxFilesize')) $i++;
        if (!self::check('>=', '2M', 'getPostMaxSize')) $i++;
        if (!self::check('>=', '10000', 'getOutputBufferSize')) $i++;
//        if (!self::check('=', true, 'getSSLSupport')) $i++;

        if (!self::checkDirectoryMainWPDirectory(false)) $i++;

        return $i;
    }

    public static function getConflicts()
    {
        global $mainWPChild;

        $pluginConflicts = array('Better WP Security',
        'iThemes Security',
        'Secure WordPress',
        'Wordpress Firewall',
        'Bad Behavior',
        'SpyderSpanker'
        );
        $conflicts = array();
        if (count($pluginConflicts) > 0)
        {
            $plugins = $mainWPChild->get_all_plugins_int(false);
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
        return $conflicts;
    }

    public static function renderConflicts()
    {               
        $conflicts = self::getConflicts();
        $branding_title = "MainWP";
        if (MainWPChildBranding::is_branding()) {
            $branding_title = MainWPChildBranding::get_branding();
        }
        
        if (count($conflicts) > 0)
        {
            $information['pluginConflicts'] = $conflicts;
            ?>
            <style type="text/css">
            .mainwp-child_info-box-warning {
            background-color: rgba(187, 114, 57, 0.2) !important;
            border-bottom: 4px solid #bb7239 !important;
            border-top: 1px solid #bb7239 !important;
            border-left: 1px solid #bb7239 !important;
            border-right: 1px solid #bb7239 !important;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
            <?php if (!MainWPChildBranding::is_branding()) { ?>
            padding-left: 4.5em;
            background-image: url('<?php echo plugins_url('images/mainwp-icon-orange.png', dirname(__FILE__)); ?>') !important;
            <?php } ?>
            background-position: 1.5em 50% !important;
            background-repeat: no-repeat !important;
            background-size: 30px !important;
            }
             </style>
        <table id="mainwp-table" class="wp-list-table widefat mainwp-child_info-box-warning" cellspacing="0">
            <tbody id="the-sites-list" class="list:sites">
                <tr><td colspan="2"><strong><?php echo count($conflicts); ?> plugin conflict<?php echo (count($conflicts) > 1 ? 's' : ''); ?> found</strong></td><td style="text-align: right;"></td></tr>
                <?php foreach ($conflicts as $conflict) { ?>
                <tr><td><strong><?php echo $conflict; ?></strong> is installed on this site. This plugin is known to have a potential conflict with <?php echo $branding_title; ?> functions. <a href="http://docs.mainwp.com/known-plugin-conflicts/">Please click this link for possible solutions</a></td></tr>
                <?php } ?>
            </tbody>
        </table>
            <?php
        }
        else
        {
            ?>
            <style type="text/css">
            .mainwp-child_info-box {
            background-color: rgba(127, 177, 0, 0.2) !important;
            border-bottom: 4px solid #7fb100 !important;
            border-top: 1px solid #7fb100 !important;
            border-left: 1px solid #7fb100 !important;
            border-right: 1px solid #7fb100 !important;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
            <?php if (!MainWPChildBranding::is_branding()) { ?>
            padding-left: 4.5em;
            background-image: url('<?php echo plugins_url('images/mainwp-icon.png', dirname(__FILE__)); ?>') !important;
            <?php } ?>
            background-position: 1.5em 50% !important;
            background-repeat: no-repeat !important;
            background-size: 30px !important;
            }
             </style>
        <table id="mainwp-table" class="wp-list-table widefat mainwp-child_info-box" cellspacing="0">
            <tbody id="the-sites-list" class="list:sites">
                <tr><td>No conflicts found.</td></td><td style="text-align: right;"><a href="#" id="mainwp-child-info-dismiss">Dismiss</a></td></tr>
            </tbody>
        </table>
            <?php
        }
        ?><br /><?php
    }

    protected static function getFileSystemMethod() {
        $fs = get_filesystem_method();
        return $fs;
    }

    protected static function getFileSystemMethodCheck() {
        $fsmethod = self::getFileSystemMethod();
        if ($fsmethod == 'direct') {
            echo '<span class="mainwp-pass"><i class="fa fa-check-circle"></i> Pass</span>';
        } else {
            echo '<span class="mainwp-warning"><i class="fa fa-exclamation-circle"></i> Warning</span>';
        }
    }

    public static function render()
    {
        $branding_title = "MainWP Child";
        if (MainWPChildBranding::is_branding()) {
            $branding_title = MainWPChildBranding::get_branding();
        }
        
        ?>
        <br />
        <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
            <thead>
            <tr>
                <th scope="col" class="manage-column column-posts mwp-not-generate-row" style="width: 1px;"><?php _e('','mainwp-child'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><span><?php _e('Server Configuration','mainwp-child'); ?></span></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Required Value','mainwp'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Value','mainwp'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Status','mainwp'); ?></th>
            </tr>
            </thead>

            <tbody id="the-sites-list" class="list:sites">
                <tr><td style="background: #333; color: #fff;" colspan="5"><?php echo strtoupper($branding_title);  ?></td></tr>
                <tr><td></td><td><?php echo $branding_title; ?> Version</td><td><?php echo self::getMainWPVersion(); ?></td><td><?php echo self::getCurrentVersion(); ?></td><td><?php echo self::getMainWPVersionCheck(); ?></td></tr>
                <?php
                self::checkDirectoryMainWPDirectory();
                ?><tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('WORDPRESS','mainwp-child'); ?></td></tr><?php
                self::renderRow('WordPress Version', '>=', '3.4', 'getWordpressVersion');
                ?><tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('PHP SETTINGS','mainwp-child'); ?></td></tr><?php
                self::renderRow('PHP Version', '>=', '5.3', 'getPHPVersion');                
                self::renderRow('PHP Max Execution Time', '>=', '30', 'getMaxExecutionTime', 'seconds', '=', '0');
                self::renderRow('PHP Upload Max Filesize', '>=', '2M', 'getUploadMaxFilesize', '(2MB+ best for upload of big plugins)', null, null, true);
                self::renderRow('PHP Post Max Size', '>=', '2M', 'getPostMaxSize', '(2MB+ best for upload of big plugins)', null, null, true);
                self::renderRow('PHP Memory Limit', '>=', '128M', 'getPHPMemoryLimit', '(256M+ best for big backups)' , null, null, true);
                self::renderRow('SSL Extension Enabled', '=', true, 'getSSLSupport');
                ?><tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('MISC','mainwp-child'); ?></td></tr><?php                
                self::renderRow('PCRE Backtracking Limit', '>=', '10000', 'getOutputBufferSize');
                ?><tr><td></td><td><?php _e('FileSystem Method','mainwp'); ?></td><td><?php echo '= ' . __('direct','mainwp'); ?></td><td><?php echo self::getFileSystemMethod(); ?></td><td><?php echo self::getFileSystemMethodCheck(); ?></td></tr><?php
                
                ?><tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('MySQL SETTINGS','mainwp-child'); ?></td></tr><?php
                self::renderRow('MySQL Version', '>=', '5.0', 'getMySQLVersion');
                ?><tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('BACKUP ARCHIVE INFORMATION','mainwp-child'); ?></td></tr><?php
                
                self::renderRow('ZipArchive enabled in PHP', '=', true, 'getZipArchiveEnabled');
                self::renderRow('Tar GZip supported', '=', true, 'getGZipEnabled');
                self::renderRow('Tar BZip2 supported', '=', true, 'getBZipEnabled');
            ?>
           
              <tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('SERVER INFORMATION','mainwp'); ?></td></tr>
              <tr><td ></td><td><?php _e('WordPress Root Directory','mainwp'); ?></td><td colspan="3"><?php self::getWPRoot(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server Name','mainwp'); ?></td><td colspan="3"><?php self::getSeverName(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server Sofware','mainwp'); ?></td><td colspan="3"><?php self::getServerSoftware(); ?></td></tr>
              <tr><td ></td><td><?php _e('Operating System','mainwp'); ?></td><td colspan="3"><?php self::getOS(); ?></td></tr>
              <tr><td ></td><td><?php _e('Architecture','mainwp'); ?></td><td colspan="3"><?php self::getArchitecture(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server IP','mainwp'); ?></td><td colspan="3"><?php self::getServerIP(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server Protocol','mainwp'); ?></td><td colspan="3"><?php self::getServerProtocol(); ?></td></tr>
              <tr><td ></td><td><?php _e('HTTP Host','mainwp'); ?></td><td colspan="3"><?php self::getHTTPHost(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server Admin','mainwp'); ?></td><td colspan="3"><?php self::getServerAdmin(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server Port','mainwp'); ?></td><td colspan="3"><?php self::getServerPort(); ?></td></tr>
              <tr><td ></td><td><?php _e('Getaway Interface','mainwp'); ?></td><td colspan="3"><?php self::getServerGetawayInterface(); ?></td></tr>
              <tr><td ></td><td><?php _e('Memory Usage','mainwp'); ?></td><td colspan="3"><?php self::memoryUsage(); ?></td></tr>
              <tr><td ></td><td><?php _e('HTTPS','mainwp'); ?></td><td colspan="3"><?php self::getHTTPS(); ?></td></tr>
              <tr><td ></td><td><?php _e('User Agent','mainwp'); ?></td><td colspan="3"><?php self::getUserAgent(); ?></td></tr>
              <tr><td ></td><td><?php _e('Complete URL','mainwp'); ?></td><td colspan="3"><?php self::getCompleteURL(); ?></td></tr>
              <tr><td ></td><td><?php _e('Request Method','mainwp'); ?></td><td colspan="3"><?php self::getServerRequestMethod(); ?></td></tr>
              <tr><td ></td><td><?php _e('Request Time','mainwp'); ?></td><td colspan="3"><?php self::getServerRequestTime(); ?></td></tr>
              <tr><td ></td><td><?php _e('Query String','mainwp'); ?></td><td colspan="3"><?php self::getServerQueryString(); ?></td></tr>
              <tr><td ></td><td><?php _e('Accept Content','mainwp'); ?></td><td colspan="3"><?php self::getServerHTTPAccept(); ?></td></tr>
              <tr><td ></td><td><?php _e('Accept-Charset Content','mainwp'); ?></td><td colspan="3"><?php self::getServerAcceptCharset(); ?></td></tr>
              <tr><td ></td><td><?php _e('Currently Executing Script Pathname','mainwp'); ?></td><td colspan="3"><?php self::getScriptFileName(); ?></td></tr>
              <tr><td ></td><td><?php _e('Server Signature','mainwp'); ?></td><td colspan="3"><?php self::getServerSignature(); ?></td></tr>
              <tr><td ></td><td><?php _e('Currently Executing Script','mainwp'); ?></td><td colspan="3"><?php self::getCurrentlyExecutingScript(); ?></td></tr>
              <tr><td ></td><td><?php _e('Path Translated','mainwp'); ?></td><td colspan="3"><?php self::getServerPathTranslated(); ?></td></tr>
              <tr><td ></td><td><?php _e('Current Script Path','mainwp'); ?></td><td colspan="3"><?php self::getScriptName(); ?></td></tr>
              <tr><td ></td><td><?php _e('Current Page URI','mainwp'); ?></td><td colspan="3"><?php self::getCurrentPageURI(); ?></td></tr>
              <tr><td ></td><td><?php _e('Remote Address','mainwp'); ?></td><td colspan="3"><?php self::getRemoteAddress(); ?></td></tr>
              <tr><td ></td><td><?php _e('Remote Host','mainwp'); ?></td><td colspan="3"><?php self::getRemoteHost(); ?></td></tr>
              <tr><td ></td><td><?php _e('Remote Port','mainwp'); ?></td><td colspan="3"><?php self::getRemotePort(); ?></td></tr>
              <tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('PHP INFORMATION','mainwp'); ?></td></tr>
              <tr><td ></td><td><?php _e('PHP Safe Mode Disabled','mainwp'); ?></td><td colspan="3"><?php self::getPHPSafeMode(); ?></td></tr>
              <tr><td ></td><td><?php _e('PHP Allow URL fopen','mainwp'); ?></td><td colspan="3"><?php self::getPHPAllowUrlFopen(); ?></td></tr>
              <tr><td ></td><td><?php _e('PHP Exif Support','mainwp'); ?></td><td colspan="3"><?php self::getPHPExif(); ?></td></tr>
              <tr><td ></td><td><?php _e('PHP IPTC Support','mainwp'); ?></td><td colspan="3"><?php self::getPHPIPTC(); ?></td></tr>
              <tr><td ></td><td><?php _e('PHP XML Support','mainwp'); ?></td><td colspan="3"><?php self::getPHPXML(); ?></td></tr>
              <tr><td ></td><td><?php _e('PHP Disabled Functions','mainwp'); ?></td><td colspan="3"><?php self::mainwpRequiredFunctions(); ?></td></tr>
              <tr><td></td><td><?php _e('PHP Loaded Extensions','mainwp'); ?></td><td colspan="3" style="width: 73% !important;"><?php self::getLoadedPHPExtensions(); ?></td></tr>
                <tr><td style="background: #333; color: #fff;" colspan="5"><?php _e('MySQL INFORMATION','mainwp'); ?></td></tr>
                <tr><td ></td><td><?php _e('MySQL Mode','mainwp'); ?></td><td colspan="3"><?php self::getSQLMode(); ?></td></tr>
                <tr><td ></td><td><?php _e('MySQL Client Encoding','mainwp'); ?></td><td colspan="3"><?php echo defined( 'DB_CHARSET' ) ? DB_CHARSET : ''; ?></td></tr>
            </tbody>
        </table>
        <br />
    <?php
    }

    
    public static function mainwpRequiredFunctions() {
        //error_reporting(E_ALL);
        $disabled_functions = ini_get('disable_functions');
        if ($disabled_functions != '')
        {
              $arr = explode(',', $disabled_functions);
              sort($arr);
              for ($i=0; $i<count($arr); $i++)
              {
                  echo $arr[$i].', ';
              }
        }
        else
        {
              echo __('No functions disabled','mainwp');
        }
    }

    protected static function getLoadedPHPExtensions() {
      $extensions = get_loaded_extensions();
      sort( $extensions );
      echo  implode( ', ', $extensions);
    }
    protected static function getCurrentVersion() {
        $currentVersion = get_option('mainwp_child_plugin_version');
        return $currentVersion;
    }

    protected static function getMainwpVersion() {
            include_once(ABSPATH . '/wp-admin/includes/plugin-install.php');        
            $api = plugins_api('plugin_information', array('slug' => "mainwp-child", 'fields' => array('sections' => false), 'timeout' => 60)); 
            if (is_object($api) && isset($api->version)) {
                return $api->version;
            }
            return false;
    }
    
    protected static function getMainWPVersionCheck() {
        $current = get_option('mainwp_child_plugin_version');
        $latest = self::getMainwpVersion();
        if ($current == $latest) {
            echo '<span class="mainwp-pass"><i class="fa fa-check-circle"></i> Pass</span>';
        } else {
            echo '<span class="mainwp-warning"><i class="fa fa-exclamation-circle"></i> Warning</span>';
        }
    }
    
    public static function renderCron()
    {
        $cron_array = _get_cron_array();
        $schedules = wp_get_schedules();
        ?>
    <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-posts" style=""><span><?php _e('Next due','mainwp'); ?></span></th>
                <th scope="col" class="manage-column column-posts" style=""><span><?php _e('Schedule','mainwp'); ?></span></th>
                <th scope="col" class="manage-column column-posts" style=""><span><?php _e('Hook','mainwp'); ?></span></th>
            </tr>
        </thead>
        <tbody id="the-sites-list" class="list:sites">
        <?php
        foreach ($cron_array as $time => $cron)
        {
            foreach ($cron as $hook => $cron_info)
            {
                foreach ($cron_info as $key => $schedule )
                {
                    ?>
                    <tr><td><?php echo MainWPHelper::formatTimestamp(MainWPHelper::getTimestamp($time)); ?></td><td><?php echo ((isset($schedule['schedule']) && isset($schedules[$schedule['schedule']]) && isset($schedules[$schedule['schedule']]['display'])) ? $schedules[$schedule['schedule']]['display'] : '');?> </td><td><?php echo $hook; ?></td></tr>
                    <?php
                }
            }
        }
        ?>
        </tbody>
    </table>
        <?php
    }

    protected static function checkDirectoryMainWPDirectory($write = true)
    {       
        $branding_title = "MainWP";
        if (MainWPChildBranding::is_branding()) {
            $branding_title = MainWPChildBranding::get_branding();
        }
        $branding_title .= " upload directory"; 
        
        
        try
        {
            $dirs = MainWPHelper::getMainWPDir(null, false);
            $path = $dirs[0];
        }
        catch (Exception $e)
        {
            return self::renderDirectoryRow($branding_title, '', 'Writable', $e->getMessage(), false);
        }

        if (!is_dir(dirname($path)))
        {
            if ($write)
            {
                return self::renderDirectoryRow($branding_title, $path, 'Writable', 'Directory not found', false);
            }
            else return false;
        }

        $hasWPFileSystem = MainWPHelper::getWPFilesystem();
        global $wp_filesystem;

        if ($hasWPFileSystem && !empty($wp_filesystem))
        {
            if (!$wp_filesystem->is_writable($path))
            {
                if ($write)
                {
                return self::renderDirectoryRow($branding_title, $path, 'Writable', 'Directory not writable', false);
                }
                else return false;
            }
        }
        else
        {
            if (!is_writable($path))
            {
                if ($write)
                {
                    return self::renderDirectoryRow($branding_title, $path, 'Writable', 'Directory not writable', false);
                }
                else return false;
            }
        }

        if ($write)
        {
        return self::renderDirectoryRow($branding_title, $path, 'Writable', 'Writable', true);
    }
        else return true;
    }

    protected static function renderDirectoryRow($pName, $pDirectory, $pCheck, $pResult, $pPassed)
    {        
        ?>
    <tr>
        <td ></td>
        <td><?php echo $pName; ?><br/><?php echo (MainWPChildBranding::is_branding()) ? "" : $pDirectory; ?></td>
        <td><?php echo $pCheck; ?></td>
        <td><?php echo $pResult; ?></td>
        <td><?php echo ($pPassed ? '<span class="mainwp-pass"><i class="fa fa-check-circle"></i> Pass</span>' : '<span class="mainwp-warning"><i class="fa fa-exclamation-circle"></i> Warning</span>'); ?></td>
    </tr>
    <?php
      return true;
    }

    protected static function renderRow($pConfig, $pCompare, $pVersion, $pGetter, $pExtraText = '', $pExtraCompare = null, $pExtraVersion = null, $compareFilesize = false)
    {
        $currentVersion = call_user_func(array('MainWPChildServerInformation', $pGetter));

        ?>
    <tr>
        <td ></td>
        <td><?php echo $pConfig; ?></td>
        <td><?php echo $pCompare; ?>  <?php echo ($pVersion === true ? 'true' : $pVersion) . ' ' . $pExtraText; ?></td>
        <td><?php echo ($currentVersion === true ? 'true' : $currentVersion); ?></td>
         <?php if ($compareFilesize) { ?>        
        <td><?php echo (self::filesize_compare($currentVersion, $pVersion, $pCompare) ? '<span class="mainwp-pass"><i class="fa fa-check-circle"></i> Pass</span>' : '<span class="mainwp-warning"><i class="fa fa-exclamation-circle"></i> Warning</span>'); ?></td>
        <?php } else { ?>
        <td><?php echo (self::check($pCompare, $pVersion, $pGetter, $pExtraCompare, $pExtraVersion) ? '<span class="mainwp-pass"><i class="fa fa-check-circle"></i> Pass</span>' : '<span class="mainwp-warning"><i class="fa fa-exclamation-circle"></i> Warning</span>'); ?></td>
        <?php } ?>
    </tr>
    <?php
    }

    protected static function filesize_compare($value1, $value2, $operator = null) {        
        if (strpos($value1, "G") !== false) {
            $value1 = preg_replace('/[A-Za-z]/', '', $value1);
            $value1 = intval($value1) * 1024; // Megabyte number            
        } else {
            $value1 = preg_replace('/[A-Za-z]/', '', $value1); // Megabyte number
        }
        
        if (strpos($value2, "G") !== false) {
            $value2 = preg_replace('/[A-Za-z]/', '', $value2);
            $value2 = intval($value2) * 1024; // Megabyte number            
        } else {
            $value2 = preg_replace('/[A-Za-z]/', '', $value2); // Megabyte number
        }
        
        return version_compare($value1, $value2, $operator);
    }
    
    protected static function check($pCompare, $pVersion, $pGetter, $pExtraCompare = null, $pExtraVersion = null)
    {
        $currentVersion = call_user_func(array('MainWPChildServerInformation', $pGetter));

        return (version_compare($currentVersion, $pVersion, $pCompare) || (($pExtraCompare != null) && version_compare($currentVersion, $pExtraVersion, $pExtraCompare)));
    }

    protected static function getZipArchiveEnabled()
    {
        return class_exists('ZipArchive');
    }

    protected static function getGZipEnabled()
    {
        return function_exists('gzopen');
    }

    protected static function getBZipEnabled()
    {
        return function_exists('bzopen');
    }

    protected static function getWordpressVersion()
    {
        global $wp_version;
        return $wp_version;
    }

    protected static function getSSLSupport()
    {
        return extension_loaded('openssl');
    }

    protected static function getPHPVersion()
    {
        return phpversion();
    }

    protected static function getMaxExecutionTime()
    {
        return ini_get('max_execution_time');
    }

    protected static function getUploadMaxFilesize()
    {
        return ini_get('upload_max_filesize');
    }

    protected static function getPostMaxSize()
    {
        return ini_get('post_max_size');
    }

    protected static function getMySQLVersion()
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        return $wpdb->get_var('SHOW VARIABLES LIKE "version"', 1);
    }

    protected static function getPHPMemoryLimit()
    {
        return ini_get('memory_limit');
    }
    protected static function getOS()
    {
        echo PHP_OS;
    }
    protected static function getArchitecture()
    {
        echo (PHP_INT_SIZE * 8)?>&nbsp;bit <?php
    }
    protected static function memoryUsage()
    {
       if (function_exists('memory_get_usage')) $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MB');
       else $memory_usage = __('N/A');
       echo $memory_usage;
    }
    protected static function getOutputBufferSize()
    {
       return ini_get('pcre.backtrack_limit');
    }
    protected static function getPHPSafeMode()
    {
       if(ini_get('safe_mode')) $safe_mode = __('ON');
       else $safe_mode = __('OFF');
       echo $safe_mode;
    }
    protected static function getSQLMode()
    {
        global $wpdb;
        $mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
        if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
        if (empty($sql_mode)) $sql_mode = __('NOT SET');
        echo $sql_mode;
    }
    protected static function getPHPAllowUrlFopen()
    {
        if(ini_get('allow_url_fopen')) $allow_url_fopen = __('ON');
        else $allow_url_fopen = __('OFF');
        echo $allow_url_fopen;
    }
    protected static function getPHPExif()
    {
        if (is_callable('exif_read_data')) $exif = __('YES'). " ( V" . substr(phpversion('exif'),0,4) . ")" ;
        else $exif = __('NO');
        echo $exif;
    }
    protected static function getPHPIPTC()
    {
        if (is_callable('iptcparse')) $iptc = __('YES');
        else $iptc = __('NO');
        echo $iptc;
    }
     protected static function getPHPXML()
    {
        if (is_callable('xml_parser_create')) $xml = __('YES');
        else $xml = __('NO');
        echo $xml;
    }

    // new

    protected static function getCurrentlyExecutingScript() {
        echo $_SERVER['PHP_SELF'];
    }

    protected static function getServerGetawayInterface() {
        echo $_SERVER['GATEWAY_INTERFACE'];
    }

    protected static function getServerIP() {
        echo $_SERVER['SERVER_ADDR'];
    }

    protected static function getSeverName() {
        echo $_SERVER['SERVER_NAME'];
    }

    protected static function getServerSoftware() {
        echo $_SERVER['SERVER_SOFTWARE'];
    }

    protected static function getServerProtocol() {
        echo $_SERVER['SERVER_PROTOCOL'];
    }

    protected static function getServerRequestMethod() {
        echo $_SERVER['REQUEST_METHOD'];
    }

    protected static function getServerRequestTime(){
        echo $_SERVER['REQUEST_TIME'];
    }

    protected static function getServerQueryString() {
        echo $_SERVER['QUERY_STRING'];
    }

    protected static function getServerHTTPAccept() {
        echo $_SERVER['HTTP_ACCEPT'];
    }

    protected static function getServerAcceptCharset() {
        if (!isset($_SERVER['HTTP_ACCEPT_CHARSET']) || ($_SERVER['HTTP_ACCEPT_CHARSET'] == '')) {
            echo __('N/A','mainwp');
        }
        else
        {
            echo $_SERVER['HTTP_ACCEPT_CHARSET'];
        }
    }

    protected static function getHTTPHost() {
        echo $_SERVER['HTTP_HOST'];
    }

    protected static function getCompleteURL() {
        echo $_SERVER['HTTP_REFERER'];
    }

    protected static function getUserAgent() {
        echo $_SERVER['HTTP_USER_AGENT'];
    }

    protected static function getHTTPS() {
        if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' ) {
            echo __('ON','mainwp') . ' - ' . $_SERVER['HTTPS'] ;
        }
        else {
            echo __('OFF','mainwp') ;
        }
    }

    protected static function getRemoteAddress() {
        echo $_SERVER['REMOTE_ADDR'];
    }

    protected static function getRemoteHost() {
        if (!isset($_SERVER['REMOTE_HOST']) || ($_SERVER['REMOTE_HOST'] == '')) {
            echo __('N/A','mainwp');
        }
        else {
            echo $_SERVER['REMOTE_HOST'] ;
        }
    }

    protected static function getRemotePort() {
        echo $_SERVER['REMOTE_PORT'];
    }

    protected static function getScriptFileName() {
        echo $_SERVER['SCRIPT_FILENAME'];
    }

    protected static function getServerAdmin() {
        echo $_SERVER['SERVER_ADMIN'];
    }

    protected static function getServerPort() {
        echo $_SERVER['SERVER_PORT'];
    }

    protected static function getServerSignature() {
        echo $_SERVER['SERVER_SIGNATURE'];
    }

    protected static function getServerPathTranslated() {
        if (!isset($_SERVER['PATH_TRANSLATED']) || ($_SERVER['PATH_TRANSLATED'] == '')) {
            echo __('N/A','mainwp') ;
        }
        else {
            echo $_SERVER['PATH_TRANSLATED'] ;
        }
    }

    protected static function getScriptName() {
        echo $_SERVER['SCRIPT_NAME'];
    }

    protected static function getCurrentPageURI() {
        echo $_SERVER['REQUEST_URI'];
    }
    protected static function getWPRoot() {
        echo ABSPATH ;
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;

     }


         /*
      *Plugin Name: Error Log Dashboard Widget
      *Plugin URI: http://wordpress.org/extend/plugins/error-log-dashboard-widget/
      *Description: Robust zero-configuration and low-memory way to keep an eye on error log.
      *Author: Andrey "Rarst" Savchenko
      *Author URI: http://www.rarst.net/
      *Version: 1.0.2
      *License: GPLv2 or later

      *Includes last_lines() function by phant0m, licensed under cc-wiki and GPLv2+
    */

     public static function renderErrorLogPage()
     {
        ?>
        <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
                <thead title="Click to Toggle" style="cursor: pointer;">
                    <tr>
                        <th scope="col" class="manage-column column-posts" style="width: 10%"><span><?php _e('Time','mainwp'); ?></span></th>
                        <th scope="col" class="manage-column column-posts" style=""><span><?php _e('Error','mainwp'); ?></span></th>
                    </tr>
                </thead>
                    <tbody class="list:sites" id="mainwp-error-log-table">
                        <?php self::renderErrorLog(); ?>
                    </tbody>
                </table>
        <?php
     }

     public static function renderErrorLog()
     {
        $log_errors = ini_get( 'log_errors' );
        if ( ! $log_errors )
         echo '<tr><td colspan="2">' . __( 'Error logging disabled.', 'mainwp' ) . '</td></tr>';

        $error_log = ini_get( 'error_log' );
        $logs      = apply_filters( 'error_log_mainwp_logs', array( $error_log ) );
        $count     = apply_filters( 'error_log_mainwp_lines', 10 );
        $lines     = array();

        foreach ( $logs as $log ) {

            if ( is_readable( $log ) )
                $lines = array_merge( $lines, self::last_lines( $log, $count ) );
        }

        $lines = array_map( 'trim', $lines );
        $lines = array_filter( $lines );

        if ( empty( $lines ) ) {

            echo '<tr><td colspan="2">' . __( 'MainWP is unable to find your error logs, please contact your host for server error logs.', 'mainwp' ) . '</td></tr>';

            return;
        }

        foreach ( $lines as $key => $line )
        {

            if ( false != strpos( $line, ']' ) )
                list( $time, $error ) = explode( ']', $line, 2 );
            else
                list( $time, $error ) = array( '', $line );

            $time        = trim( $time, '[]' );
            $error       = trim( $error );
            $lines[$key] = compact( 'time', 'error' );
        }

        if ( count( $error_log ) > 1 ) {

            uasort( $lines, array( __CLASS__, 'time_compare' ) );
            $lines = array_slice( $lines, 0, $count );
        }

        foreach ( $lines as $line ) {

            $error = esc_html( $line['error'] );
            $time  = esc_html( $line['time'] );

            if ( ! empty( $error ) )
                echo( "<tr><td>{$time}</td><td>{$error}</td></tr>" );
        }

     }
    static function time_compare( $a, $b ) {

       if ( $a == $b )
           return 0;

       return ( strtotime( $a['time'] ) > strtotime( $b['time'] ) ) ? - 1 : 1;
   }

   static function last_lines( $path, $line_count, $block_size = 512 ) {
       $lines = array();

       // we will always have a fragment of a non-complete line
       // keep this in here till we have our next entire line.
       $leftover = '';

       $fh = fopen( $path, 'r' );
       // go to the end of the file
       fseek( $fh, 0, SEEK_END );

       do {
           // need to know whether we can actually go back
           // $block_size bytes
           $can_read = $block_size;

           if ( ftell( $fh ) <= $block_size )
               $can_read = ftell( $fh );

           if ( empty( $can_read ) )
               break;

           // go back as many bytes as we can
           // read them to $data and then move the file pointer
           // back to where we were.
           fseek( $fh, - $can_read, SEEK_CUR );
           $data  = fread( $fh, $can_read );
           $data .= $leftover;
           fseek( $fh, - $can_read, SEEK_CUR );

           // split lines by \n. Then reverse them,
           // now the last line is most likely not a complete
           // line which is why we do not directly add it, but
           // append it to the data read the next time.
           $split_data = array_reverse( explode( "\n", $data ) );
           $new_lines  = array_slice( $split_data, 0, - 1 );
           $lines      = array_merge( $lines, $new_lines );
           $leftover   = $split_data[count( $split_data ) - 1];
       } while ( count( $lines ) < $line_count && ftell( $fh ) != 0 );

       if ( ftell( $fh ) == 0 )
           $lines[] = $leftover;

       fclose( $fh );
       // Usually, we will read too many lines, correct that here.
       return array_slice( $lines, 0, $line_count );
   }

   public static function renderWPConfig()
   {
       ?>
       <style>
           #mainwp-code-display code {
               background: none !important;
           }
       </style>
       <div class="postbox" id="mainwp-code-display">
           <h3 class="hndle" style="padding: 8px 12px; font-size: 14px;"><span>WP-Config.php</span></h3>
           <div style="padding: 1em;">
           <?php
               @show_source( ABSPATH . 'wp-config.php');
           ?>
           </div>
       </div>
       <?php
   }

   public static function renderhtaccess() {
        ?>
        <div class="postbox" id="mainwp-code-display">
            <h3 class="hndle" style="padding: 8px 12px; font-size: 14px;"><span>.htaccess</span></h3>
            <div style="padding: 1em;">
            <?php
                @show_source( ABSPATH . '.htaccess');
            ?>
            </div>
        </div>
        <?php
    }
}

