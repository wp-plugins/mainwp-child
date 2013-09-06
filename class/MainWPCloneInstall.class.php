<?php
class MainWPCloneInstall
{
    protected $file;
    public $config;

    /**
     * Class constructor
     *
     * @param string $file The zip backup file path
     */
    public function __construct($file)
    {
        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php');

        $this->file = $file;
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
        //todo: implement
//        return function_exists('system');
        return false;
    }


    public function removeConfigFile()
    {
        if (!$this->file || !file_exists($this->file))
            return false;

        if ($this->checkZipConsole())
        {
            //todo: implement
        }
        else if ($this->checkZipSupport())
        {
            $zip = new ZipArchive();
            $zipRes = $zip->open($this->file);
            if ($zipRes)
            {
                $zip->deleteName('wp-config.php');
                $zip->deleteName('clone');
                $zip->close();
                return true;
            }

            return false;
        }
        else
        {
            //use pclzip
            $zip = new PclZip($this->file);
            $list = $zip->delete(PCLZIP_OPT_BY_NAME, 'wp-config.php');
            $list2 = $zip->delete(PCLZIP_OPT_BY_NAME, 'clone');
            if ($list == 0) return false;
            return true;
        }
        return false;
    }

    public function testDownload()
    {
        if (!$this->file_exists('wp-content/')) throw new Exception('Not a full backup.');
        if (!$this->file_exists('wp-admin/')) throw new Exception('Not a full backup.');
        if (!$this->file_exists('wp-content/dbBackup.sql')) throw new Exception('Database backup not found.');
    }

    private function file_exists($file)
    {
        if ($this->file == 'extracted') return file_get_contents('../clone/config.txt');

        if (!$this->file || !file_exists($this->file))
            return false;

        if ($this->checkZipConsole())
        {
            //todo: implement
        }
        else if ($this->checkZipSupport())
        {
            $zip = new ZipArchive();
            $zipRes = $zip->open($this->file);
            if ($zipRes)
            {
                $content = $zip->locateName($file);
                $zip->close();
                return $content !== false;
            }

            return false;
        }
        else
        {
            return true;
        }
        return false;
    }

    public function readConfigurationFile()
    {
        $configContents = $this->getConfigContents();
        if ($configContents === FALSE) throw new Exception('Cant read configuration file from backup');
        $this->config = unserialize(base64_decode($configContents));
    }

    public function setConfig($key, $val)
    {
        $this->config[$key] = $val;
    }

    public function testDatabase()
    {
        $link = @mysql_connect($this->config['dbHost'], $this->config['dbUser'], $this->config['dbPass']);
        if (!$link) throw new Exception('Invalid database host or user/password.');

        $db_selected = @mysql_select_db($this->config['dbName'], $link);
        if (!$db_selected) throw new Exception('Invalid database name');
    }

    public function clean()
    {
        if (file_exists(WP_CONTENT_DIR . '/dbBackup.sql')) @unlink(WP_CONTENT_DIR . '/dbBackup.sql');
        if (file_exists(ABSPATH . 'clone/config.txt')) @unlink(ABSPATH . 'clone/config.txt');
        if (MainWPHelper::is_dir_empty(ABSPATH . 'clone')) @rmdir(ABSPATH . 'clone');

        $dirs = MainWPHelper::getMainWPDir('backup');
        $backupdir = $dirs[0];

        $files = glob($backupdir . '*.zip');
        foreach ($files as $file)
        {
            @unlink($file);
        }
    }

