<?php

class MainWPServerInformation
{
    public static function render()
    {
        ?>
        <br />
        <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
            <thead>
            <tr>
                <th scope="col" class="manage-column sorted" style=""><span><?php _e('Server Configuration','mainwp'); ?></span></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Suggested Value','mainwp'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Value','mainwp'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Status','mainwp'); ?></th>
            </tr>
            </thead>

            <tbody id="the-sites-list" class="list:sites">
                <?php
                self::renderRow('WordPress Version', '>=', '3.4', 'getWordpressVersion');
                self::renderRow('PHP Version', '>=', '5.2.4', 'getPHPVersion');
                self::renderRow('MySQL Version', '>=', '5.0', 'getMySQLVersion');
                self::renderRow('PHP Max Execution Time', '>=', '30', 'getMaxExecutionTime', 'seconds', '=', '0');
                self::renderRow('PHP Upload Max Filesize', '>=', '2M', 'getUploadMaxFilesize', '(2MB+ best for upload of big plugins)');
                self::renderRow('PHP Post Max Size', '>=', '2M', 'getPostMaxSize', '(2MB+ best for upload of big plugins)');
//                            self::renderRow('PHP Memory Limit', '>=', '128M', 'getPHPMemoryLimit', '(256M+ best for big backups)');
                self::renderRow('PCRE Backtracking Limit', '>=', '10000', 'getOutputBufferSize');
                self::renderRow('SSL Extension Enabled', '=', true, 'getSSLSupport');
                ?>
            </tbody>
        </table>
        <br />
        <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
            <thead>
            <tr>
                <th scope="col" class="manage-column sorted" style=""><span><?php _e('Directory name','mainwp'); ?></span></th>
                <th scope="col" class="manage-column sorted" style=""><span><?php _e('Path','mainwp'); ?></span></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Check','mainwp'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Result','mainwp'); ?></th>
                <th scope="col" class="manage-column column-posts" style=""><?php _e('Status','mainwp'); ?></th>
            </tr>
            </thead>

