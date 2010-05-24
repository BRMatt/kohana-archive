<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Archive library.
 *
 * $Id: Archive.php 4367 2009-05-27 21:23:57Z samsoir $
 *
 * @package    Archive
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license
 */
Abstract Class Archive 
{

	// Files and directories
	protected $paths;

	// Driver instance
	protected $driver;

	/**
	 * Creates an instance of an archive creator
	 * 
	 * @param string $type Type of archive to create
	 * @return Archive
	 */
	static function factory($type = 'zip')
	{
		$class = 'Archive_'.ucfirst($type);

		if( ! class_exists($class))
		{
			throw new Kohana_Exception('Archive class does not exist');
		}

		return new $class;
	}

	/**
	 * Adds files or directories, recursively, to an archive.
	 *
	 * @param   string   file or directory to add
	 * @param   string   name to use for the given file or directory
	 * @param   bool     add files recursively, used with directories
	 * @return  object
	 */
	public function add($path, $name = NULL, $recursive = NULL)
	{
		// Normalize to forward slashes
		$path = str_replace('\\', '/', $path);

		// Set the name
		empty($name) and $name = $path;

		if (is_dir($path))
		{
			// Force directories to end with a slash
			$path = rtrim($path, '/').'/';
			$name = rtrim($name, '/').'/';

			// Add the directory to the paths
			$this->paths[] = array($path, $name);

			if ($recursive === TRUE)
			{
				$dir = opendir($path);
				while (($file = readdir($dir)) !== FALSE)
				{
					// Do not add hidden files or directories
					if ($file[0] === '.')
						continue;

					// Add directory contents
					$this->add($path.$file, $name.$file, TRUE);
				}
				closedir($dir);
			}
		}
		else
		{
			$this->paths[] = array($path, $name);
		}

		return $this;
	}

	/**
	 * Creates an archive and saves it into a file.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   archive filename
	 * @return  boolean
	 */
	public function save($filename)
	{
		// Get the directory name
		$directory = pathinfo($filename, PATHINFO_DIRNAME);

		if ( ! is_writable($directory))
			throw new Kohana_Exception(':directory not writable', 
				array(':directory' => $directory));

		if (is_file($filename))
		{
			// Unable to write to the file
			if ( ! is_writable($filename))
				throw new Kohana_Exception(':filename filename conflict', 
					array(':filename' => $filename));

			// Remove the file
			unlink($filename);
		}

		return $this->create($this->paths, $filename);
	}

	/**
	 * Creates a raw archive file and returns it.
	 *
	 * @return  string
	 */
	Abstract public function create($paths, $filename = FALSE);

	/**
	 * Forces a download of a created archive.
	 *
	 * @param   string   name of the file that will be downloaded
	 * @return  void
	 */
	public function download($filename)
	{
		download::force($filename, $this->create($this->paths));
	}

} // End Archive