    /**
     * Run the installation
     *
     * @return bool
     */
    public function install()
    {
        global $table_prefix, $wpdb;


        $home = get_option('home');
        $site_url = get_option('siteurl');
        // Install database
        define('WP_INSTALLING', true);
//        define('ABSPATH', ABSPATH);
//        define('WPINC', 'wp-includes');
//        define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
        define('WP_DEBUG', false);
        $query = '';
        $tableName = '';
        $handle = @fopen(WP_CONTENT_DIR . '/dbBackup.sql', 'r');
        if ($handle)
        {
            $readline = '';
            while (($line = fgets($handle, 81920)) !== false)
            {
                $readline .= $line;
                if (!stristr($line, "\n") && !feof($handle)) continue;

                if (preg_match('/^(DROP +TABLE +IF +EXISTS|CREATE +TABLE|INSERT +INTO) +(\S+)/is', $readline, $match))
                {
                    if (trim($query) != '')
                    {
                        $queryTable = $tableName;
                        $query = preg_replace('/^(DROP +TABLE +IF +EXISTS|CREATE +TABLE|INSERT +INTO) +(\S+)/is', '\\1 `' . $queryTable . '`', $query);

                        $query = str_replace($this->config['home'], $home, $query);
                        $query = str_replace($this->config['siteurl'], $site_url, $query);
                        $query = str_replace($this->config['abspath'], ABSPATH, $query);
                        $query = str_replace('\"', '\\\"', $query);
                        $query = str_replace("\\\\'", "\\'", $query);
                        $query = str_replace('\r\n', '\\\r\\\n', $query);

                        if ($wpdb->query($query) === false) throw new Exception('Error importing database');
                    }

                    $query = $readline;
                    $readline = '';
                    $tableName = trim($match[2], '`; ');
                }
                else
                {
                    $query .= $readline;
                    $readline = '';
                }
            }

            if (trim($query) != '')
            {
                $queryTable = $tableName;
                $query = preg_replace('/^(DROP +TABLE +IF +EXISTS|CREATE +TABLE|INSERT +INTO) +(\S+)/is', '\\1 `' . $queryTable . '`', $query);

                $query = str_replace($this->config['home'], $home, $query);
                $query = str_replace($this->config['siteurl'], $site_url, $query);
                $query = str_replace('\"', '\\\"', $query);
                $query = str_replace("\\\\'", "\\'", $query);
                $query = str_replace('\r\n', '\\\r\\\n', $query);
                if ($wpdb->query($query) === false) throw new Exception('Error importing database');
            }

            if (!feof($handle))
            {
                throw new Exception('Error: unexpected end of file for database');
            }
            fclose($handle);
        }

        // Update site url
        $wpdb->query('UPDATE '.$table_prefix.'options SET option_value = "'.$site_url.'" WHERE option_name = "siteurl"');
        $wpdb->query('UPDATE '.$table_prefix.'options SET option_value = "'.$home.'" WHERE option_name = "home"');

        $rows = $wpdb->get_results( 'SELECT * FROM ' . $table_prefix.'options', ARRAY_A);
        foreach ($rows as $row)
        {
            $option_val = $row['option_value'];
            if (!$this->is_serialized($option_val)) continue;

            $option_val = $this->recalculateSerializedLengths($option_val);
            $option_id = $row['option_id'];
            $wpdb->query('UPDATE '.$table_prefix.'options SET option_value = "'.mysql_real_escape_string($option_val).'" WHERE option_id = '.$option_id);
        }
//die('jup');
        return true;
    }

    protected function recalculateSerializedLengths($pObject)
    {
       return preg_replace_callback('|s:(\d+):"(.*?)";|', array($this, 'recalculateSerializedLengths_callback'), $pObject);
    }

    protected function recalculateSerializedLengths_callback($matches)
    {
        return 's:'.strlen($matches[2]).':"'.$matches[2].'";';
    }

    /**
     * Check value to find if it was serialized.
     *
     * If $data is not an string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @since 2.0.5
     *
     * @param mixed $data Value to check to see if was serialized.
     * @return bool False if not serialized and true if it was.
     */
    function is_serialized( $data ) {
    	// if it isn't a string, it isn't serialized
    	if ( ! is_string( $data ) )
    		return false;
    	$data = trim( $data );
     	if ( 'N;' == $data )
    		return true;
    	$length = strlen( $data );
    	if ( $length < 4 )
    		return false;
    	if ( ':' !== $data[1] )
    		return false;
    	$lastc = $data[$length-1];
    	if ( ';' !== $lastc && '}' !== $lastc )
    		return false;
    	$token = $data[0];
    	switch ( $token ) {
    		case 's' :
    			if ( '"' !== $data[$length-2] )
    				return false;
    		case 'a' :
    		case 'O' :
    			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
    		case 'b' :
    		case 'i' :
    		case 'd' :
    			return (bool) preg_match( "/^{$token}:[0-9.E-]+;\$/", $data );
    	}
    	return false;
    }