            <tbody id="the-sites-list" class="list:sites">
                <?php
                self::checkDirectoryMainWPDirectory();
                ?>
            </tbody>
        </table>
        <br/>
        <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" class="manage-column sorted" style=""><span><?php _e('Server Info','mainwp'); ?></span></th>
                <th scope="col" class="manage-column column-posts" style=""><span><?php _e('Value','mainwp'); ?></span></th>
            </tr>
        </thead>
            <tbody id="the-sites-list" class="list:sites">
              <tr><td><?php _e('WordPress Root Directory','mainwp'); ?></td><td><?php self::getWPRoot(); ?></td></tr>
              <tr><td><?php _e('Server Name','mainwp'); ?></td><td><?php self::getSeverName(); ?></td></tr>
              <tr><td><?php _e('Server Sofware','mainwp'); ?></td><td><?php self::getServerSoftware(); ?></td></tr>
              <tr><td><?php _e('Operating System','mainwp'); ?></td><td><?php self::getOS(); ?></td></tr>
              <tr><td><?php _e('Architecture','mainwp'); ?></td><td><?php self::getArchitecture(); ?></td></tr>
              <tr><td><?php _e('Server IP','mainwp'); ?></td><td><?php self::getServerIP(); ?></td></tr>
              <tr><td><?php _e('Server Protocol','mainwp'); ?></td><td><?php self::getServerProtocol(); ?></td></tr>
              <tr><td><?php _e('HTTP Host','mainwp'); ?></td><td><?php self::getHTTPHost(); ?></td></tr>
              <tr><td><?php _e('Server Admin','mainwp'); ?></td><td><?php self::getServerAdmin(); ?></td></tr>
              <tr><td><?php _e('Server Port','mainwp'); ?></td><td><?php self::getServerPort(); ?></td></tr>
              <tr><td><?php _e('Getaway Interface','mainwp'); ?></td><td><?php self::getServerGetawayInterface(); ?></td></tr>
              <tr><td><?php _e('Memory Usage','mainwp'); ?></td><td><?php self::memoryUsage(); ?></td></tr>
              <tr><td><?php _e('HTTPS','mainwp'); ?></td><td><?php self::getHTTPS(); ?></td></tr>
              <tr><td><?php _e('User Agent','mainwp'); ?></td><td><?php self::getUserAgent(); ?></td></tr>
              <tr><td><?php _e('Complete URL','mainwp'); ?></td><td><?php self::getCompleteURL(); ?></td></tr>
              <tr><td><?php _e('Request Method','mainwp'); ?></td><td><?php self::getServerRequestMethod(); ?></td></tr>
              <tr><td><?php _e('Request Time','mainwp'); ?></td><td><?php self::getServerRequestTime(); ?></td></tr>
              <tr><td><?php _e('Query String','mainwp'); ?></td><td><?php self::getServerQueryString(); ?></td></tr>
              <tr><td><?php _e('Accept Content','mainwp'); ?></td><td><?php self::getServerHTTPAccept(); ?></td></tr>
              <tr><td><?php _e('Accept-Charset Content','mainwp'); ?></td><td><?php self::getServerAcceptCharset(); ?></td></tr>
              <tr><td><?php _e('Currently Executing Script Pathname','mainwp'); ?></td><td><?php self::getScriptFileName(); ?></td></tr>
              <tr><td><?php _e('Server Signature','mainwp'); ?></td><td><?php self::getServerSignature(); ?></td></tr>
              <tr><td><?php _e('Currently Executing Script','mainwp'); ?></td><td><?php self::getCurrentlyExecutingScript(); ?></td></tr>
              <tr><td><?php _e('Path Translated','mainwp'); ?></td><td><?php self::getServerPathTranslated(); ?></td></tr>
              <tr><td><?php _e('Current Script Path','mainwp'); ?></td><td><?php self::getScriptName(); ?></td></tr>
              <tr><td><?php _e('Current Page URI','mainwp'); ?></td><td><?php self::getCurrentPageURI(); ?></td></tr>
              <tr><td><?php _e('Remote Address','mainwp'); ?></td><td><?php self::getRemoteAddress(); ?></td></tr>
              <tr><td><?php _e('Remote Host','mainwp'); ?></td><td><?php self::getRemoteHost(); ?></td></tr>
              <tr><td><?php _e('Remote Port','mainwp'); ?></td><td><?php self::getRemotePort(); ?></td></tr>
              <tr><td><?php _e('PHP Safe Mode','mainwp'); ?></td><td><?php self::getPHPSafeMode(); ?></td></tr>
              <tr><td><?php _e('PHP Allow URL fopen','mainwp'); ?></td><td><?php self::getPHPAllowUrlFopen(); ?></td></tr>
              <tr><td><?php _e('PHP Exif Support','mainwp'); ?></td><td><?php self::getPHPExif(); ?></td></tr>
              <tr><td><?php _e('PHP IPTC Support','mainwp'); ?></td><td><?php self::getPHPIPTC(); ?></td></tr>
              <tr><td><?php _e('PHP XML Support','mainwp'); ?></td><td><?php self::getPHPXML(); ?></td></tr>
              <tr><td><?php _e('SQL Mode','mainwp'); ?></td><td><?php self::getSQLMode(); ?></td></tr>
            </tbody>
        </table>
        <br />
    <?php
    }

    public static function renderCron()
    {
        $cron_array = _get_cron_array();
        $schedules = wp_get_schedules();
        ?>
    <table id="mainwp-table" class="wp-list-table widefat" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" class="manage-column sorted" style=""><span><?php _e('Next due','mainwp'); ?></span></th>
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
                    <tr><td><?php echo MainWPHelper::formatTimestamp(MainWPHelper::getTimestamp($time)); ?></td><td><?php echo $schedules[$schedule['schedule']]['display'];?> </td><td><?php echo $hook; ?></td></tr>
                    <?php
                }
            }
        }
        ?>
        </tbody>
    </table>
        <?php
    }

    protected static function checkDirectoryMainWPDirectory()
    {
        $dirs = MainWPHelper::getMainWPDir();
        $path = $dirs[0];

        if (!is_dir(dirname($path)))
        {
            return self::renderDirectoryRow('MainWP upload directory', $path, 'Writable', 'Directory not found', false);
        }

        $hasWPFileSystem = MainWPHelper::getWPFilesystem();
        global $wp_filesystem;

        if ($hasWPFileSystem && !empty($wp_filesystem))
        {
            if (!$wp_filesystem->is_writable($path))
            {
                return self::renderDirectoryRow('MainWP upload directory', $path, 'Writable', 'Directory not writable', false);
            }
        }
        else
        {
            if (!is_writable($path))
            {
                return self::renderDirectoryRow('MainWP upload directory', $path, 'Writable', 'Directory not writable', false);
            }
        }

        return self::renderDirectoryRow('MainWP upload directory', $path, 'Writable', '/', true);
    }

    protected static function renderDirectoryRow($pName, $pDirectory, $pCheck, $pResult, $pPassed)
    {
        ?>
    <tr>
        <td><?php echo $pName; ?></td>
        <td><?php echo $pDirectory; ?></td>
        <td><?php echo $pCheck; ?></td>
        <td><?php echo $pResult; ?></td>
        <td><?php echo ($pPassed ? '<span class="mainwp-pass">Pass</span>' : '<span class="mainwp-warning">Warning</span>'); ?></td>
    </tr>
    <?php
      return true;
    }

    protected static function renderRow($pConfig, $pCompare, $pVersion, $pGetter, $pExtraText = '', $pExtraCompare = null, $pExtraVersion = null)
    {
        $currentVersion = call_user_func(array('MainWPServerInformation', $pGetter));

        ?>
    <tr>
        <td><?php echo $pConfig; ?></td>
        <td><?php echo $pCompare; ?>  <?php echo ($pVersion === true ? 'true' : $pVersion) . ' ' . $pExtraText; ?></td>
        <td><?php echo ($currentVersion === true ? 'true' : $currentVersion); ?></td>
        <td><?php echo (version_compare($currentVersion, $pVersion, $pCompare) || (($pExtraCompare != null) && version_compare($currentVersion, $pExtraVersion, $pExtraCompare)) ? '<span class="mainwp-pass">Pass</span>' : '<span class="mainwp-warning">Warning</span>'); ?></td>
    </tr>
    <?php
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

}

