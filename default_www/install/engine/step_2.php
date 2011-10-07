<?php

/**
 * Step 2 of the Fork installer
 *
 * @package		install
 * @subpackage	installer
 *
 * @author		Davy Hellemans <davy@netlash.com>
 * @author		Tijs Verkoyen <tijs@sumocoders.be>
 * @author		Matthias Mullie <matthias@netlash.com>
 * @author		Dieter Vanden Eynde <dieter@dieterve.be>
 * @since		2.0
 */
class InstallerStep2 extends InstallerStep
{
	/**
	 * Error statusses for the requirements.
	 *
	 * @var	string
	 */
	const STATUS_OK = 'ok',
			STATUS_WARNING = 'warning',
			STATUS_ERROR = 'error';


	/**
	 * Array containing all variables to be parsed in the template.
	 *
	 * @var	array
	 */
	private static $variables = array();


	/**
	 * Check if a specific requirement is satisfied
	 *
	 * @return	boolean
	 * @param	string $variable				The "name" of the check.
	 * @param	bool $requirement				The result of the check.
	 * @param	string $severity					The severity of the requirement.
	 */
	public static function checkRequirement($variable, $requirement, $severity = self::STATUS_ERROR)
	{
		// set status
		self::$variables[$variable] = $requirement ? self::STATUS_OK : $severity;
		return self::$variables[$variable] == self::STATUS_OK;
	}


	/**
	 * Checks the requirements
	 *
	 * @return	bool
	 */
	public static function checkRequirements()
	{
		// define step
		$step = (isset($_GET['step']) && in_array($_GET['step'], array('1', '2', '3', '4', '5', '6', '7'))) ? (int) $_GET['step'] : 1;

		// define constants
		if(!defined('PATH_WWW') && !defined('PATH_LIBRARY')) self::defineConstants($step);

		/*
		 * At first we're going to check to see if the PHP version meets the minimum requirements
		 * for Fork CMS.
		 */

		// fetch the PHP version.
		$version = (int) str_replace('.', '', PHP_VERSION);

		// we require at least PHP 5.2.x
		self::checkRequirement('phpVersion', version_compare(PHP_VERSION, '5.2.0-whatever', '>='), self::STATUS_ERROR);

		/*
		 * A couple extensions need to be loaded in order to be able to use Fork CMS. Without these
		 * extensions, we can't guarantee that everything will work.
		 */

		// check for cURL extension
		self::checkRequirement('extensionCURL', extension_loaded('curl'), self::STATUS_ERROR);

		// check for libxml extension
		self::checkRequirement('extensionLibXML', extension_loaded('libxml'), self::STATUS_ERROR);

		// check for DOM extension
		self::checkRequirement('extensionDOM', extension_loaded('dom'), self::STATUS_ERROR);

		// check for SimpleXML extension
		self::checkRequirement('extensionSimpleXML', extension_loaded('SimpleXML'), self::STATUS_ERROR);

		// check for SPL extension
		self::checkRequirement('extensionSPL', extension_loaded('SPL'), self::STATUS_ERROR);

		// check for PDO extension
		self::checkRequirement('extensionPDO', extension_loaded('PDO'), self::STATUS_ERROR);

		// check for MySQL driver
		self::checkRequirement('extensionPDOMySQL', extension_loaded('PDO') && in_array('mysql', PDO::getAvailableDrivers()), self::STATUS_ERROR);

		// check for mbstring extension
		self::checkRequirement('extensionMBString', extension_loaded('mbstring'), self::STATUS_ERROR);

		// check for iconv extension
		self::checkRequirement('extensionIconv', extension_loaded('iconv'), self::STATUS_ERROR);

		// check for gd extension and correct version
		self::checkRequirement('extensionGD2', extension_loaded('gd') && function_exists('gd_info'), self::STATUS_ERROR);

		/*
		 * A couple of php.ini settings should be configured in a specific way to make sure that
		 * they don't intervene with Fork CMS.
		 */

		// check for safe mode
		self::checkRequirement('settingsSafeMode', ini_get('safe_mode') == '', self::STATUS_WARNING);

		// check for open basedir
		self::checkRequirement('settingsOpenBasedir', ini_get('open_basedir') == '', self::STATUS_WARNING);

		/*
		 * Make sure the filesystem is prepared for the installation and everything can be read/
		 * written correctly.
		 */

		// check if the backend-cache-directory is writable
		self::checkRequirement('fileSystemBackendCache', defined('PATH_WWW') && self::isRecursivelyWritable(PATH_WWW . '/backend/cache/'), self::STATUS_ERROR);

		// check if the frontend-cache-directory is writable
		self::checkRequirement('fileSystemFrontendCache', defined('PATH_WWW') && self::isRecursivelyWritable(PATH_WWW . '/frontend/cache/'), self::STATUS_ERROR);

		// check if the frontend-files-directory is writable
		self::checkRequirement('fileSystemFrontendFiles', defined('PATH_WWW') && self::isRecursivelyWritable(PATH_WWW . '/frontend/files/'), self::STATUS_ERROR);

		// check if the library-directory is writable
		self::checkRequirement('fileSystemLibrary', defined('PATH_LIBRARY') && self::isWritable(PATH_LIBRARY), self::STATUS_ERROR);

		// check if the library/external-directory is writable
		// self::checkRequirement('fileSystemLibraryExternal', (defined('PATH_LIBRARY') && self::isWritable(PATH_LIBRARY . '/external')), self::STATUS_ERROR);

		// check if the installer-directory is writable
		self::checkRequirement('fileSystemInstaller', defined('PATH_WWW') && self::isWritable(PATH_WWW . '/install/cache'), self::STATUS_ERROR);

		// does the config.base.php file exist
		self::checkRequirement('fileSystemConfig', defined('PATH_LIBRARY') && file_exists(PATH_LIBRARY . '/config.base.php') && is_readable(PATH_LIBRARY . '/config.base.php'), self::STATUS_ERROR);

		// does the globals.base.php file exist
		self::checkRequirement('fileSystemGlobals', defined('PATH_LIBRARY') && file_exists(PATH_LIBRARY . '/globals.base.php') && is_readable(PATH_LIBRARY . '/globals.base.php'), self::STATUS_ERROR);

		// does the globals_backend.base.php file exist
		self::checkRequirement('fileSystemGlobalsBackend', defined('PATH_LIBRARY') && file_exists(PATH_LIBRARY . '/globals_backend.base.php') && is_readable(PATH_LIBRARY . '/globals_backend.base.php'), self::STATUS_ERROR);

		// does the globals_frontend.base.php file exist
		self::checkRequirement('fileSystemGlobalsFrontend', defined('PATH_LIBRARY') && file_exists(PATH_LIBRARY . '/globals_frontend.base.php') && is_readable(PATH_LIBRARY . '/globals_frontend.base.php'), self::STATUS_ERROR);

		// library path exists
		self::checkRequirement('fileSystemPathLibrary', defined('PATH_LIBRARY') && PATH_LIBRARY != '', self::STATUS_ERROR);

		/*
		 * Ensure that Apache .htaccess file is written and mod_rewrite does its job
		 */
		self::checkRequirement('modRewrite', (bool) getenv('MOD_REWRITE'), self::STATUS_WARNING);

		// error status
		return !in_array(self::STATUS_ERROR, self::$variables);
	}