    public function cleanUp()
    {
        // Clean up!
        @unlink('../dbBackup.sql');
    }

    public function getConfigContents()
    {
        if ($this->file == 'extracted') return file_get_contents('../clone/config.txt');

        if (!$this->file || !file_exists($this->file))
            return false;

        if ($this->checkZipConsole())
        {
            //todo: implement
        }
        else if ($this->checkZipSupport())
        {
            $zip = new ZipArchive();
            $zipRes = $zip->open($this->file);
            if ($zipRes)
            {
                $content = $zip->getFromName('clone/config.txt');
//                $zip->deleteName('clone/config.txt');
//                $zip->deleteName('clone/');
                $zip->close();
                return $content;
            }

            return false;
        }
        else
        {
            //use pclzip
            $zip = new PclZip($this->file);
            $content = $zip->extract(PCLZIP_OPT_BY_NAME, 'clone/config.txt',
                PCLZIP_OPT_EXTRACT_AS_STRING);
            if (!is_array($content) || !isset($content[0]['content'])) return false;
            return $content[0]['content'];
        }
        return false;
    }

    /**
     * Extract backup
     *
     * @return bool
     */
    public function extractBackup()
    {
        if (!$this->file || !file_exists($this->file))
            return false;

        if ($this->checkZipConsole())
            return $this->extractZipConsoleBackup();
        else if ($this->checkZipSupport())
            return $this->extractZipBackup();
        else
            return $this->extractZipPclBackup();

        return false;
    }

    /**
     * Extract backup using default PHP zip library
     *
     * @return bool
     */
    public function extractZipBackup()
    {
        $zip = new ZipArchive();
        $zipRes = $zip->open($this->file);
        if ($zipRes)
        {
            $zip->extractTo(ABSPATH);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * Extract backup using pclZip library
     *
     * @return bool
     */
    public function extractZipPclBackup()
    {
        $zip = new PclZip($this->file);
        if ($zip->extract(PCLZIP_OPT_PATH, ABSPATH, PCLZIP_OPT_REPLACE_NEWER) == 0)
        {
            return false;
        }
		if ($zip->error_code != PCLZIP_ERR_NO_ERROR) throw new Exception($zip->errorInfo(true));
		return true;
    }

    /**
     * Extract backup using zip on console
     *
     * @return bool
     */
    public function extractZipConsoleBackup()
    {
        //todo implement
        //system('zip');
        return false;
    }

    /**
     * Replace define statement to work with wp-config.php
     *
     * @param string $constant The constant name
     * @param string $value The new value
     * @param string $content The PHP file content
     * @return string Replaced define statement with new value
     */
    protected function replaceDefine($constant, $value, $content)
    {
        return preg_replace('/(define *\( *[\'"]' . $constant . '[\'"] *, *[\'"])(.*?)([\'"] *\))/is', '\\1' . $value . '\\3', $content);
    }

    /**
     * Replace variable value to work with wp-config.php
     *
     * @param string $varname The variable name
     * @param string $value The new value
     * @param string $content The PHP file content
     * @return string Replaced variable value with new value
     */
    protected function replaceVar($varname, $value, $content)
    {
        return preg_replace('/(\$' . $varname . ' *= *[\'"])(.*?)([\'"] *;)/is', '\\1' . $value . '\\3', $content);
    }

    function recurse_chmod($mypath, $arg)
    {
        $d = opendir($mypath);
        while (($file = readdir($d)) !== false)
        {
            if ($file != "." && $file != "..")
            {
                $typepath = $mypath . "/" . $file;
                if (filetype($typepath) == 'dir')
                {
                    recurse_chmod($typepath, $arg);
                }
                chmod($typepath, $arg);
            }
        }
    }
}