<?php
class MainWPBackup
{
    protected static $instance = null;
    protected $excludeZip;
    protected $zip;
    protected $zipArchiveFileCount;
    protected $zipArchiveSizeCount;
    protected $zipArchiveFileName;
    protected $file_descriptors;
    protected $loadFilesBeforeZip;

    protected $timeout;
    protected $lastRun;

    protected $archiver = null;

    protected function __construct()
    {

    }

    public static function get()
    {
        if (self::$instance == null)
        {
            self::$instance = new MainWPBackup();
        }
        return self::$instance;
    }

    /**
     * Create full backup
     */
    public function createFullBackup($excludes, $filePrefix = '', $addConfig = false, $includeCoreFiles = false, $file_descriptors = 0, $fileSuffix = false, $excludezip = false, $excludenonwp = false, $loadFilesBeforeZip = true, $ext = 'zip', $pid = false, $append = false)
    {
        $this->file_descriptors = $file_descriptors;
        $this->loadFilesBeforeZip = $loadFilesBeforeZip;

        $dirs = MainWPHelper::getMainWPDir('backup');
        $backupdir = $dirs[0];
        if (!defined('PCLZIP_TEMPORARY_DIR')) define('PCLZIP_TEMPORARY_DIR', $backupdir);

        if ($pid !== false)
        {
            $pid = trailingslashit($backupdir) . 'backup-' . $pid . '.pid';
        }



        //Verify if another backup is running, if so, return an error
        $files = glob($backupdir . '*.pid');
        foreach ($files as $file)
        {
            if (basename($file) == basename($pid)) continue;

            if ((time() - filemtime($file)) < 160)
            {
                MainWPHelper::error('Another backup process is running, try again later');
            }
        }

        $timestamp = time();
        if ($filePrefix != '') $filePrefix .= '-';

        if ($ext == 'zip')
        {
            $this->archiver = null;
            $ext = '.zip';
        }
        else
        {
            $this->archiver = new TarArchiver($this, $ext, $pid);
            $ext = $this->archiver->getExtension();
        }

//        throw new Exception('Test 1 2 : ' . print_r($append,1));
        if (($fileSuffix !== false) && !empty($fileSuffix))
        {
            $file = $fileSuffix . ($append === true ? '' : $ext); //Append already contains extension!
        }
        else
        {
            $file =  'backup-' . $filePrefix . $timestamp . $ext;
        }
        $filepath = $backupdir . $file;
        $fileurl = $file;

//        if (!$append)
//        {
//            if ($dh = opendir($backupdir))
//            {
//                while (($file = readdir($dh)) !== false)
//                {
//                    if ($file != '.' && $file != '..' && preg_match('/(.*).(zip|tar|tar.gz|tar.bz2|pid|done)$/', $file))
//                    {
//                        @unlink($backupdir . $file);
//                    }
//                }
//                closedir($dh);
//            }
//        }

        if (!$addConfig)
        {
            if (!in_array(str_replace(ABSPATH, '', WP_CONTENT_DIR), $excludes) && !in_array('wp-admin', $excludes) && !in_array(WPINC, $excludes))
            {
                $addConfig = true;
                $includeCoreFiles = true;
            }
        }

        $this->timeout = 20 * 60 * 60; /*20 minutes*/
        $mem =  '512M';
        @ini_set('memory_limit', $mem);
        @set_time_limit($this->timeout);
        @ini_set('max_execution_time', $this->timeout);

        if ($this->archiver != null)
        {
            $success = $this->archiver->createFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp, $append);
        }
        else if ($this->checkZipSupport())
        {
            $success = $this->createZipFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp);
        }
        else if ($this->checkZipConsole())
        {
            $success = $this->createZipConsoleFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp);
        }
        else
        {			
            $success = $this->createZipPclFullBackup2($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp);
        }

        return ($success) ? array(
            'timestamp' => $timestamp,
            'file' => $fileurl,
            'filesize' => filesize($filepath)
        ) : false;
    }

    public function zipFile($file, $archive)
    {
        $this->timeout = 20 * 60 * 60; /*20 minutes*/
        $mem =  '512M';
        @ini_set('memory_limit', $mem);
        @set_time_limit($this->timeout);
        @ini_set('max_execution_time', $this->timeout);

        if ($this->archiver != null)
        {
            $success = $this->archiver->zipFile($file, $archive);
        }
        else if ($this->checkZipSupport())
        {
            $success = $this->_zipFile($file, $archive);
        }
        else if ($this->checkZipConsole())
        {
            $success = $this->_zipFileConsole($file, $archive);
        }
        else
        {
            $success = $this->_zipFilePcl($file, $archive);
        }

        return $success;
    }

    function _zipFile($file, $archive)
    {
        $this->zip = new ZipArchive();
        $this->zipArchiveFileCount = 0;
        $this->zipArchiveSizeCount = 0;

        $zipRes = $this->zip->open($archive, ZipArchive::CREATE);
        if ($zipRes)
        {
            $this->addFileToZip($file, basename($file));

            return $this->zip->close();
        }

        return false;
    }

    function _zipFileConsole($file, $archive)
    {
        return false;
    }

    public function _zipFilePcl($file, $archive)
    {
        //Zip this backup folder..
        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php');
        $this->zip = new PclZip($archive);

        $error = false;
        if (($rslt = $this->zip->add($file, PCLZIP_OPT_REMOVE_PATH, dirname($file))) == 0)
        {
            $error = true;
        }

        return !$error;
    }

    /**
     * Check for default PHP zip support
     *
     * @return bool
     */
    public function checkZipSupport()
    {
        return class_exists('ZipArchive');
    }

    /**
     * Check if we could run zip on console
     *
     * @return bool
     */
    public function checkZipConsole()
    {
        return false;
//        return function_exists('system');
    }

    /**
     * Create full backup using default PHP zip library
     *
     * @param string $filepath File path to create
     * @return bool
     */
    public function createZipFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp)
    {
        $this->excludeZip = $excludezip;
        $this->zip = new ZipArchive();
        $this->zipArchiveFileCount = 0;
        $this->zipArchiveSizeCount = 0;
        $this->zipArchiveFileName = $filepath;
        $zipRes = $this->zip->open($filepath, ZipArchive::CREATE);
        if ($zipRes)
        {
            $nodes = glob(ABSPATH . '*');
            if (!$includeCoreFiles)
            {
                $coreFiles = array('favicon.ico', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-app.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php', 'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-register.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
                foreach ($nodes as $key => $node)
                {
                    if (MainWPHelper::startsWith($node, ABSPATH . WPINC))
                    {
                        unset($nodes[$key]);
                    }
                    else if (MainWPHelper::startsWith($node, ABSPATH . basename(admin_url(''))))
                    {
                        unset($nodes[$key]);
                    }
                    else
                    {
                        foreach ($coreFiles as $coreFile)
                        {
                            if ($node == ABSPATH . $coreFile) unset($nodes[$key]);
                        }
                    }
                }
                unset($coreFiles);
            }

            $this->createBackupDB(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');
			$this->addFileToZip(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql', basename(WP_CONTENT_DIR) . '/' . 'dbBackup.sql');
            if (file_exists(ABSPATH . '.htaccess')) $this->addFileToZip(ABSPATH . '.htaccess', 'mainwp-htaccess');

            foreach ($nodes as $node)
            {
                if ($excludenonwp && is_dir($node))
                {
                    if (!MainWPHelper::startsWith($node, WP_CONTENT_DIR) && !MainWPHelper::startsWith($node,  ABSPATH . 'wp-admin') && !MainWPHelper::startsWith($node, ABSPATH . WPINC)) continue;
                }

                if (!MainWPHelper::inExcludes($excludes, str_replace(ABSPATH, '', $node)))
                {
                    if (is_dir($node))
                    {
                        $this->zipAddDir($node, $excludes);
                    }
                    else if (is_file($node))
                    {
                        $this->addFileToZip($node, str_replace(ABSPATH, '', $node));
                    }
                }
            }


            if ($addConfig)
            {
                global $wpdb;
                $plugins = array();
                $dir = WP_CONTENT_DIR . '/plugins/';
                $fh = @opendir($dir);
                while ($entry = @readdir($fh))
                {
                    if (!@is_dir($dir . $entry)) continue;
                    if (($entry == '.') || ($entry == '..')) continue;
                    $plugins[] = $entry;
                }
                @closedir($fh);

                $themes = array();
                $dir = WP_CONTENT_DIR . '/themes/';
                $fh = @opendir($dir);
                while ($entry = @readdir($fh))
                {
                    if (!@is_dir($dir . $entry)) continue;
                    if (($entry == '.') || ($entry == '..')) continue;
                    $themes[] = $entry;
                }
                @closedir($fh);

                $string = base64_encode(serialize(array('siteurl' => get_option('siteurl'),
                                        'home' => get_option('home'),
                                        'abspath' => ABSPATH,
                                        'prefix' => $wpdb->prefix,
                                        'lang' => defined('WPLANG') ? WPLANG : '',
                                        'plugins' => $plugins,
                                        'themes' => $themes)));

                $this->addFileFromStringToZip('clone/config.txt', $string);
            }

            $return = $this->zip->close();
            @unlink(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');

            return true;
        }

        return false;
    }

    /**
     * Create full backup using pclZip library
     *
     * @param string $filepath File path to create
     * @return bool
     */
    public function createZipPclFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles)
    {
        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php');
        $this->zip = new PclZip($filepath);
        $nodes = glob(ABSPATH . '*');
        if (!$includeCoreFiles)
        {
            $coreFiles = array('favicon.ico', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-app.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php', 'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-register.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
            foreach ($nodes as $key => $node)
            {
                if (MainWPHelper::startsWith($node, ABSPATH . WPINC))
                {
                    unset($nodes[$key]);
                }
                else if (MainWPHelper::startsWith($node, ABSPATH . basename(admin_url(''))))
                {
                    unset($nodes[$key]);
                }
                else
                {
                    foreach ($coreFiles as $coreFile)
                    {
                        if ($node == ABSPATH . $coreFile) unset($nodes[$key]);
                    }
                }
            }
            unset($coreFiles);
        }

        $this->createBackupDB(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');		
        $error = false;
        if (($rslt = $this->zip->add(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql', PCLZIP_OPT_REMOVE_PATH, dirname($filepath), PCLZIP_OPT_ADD_PATH, basename(WP_CONTENT_DIR))) == 0) $error = true;

        @unlink(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');
        if (!$error)
        {
            foreach ($nodes as $node)
            {
                if ($excludes == null || !in_array(str_replace(ABSPATH, '', $node), $excludes))
                {
                    if (is_dir($node))
                    {
                        if (!$this->pclZipAddDir($node, $excludes))
                        {
                            $error = true;
                            break;
                        }
                    }
                    else if (is_file($node))
                    {
                        if (($rslt = $this->zip->add($node, PCLZIP_OPT_REMOVE_PATH, ABSPATH)) == 0)
                        {
                            $error = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($addConfig)
        {
            global $wpdb;
            $string = base64_encode(serialize(array('siteurl' => get_option('siteurl'),
                                            'home' => get_option('home'), 'abspath' => ABSPATH, 'prefix' => $wpdb->prefix, 'lang' => WPLANG)));

            $this->addFileFromStringToPCLZip('clone/config.txt', $string, $filepath);
        }

        if ($error)
        {
            @unlink($filepath);
            return false;
        }
        return true;
    }

    function copy_dir( $nodes, $excludes, $backupfolder, $excludenonwp, $root) {
        if (!is_array($nodes)) return;

        foreach ($nodes as $node)
        {
            if ($excludenonwp && is_dir($node))
            {
                if (!MainWPHelper::startsWith($node, WP_CONTENT_DIR) && !MainWPHelper::startsWith($node,  ABSPATH . 'wp-admin') && !MainWPHelper::startsWith($node, ABSPATH . WPINC)) continue;
            }

            if (!MainWPHelper::inExcludes($excludes, str_replace(ABSPATH, '', $node)))
            {
                if (is_dir($node))
                {
                    if( !file_exists( str_replace(ABSPATH, $backupfolder, $node) ) )
                               @mkdir ( str_replace(ABSPATH, $backupfolder, $node) );

                    $newnodes = glob($node . DIRECTORY_SEPARATOR . '*');
                    $this->copy_dir($newnodes, $excludes, $backupfolder, $excludenonwp, false);
                    unset($newnodes);
                }
                else if (is_file($node))
                {
                    if ($this->excludeZip && MainWPHelper::endsWith($node, '.zip')) continue;

                    @copy($node, str_replace(ABSPATH, $backupfolder, $node));
                }
            }
        }
    }

    public function createZipPclFullBackup2($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp)
    {
        global $classDir;
        //Create backup folder
        $backupFolder = dirname($filepath) . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR;
        @mkdir($backupFolder);

        //Create DB backup
        $this->createBackupDB($backupFolder . 'dbBackup.sql');
		
        //Copy installation to backup folder
        $nodes = glob(ABSPATH . '*');
        if (!$includeCoreFiles)
        {
            $coreFiles = array('favicon.ico', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-app.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php', 'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-register.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
            foreach ($nodes as $key => $node)
            {
                if (MainWPHelper::startsWith($node, ABSPATH . WPINC))
                {
                    unset($nodes[$key]);
                }
                else if (MainWPHelper::startsWith($node, ABSPATH . basename(admin_url(''))))
                {
                    unset($nodes[$key]);
                }
                else
                {
                    foreach ($coreFiles as $coreFile)
                    {
                        if ($node == ABSPATH . $coreFile) unset($nodes[$key]);
                    }
                }
            }
            unset($coreFiles);
        }
        $this->copy_dir($nodes, $excludes, $backupFolder, $excludenonwp, true);
		// to fix bug wrong folder
		@copy($backupFolder.'dbBackup.sql', $backupFolder . basename(WP_CONTENT_DIR) . '/dbBackup.sql');
		@unlink($backupFolder.'dbBackup.sql');
        unset($nodes);
		
        //Zip this backup folder..
        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php');
        $this->zip = new PclZip($filepath);
        $this->zip->create($backupFolder, PCLZIP_OPT_REMOVE_PATH, $backupFolder);
        if ($addConfig)
        {
            global $wpdb;
            $string = base64_encode(serialize(array('siteurl' => get_option('siteurl'),
                                            'home' => get_option('home'), 'abspath' => ABSPATH, 'prefix' => $wpdb->prefix, 'lang' => WPLANG)));

            $this->addFileFromStringToPCLZip('clone/config.txt', $string, $filepath);
        }
        //Remove backup folder
        MainWPHelper::delete_dir($backupFolder);
        return true;
    }

    /**
     * Recursive add directory for default PHP zip library
     */
    public function zipAddDir($path, $excludes)
    {
        $this->zip->addEmptyDir(str_replace(ABSPATH, '', $path));

        if (file_exists(rtrim($path, '/') . '/.htaccess')) $this->addFileToZip(rtrim($path, '/') . '/.htaccess', rtrim(str_replace(ABSPATH, '', $path), '/') . '/mainwp-htaccess');

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $path)
        {
            $name = $path->__toString();
            if ((basename($name) == '.') || (basename($name) == '..')) continue;

            if (!MainWPHelper::inExcludes($excludes, str_replace(ABSPATH, '', $name)))
            {
                if ($path->isDir())
                {
                    $this->zipAddDir($name, $excludes);
                }
                else
                {
                    $this->addFileToZip($name, str_replace(ABSPATH, '', $name));
                }
            }
            $name = null;
            unset($name);
        }

        $iterator = null;
        unset($iterator);

//        $nodes = glob(rtrim($path, '/') . '/*');
//        if (empty($nodes)) return true;
//
//        foreach ($nodes as $node)
//        {
//            if (!MainWPHelper::inExcludes($excludes, str_replace(ABSPATH, '', $node)))
//            {
//                if (is_dir($node))
//                {
//                    $this->zipAddDir($node, $excludes);
//                }
//                else if (is_file($node))
//                {
//                    $this->addFileToZip($node, str_replace(ABSPATH, '', $node));
//                }
//            }
//        }
    }

    public function pclZipAddDir($path, $excludes)
    {
        $error = false;
        $nodes = glob(rtrim($path, '/') . '/*');
        if (empty($nodes)) return true;

        foreach ($nodes as $node)
        {
            if ($excludes == null || !in_array(str_replace(ABSPATH, '', $node), $excludes))
            {
                if (is_dir($node))
                {
                    if (!$this->pclZipAddDir($node, $excludes))
                    {
                        $error = true;
                        break;
                    }
                }
                else if (is_file($node))
                {
                    if (($rslt = $this->zip->add($node, PCLZIP_OPT_REMOVE_PATH, ABSPATH)) == 0)
                    {
                        $error = true;
                        break;
                    }
                }
            }
        }
        return !$error;
    }

    function addFileFromStringToZip($file, $string)
    {
        return $this->zip->addFromString($file, $string);
    }

    public function addFileFromStringToPCLZip($file, $string, $filepath)
   	{
        $file = preg_replace("/(?:\.|\/)*(.*)/", "$1", $file);
   		$localpath = dirname($file);
   		$tmpfilename = dirname($filepath). '/' . basename($file);
   		if (false !== file_put_contents($tmpfilename, $string)) {
   			$this->zip->delete(PCLZIP_OPT_BY_NAME, $file);
   			$add = $this->zip->add($tmpfilename,
   				PCLZIP_OPT_REMOVE_PATH, dirname($filepath),
   				PCLZIP_OPT_ADD_PATH, $localpath);
   			unlink($tmpfilename);
   			if (!empty($add)) {
   				return true;
   			}
   		}
   		return false;
   	}

    protected $gcCnt = 0;
    protected $testContent;

    function addFileToZip($path, $zipEntryName)
    {
        if (time() - $this->lastRun > 20)
        {
            @set_time_limit($this->timeout);
            $this->lastRun = time();
        }

        if ($this->excludeZip && MainWPHelper::endsWith($path, '.zip')) return false;

        // this would fail with status ZIPARCHIVE::ER_OPEN
        // after certain number of files is added since
        // ZipArchive internally stores the file descriptors of all the
        // added files and only on close writes the contents to the ZIP file
        // see: http://bugs.php.net/bug.php?id=40494
        // and: http://pecl.php.net/bugs/bug.php?id=9443
        // return $zip->addFile( $path, $zipEntryName );

        $this->zipArchiveSizeCount += filesize($path);
        $this->gcCnt++;

        //5 mb limit!
        if (!$this->loadFilesBeforeZip || (filesize($path) > 5 * 1024 * 1024))
        {
            $this->zipArchiveFileCount++;
            $added = $this->zip->addFile($path, $zipEntryName);
        }
        else
        {
            $this->zipArchiveFileCount++;

            $this->testContent = file_get_contents($path);
            if ($this->testContent === false)
            {
                return false;
            }
            $added = $this->zip->addFromString($zipEntryName, $this->testContent);
        }

        if ($this->gcCnt > 20)
        {
            if (function_exists('gc_enable')) @gc_enable();
            if (function_exists('gc_collect_cycles')) @gc_collect_cycles();
            $this->gcCnt = 0;
        }

        //Over limits?
        if ((($this->file_descriptors > 0) && ($this->zipArchiveFileCount > $this->file_descriptors))) // || $this->zipArchiveSizeCount >= (31457280 * 2))
        {
            $this->zip->close();
            $this->zip = null;
            unset($this->zip);
            if (function_exists('gc_enable')) @gc_enable();
            if (function_exists('gc_collect_cycles')) @gc_collect_cycles();
            $this->zip = new ZipArchive();
            $this->zip->open($this->zipArchiveFileName);
            $this->zipArchiveFileCount = 0;
            $this->zipArchiveSizeCount = 0;
        }

        return $added;
    }

    public function createZipConsoleFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles, $excludezip, $excludenonwp)
    {
        // @TODO to work with 'zip' from system if PHP Zip library not available
        //system('zip');
        return false;
    }

    public function createBackupDB($filepath, $archiveExt = false, &$archiver = NULL)
    {
        $timeout = 20 * 60 * 60; //20minutes
        @set_time_limit($timeout);
        @ini_set('max_execution_time', $timeout);
        $mem =  '512M';
        @ini_set('memory_limit', $mem);

        $fh = fopen($filepath, 'w'); //or error;

        /** @var $wpdb wpdb */
        global $wpdb;

        //Get all the tables
        $tables_db = $wpdb->get_results('SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_N);
        foreach ($tables_db as $curr_table)
        {
            if ($archiver != NULL)
            {
                $archiver->updatePidFile();
            }

            $table = $curr_table[0];

            fwrite($fh, "\n\n" . 'DROP TABLE IF EXISTS ' . $table . ';');
            $table_create = $wpdb->get_row('SHOW CREATE TABLE ' . $table, ARRAY_N);
            fwrite($fh, "\n" . $table_create[1] . ";\n\n");

            $rows = @MainWPChildDB::_query('SELECT * FROM ' . $table, $wpdb->dbh);
            if ($rows)
            {
                $i = 0;
                $table_insert = 'INSERT INTO `' . $table . '` VALUES (';

                while ($row = @MainWPChildDB::fetch_array($rows))
                {
                    $query = $table_insert;
                    foreach ($row as $value)
                    {
                        $query.= '"'.MainWPChildDB::real_escape_string($value).'", ' ;
                    }
                    $query = trim($query, ', ') . ");";

                    fwrite($fh, "\n" . $query);
                    $i++;

                    if ($i >= 50)
                    {
                        fflush($fh);
                        $i = 0;
                    }

                    $query = null;
                    $row = null;
                }
            }
            $rows = null;
            fflush($fh);
        }

        fclose($fh);

        if ($archiveExt != false)
        {
            $newFilepath = $filepath . '.' . $archiveExt;

            if ($archiveExt == 'zip')
            {
                $this->archiver = null;
            }
            else
            {
                $this->archiver = new TarArchiver($this, $archiveExt);
            }

            if ($this->zipFile($filepath, $newFilepath) && file_exists($newFilepath))
            {
                @unlink($filepath);
                $filepath = $newFilepath;
            }
        }
        return array('filepath' => $filepath);
    }

    public function createBackupDB_legacy($filepath)
    {
        $fh = fopen($filepath, 'w'); //or error;

        global $wpdb;
        $maxchars = 50000;

        //Get all the tables
        $tables_db = $wpdb->get_results('SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_N);
        foreach ($tables_db as $curr_table)
        {
            $table = $curr_table[0];

            fwrite($fh, "\n" . 'DROP TABLE IF EXISTS ' . $table . ';');
            $table_create = $wpdb->get_row('SHOW CREATE TABLE ' . $table, ARRAY_N);
            fwrite($fh, "\n" . $table_create[1] . ';');

            //$rows = $wpdb->get_results('SELECT * FROM ' . $table, ARRAY_N);
            $rows = @MainWPChildDB::_query('SELECT * FROM ' . $table, $wpdb->dbh);
            if ($rows)
            {
                $table_columns = $wpdb->get_results('SHOW COLUMNS FROM ' . $table);
                $table_columns_insert = '';
                foreach ($table_columns as $table_column)
                {
                    if ($table_columns_insert != '')
                        $table_columns_insert .= ', ';
                    $table_columns_insert .= '`' . $table_column->Field . '`';
                }
                $table_insert = 'INSERT INTO `' . $table . '` (';
                $table_insert .= $table_columns_insert;
                $table_insert .= ') VALUES ' . "\n";


                $current_insert = $table_insert;

                $inserted = false;
                $add_insert = '';
                while ($row = @MainWPChildDB::fetch_array($rows))
                {
                    //Create new insert!
                    $add_insert = '(';
                    $add_insert_each = '';
                    foreach ($row as $value)
                    {
                        //$add_insert_each .= "'" . str_replace(array("\n", "\r", "'"), array('\n', '\r', "\'"), $value) . "',";

                        $value = addslashes($value);
                        $value = str_replace("\n","\\n",$value);
                        $value = str_replace("\r","\\r",$value);
                        $add_insert_each.= '"'.$value.'",' ;
                    }
                    $add_insert .= trim($add_insert_each, ',') . ')';

                    //If we already inserted something & the total is too long - commit previous!
                    if ($inserted && strlen($add_insert) + strlen($current_insert) >= $maxchars)
                    {
                        fwrite($fh, "\n" . $current_insert . ';');
                        $current_insert = $table_insert;
                        $current_insert .= $add_insert;
                        $inserted = false;
                    }
                    else
                    {
                        if ($inserted)
                        {
                            $current_insert .= ', ' . "\n";
                        }
                        $current_insert .= $add_insert;
                    }
                    $inserted = true;
                }
                if ($inserted)
                {
                    fwrite($fh, "\n" . $current_insert . ';');
                }
            }
        }

        fclose($fh);
        return true;
    }

}

?>