	/**
	 * Define path constants
	 *
	 * @return	void
	 * @param	int $step	The step wherefor the constant should be defined.
	 */
	private static function defineConstants($step)
	{
		// init library path
		$pathLibrary = null;

		// define step
		if($step != 1) $pathLibrary = (isset($_SESSION['path_library'])) ? $_SESSION['path_library'] : null;

		// guess the path to the library
		if($pathLibrary == null)
		{
			// guess the path
			self::guessLibraryPath(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))), $pathLibrary);

			$count = count($pathLibrary);

			// just one found? add it into the session
			if($count == 1)
			{
				$_SESSION['path_library'] = $pathLibrary[0];
				$pathLibrary = $pathLibrary[0];
			}

			// none found means there is no Spoon
			elseif($count == 0) return false;

			// multiple
			else
			{
				// redirect
				header('Location: index.php?step=1');
				exit;
			}
		}

		// define constants
		if(!defined('PATH_WWW')) define('PATH_WWW', dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME']))));
		if(!defined('PATH_LIBRARY')) define('PATH_LIBRARY', (string) $pathLibrary);

		// update session
		if(!isset($_SESSION['path_library'])) $_SESSION['path_library'] = PATH_LIBRARY;
		if(!isset($_SESSION['path_www'])) $_SESSION['path_www'] = PATH_WWW;
	}


	/**
	 * Execute this step
	 *
	 * @return	void
	 */
	public function execute()
	{
		// init vars
		self::$variables = array();

		// head
		self::$variables['head'] = file_get_contents('layout/templates/head.tpl');
		self::$variables['foot'] = file_get_contents('layout/templates/foot.tpl');

		// next step
		self::$variables['step3'] = 'index.php?step=3';

		// check requirements
		self::checkRequirements();

		// get template contents
		$tpl = file_get_contents('layout/templates/step_2.tpl');

		// has errors
		if(in_array(self::STATUS_ERROR, self::$variables)) self::$variables[self::STATUS_ERROR] = true;

		// has warnings
		elseif(in_array(self::STATUS_WARNING, self::$variables)) self::$variables[self::STATUS_WARNING] = true;

		// no errors detected
		else
		{
			header('Location: ' . self::$variables['step3']);
			exit;
		}

		// set paths for template
		self::$variables['PATH_WWW'] = (defined('PATH_WWW')) ? PATH_WWW : '<unknown>';
		self::$variables['PATH_LIBRARY'] = (defined('PATH_LIBRARY')) ? PATH_LIBRARY : '<unknown>';

		// build the search & replace array
		$search = array_keys(self::$variables);
		$replace = array_values(self::$variables);

		// loop search values
		foreach($search as $key => $value)
		{
			// parse variables
			$tpl = str_replace('{$' . $value . '}', $replace[$key], $tpl);

			// assign options
			$tpl = preg_replace('/\{option:\!(' . $value . ')\}(.*?)\{\/option:\!\\1\}/is', (!$replace[$key] ? '\\2' : ''), $tpl);
			$tpl = preg_replace('/\{option:(' . $value . ')\}(.*?)\{\/option:\\1\}/is', ($replace[$key] ? '\\2' : ''), $tpl);
		}

		// assign leftover options
		$tpl = preg_replace('/\{option:!(.*?)\}(.*?)\{\/option:!\\1\}/is', '\\2', $tpl);
		$tpl = preg_replace('/\{option:(.*?)\}(.*?)\{\/option:\\1\}/is', '', $tpl);

		// ignore comments
		$tpl = preg_replace('/\{\*(?!.*?\{\*).*?\*\}/s', '', $tpl);

		// show output
		echo $tpl;

		// stop the script
		exit;
	}


	/**
	 * Try to guess the location of the library based on spoon library
	 *
	 * @return	void
	 * @param	string $directory			The directory to start from.
	 * @param	array[optional] $library	An array to hold the paths that were guesed.
	 */
	private static function guessLibraryPath($directory, array &$library = null)
	{
		// loop directories
		foreach((array) glob($directory . '/*') as $filename)
		{
			// not a directory and equals 'spoon.php'
			if(!is_dir($filename) && substr($filename, -9) == 'spoon.php')
			{
				// get real path
				$path = dirname(dirname($filename));

				// only unique values should be added
				if(is_array($library))
				{
					// add
					if(!in_array($path, $library)) $library[] = $path;
				}

				// not an array
				else $library = array($path);
			}

			// directory
			elseif(is_dir($filename) && substr($filename, -4) != '.svn')
			{
				// new location
				self::guessLibraryPath($filename, $library);
			}
		}
	}


	/**
	 * This step is always allowed.
	 *
	 * @return	bool
	 */
	public static function isAllowed()
	{
		return true;
	}


	/**
	 * Check if a directory and it's sub-directories and it's subdirectories and ... are writable.
	 *
	 * @return	bool
	 * @param	string $path	The path to check.
	 */
	private static function isRecursivelyWritable($path)
	{
		// redefine argument
		$path = rtrim((string) $path, '/');

		// check if path is writable
		if(!self::isWritable($path)) return false;

		// loop child directories
		foreach((array) scandir($path) as $file)
		{
			// no '.' and '..'
			if(($file != '.') && ($file != '..'))
			{
				// directory
				if(is_dir($path . '/' . $file))
				{
					// check if children are readable
					if(!self::isRecursivelyWritable($path . '/' . $file)) return false;
				}
			}
		}

		// we were able to read all sub-directories
		return true;
	}


	/**
	 * Check if a directory is writable.
	 * The default is_writable function has problems due to Windows ACLs "bug"
	 *
	 * @return	bool
	 * @param	string $path	The path to check.
	 */
	private static function isWritable($path)
	{
		// redefine argument
		$path = rtrim((string) $path, '/');

		// create random file
		$file = uniqid() . '.tmp';

		$return = @file_put_contents($path . '/' . $file, 'temporary file', FILE_APPEND);

		if($return === false) return false;

		// unlink the random file
		@unlink($path . '/' . $file);

		// return
		return true;
	}
}

?>