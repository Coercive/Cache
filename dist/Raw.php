<?php
namespace Coercive\Utility\Cache;

use Exception;
use DateInterval;

/**
 * RAW CACHE
 *
 * @package		Coercive\Utility\Cache
 * @link		@link https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   2019
 * @license 	MIT
 */
class Raw
{
	/** @var string Cache filepath */
	private $path = '';

	/** @var int File last mod timestamp */
	private $filemtime = -1;

	/** @var bool Enable cache system */
	private $state = false;

	/**
	 * CLEAN KEY
	 *
	 * @param string $key
	 * @return void
	 */
	private function clean(&$key)
	{
		$key = preg_replace('`[^a-z0-9_-]+`i', '_', $key);
	}

	/**
	 * Serialize constructor.
	 *
	 * @param string $path
	 * @param DateInterval $delay [optional]
	 * @return void
	 * @throws Exception
	 */
	public function __construct(string $path, bool $create = false)
	{
		# Set the cache filepath
		$this->path = realpath($path);

		# Create directory
		if ($create && !is_dir($this->path)) {
			if (!@mkdir($path, 0777, true)) {
				throw new Exception("Directory does not exist and can't be created : $path");
			}
			$this->path = realpath($path);
		}
	}

	/**
	 * Enable cache system
	 *
	 * @return $this
	 */
	public function enable(): Raw
	{
		$this->state = true;
		return $this;
	}

	/**
	 * Disable cache system
	 *
	 * @return $this
	 */
	public function disable(): Raw
	{
		$this->state = false;
		return $this;
	}

	/**
	 * Enable/Disable cache system
	 *
	 * @param bool $state
	 * @return $this
	 */
	public function setState(bool $state): Raw
	{
		$this->state = $state;
		return $this;
	}

	/**
	 * File last mod timestamp
	 *
	 * -1 on error, no file, cache disabled or delete...
	 *
	 * @return int
	 */
	public function filemtime(): int
	{
		return $this->filemtime;
	}

	/**
	 * GET
	 *
	 * @param string $key
	 * @param bool $filemtime [optional]
	 * @return string
	 */
	public function get(string $key, bool $filemtime = false): string
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return ''; }

		# Filepath
		$this->clean($key);
		$path = $this->path . DIRECTORY_SEPARATOR . $key;
		if (!is_file($path)) { return ''; }

		# Read datas
		if($filemtime) {
			$this->filemtime = (int) filemtime($path);
		}
		return (string) file_get_contents($path);
	}

	/**
	 * SET
	 *
	 * @param string $key
	 * @param string $data
	 * @return $this
	 * @throws Exception
	 */
	public function set(string $key, string $data): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Filepath
		$this->clean($key);
		$path = $this->path . DIRECTORY_SEPARATOR . $key;
		$tmp = $this->path . DIRECTORY_SEPARATOR . $key . uniqid('_temp_', true);

		# Write to temp file first to ensure atomicity
		// @link https://blogs.msdn.microsoft.com/adioltean/2005/12/28/how-to-do-atomic-writes-in-a-file
		if (file_put_contents($tmp, $data, LOCK_EX)) {
			rename($tmp, $path);
			chmod($path, 0777);
			$this->filemtime = 0;
			return $this;
		}
		throw new Exception("Can't write data in file : $tmp");
	}

	/**
	 * DELETE
	 *
	 * @param string $key
	 * @return $this
	 */
	public function delete(string $key): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Filepath
		$this->clean($key);
		unlink($this->path . DIRECTORY_SEPARATOR . $key);
		return $this;
	}

	/**
	 * CLEAR
	 *
	 * @param string $glob [optional]
	 * @return $this
	 */
	public function clear(string $glob = '{,.}*'): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Delete all files (even hidden)
		$files = glob($this->path . '/' . $glob, GLOB_BRACE);
		foreach($files as $file) { @unlink($file); }
		return $this;
	}
}
