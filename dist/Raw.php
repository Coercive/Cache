<?php
namespace Coercive\Utility\Cache;

use Exception;

/**
 * RAW CACHE
 *
 * @package		Coercive\Utility\Cache
 * @link		https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   2019
 * @license 	MIT
 */
class Raw
{
	const DEFAULT_FILES_PATTERN = '`[a-z0-9\._-]+`i';

	const
		DATA_PROCESS_NONE = 'NONE',
		DATA_PROCESS_SERIALIZE = 'SERIALIZE',
		DATA_PROCESS_JSON_OBJECT = 'JSON_OBJECT',
		DATA_PROCESS_JSON_ARRAY = 'JSON_ARRAY';

	/** @var string Data process type */
	private $process = self::DATA_PROCESS_NONE;

	/** @var string Cache filepath */
	private $path = '';

	/** @var int Cache max life time */
	private $maxLifeTime = 0;

	/** @var int File last mod timestamp */
	private $filemtime = -1;

	/** @var bool Enable cache system */
	private $state = false;

	/** @var array Already retrieved datas */
	private $datas = [];

	/**
	 * Clean key
	 *
	 * @param string $key
	 * @return void
	 */
	private function clean(&$key)
	{
		$key = preg_replace('`[^a-z0-9_-]+`i', '_', $key);
	}

	/**
	 * Retrieve all matched files
	 *
	 * @param string $pattern [optional]
	 * @return array
	 */
	private function getFiles(string $pattern = self::DEFAULT_FILES_PATTERN)
	{
		$files = [];
		foreach (glob($this->path . '/*') as $fullpath) {
			if(!is_file($fullpath)) { continue; }

			$suffix = '';
			$extension = pathinfo($fullpath, PATHINFO_EXTENSION);
			if($extension) {
				$suffix = '.'.$extension;
			}

			$name =  basename($fullpath, $suffix);
			if(preg_match($pattern, $name)) {
				$files[$fullpath] = $name;
			}
		}
		return $files;
	}

	/**
	 * Convert input data to storage str
	 *
	 * @param mixed $data
	 * @return string
	 * @throws Exception
	 */
	private function dataToStorage($data): string
	{
		if(null === $data || '' === $data) { return ''; }
		switch ($this->process)
		{
			case self::DATA_PROCESS_NONE:
				return $data;

			case self::DATA_PROCESS_JSON_ARRAY:
			case self::DATA_PROCESS_JSON_OBJECT:
				return json_encode($data);

			case self::DATA_PROCESS_SERIALIZE:
				return serialize($data);

			default:
				throw new Exception('Unknow process type : ' . $this->process);
		}
	}

	/**
	 * Convert storage str to data format
	 *
	 * @param string $raw
	 * @return mixed
	 * @throws Exception
	 */
	private function storageToData(string $raw)
	{
		if(null === $raw || '' === $raw) { return ''; }
		switch ($this->process)
		{
			case self::DATA_PROCESS_NONE:
				return $raw;

			case self::DATA_PROCESS_JSON_ARRAY:
				return json_decode($raw, true);

			case self::DATA_PROCESS_JSON_OBJECT:
				return json_decode($raw);

			case self::DATA_PROCESS_SERIALIZE:
				return serialize($raw);

			default:
				throw new Exception('Unknow process type : ' . $this->process);
		}
	}

	/**
	 * Raw constructor.
	 *
	 * @param string $path
	 * @param bool $create [optional]
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
	 * @return Raw
	 */
	public function enable(): Raw
	{
		$this->state = true;
		return $this;
	}

	/**
	 * Disable cache system
	 *
	 * @return Raw
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
	 * @return Raw
	 */
	public function setState(bool $state): Raw
	{
		$this->state = $state;
		return $this;
	}

	/**
	 * Cache max life time
	 *
	 * @param int $seconds [optional]
	 * @return Raw
	 */
	public function setMaxLifeTime(int $seconds = 0): Raw
	{
		$this->maxLifeTime = $seconds;
		return $this;
	}

	/**
	 * Set data process type
	 *
	 * @param string $type [optional]
	 * @return Raw
	 */
	public function setProcess(string $type = self::DATA_PROCESS_NONE): Raw
	{
		if(in_array($type, [
			self::DATA_PROCESS_NONE,
			self::DATA_PROCESS_SERIALIZE,
			self::DATA_PROCESS_JSON_OBJECT,
			self::DATA_PROCESS_JSON_ARRAY,
		], true)) {
			$this->process = $type;
		}
		return $this;
	}

	/**
	 * File last mod timestamp
	 *
	 * -1 on error, no file, cache disabled or delete...
	 *
	 * @return int
	 */
	public function getFilemtime(): int
	{
		return $this->filemtime;
	}

	/**
	 * GET
	 *
	 * @param string $key
	 * @param int $maxLifeTime [optional]
	 * @return mixed
	 * @throws Exception
	 */
	public function get(string $key, int $maxLifeTime = null)
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return ''; }

		# Clean key name
		$this->clean($key);

		# From var storage
		if(isset($this->datas[$key])) {
			$this->filemtime = intval($this->datas[$key]['filemtime'] ?? -1);
			$data = strval($this->datas[$key]['data'] ?? '');
		}

		# From file storage
		else {
			$path = $this->path . DIRECTORY_SEPARATOR . $key;
			$data = '';
			if (is_file($path)) {
				$this->filemtime = (int) filemtime($path);
				$data = (string) file_get_contents($path);
			}
			$this->datas[$key] = [
				'filemtime' => $this->filemtime,
				'data' => $data,
			];
		}

		# Expire max life time
		if(0 !== $maxLifeTime && ($max = $maxLifeTime ?: $this->maxLifeTime)) {
			$t = time();
			if(time() > $this->filemtime + $max) {
				$this->delete($key);
				$this->filemtime = -1;
				return '';
			}
		}

		# Return prepared data
		return $this->storageToData($data);
	}

	/**
	 * SET
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return $this
	 * @throws Exception
	 */
	public function set(string $key, $data): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Filepath
		$this->clean($key);
		$path = $this->path . DIRECTORY_SEPARATOR . $key;
		$tmp = $this->path . DIRECTORY_SEPARATOR . $key . uniqid('_temp_', true);

		# Prepare data
		$data = $this->dataToStorage($data);

		# Write to temp file first to ensure atomicity
		// @link https://blogs.msdn.microsoft.com/adioltean/2005/12/28/how-to-do-atomic-writes-in-a-file
		if (file_put_contents($tmp, $data, LOCK_EX)) {
			rename($tmp, $path);
			chmod($path, 0777);
			$t = time();
			$this->filemtime = $t;
			$this->datas[$key] = [
				'filemtime' => $t,
				'data' => $data,
			];
			return $this;
		}
		throw new Exception("Can't write data in file : $tmp");
	}

	/**
	 * Delete targeted file and var storage
	 *
	 * @param string $key
	 * @return $this
	 */
	public function delete(string $key): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Delete targeted file and var storage
		$this->clean($key);
		if(isset($this->datas[$key])) {
			unset($this->datas[$key]);
		}
		@unlink($this->path . DIRECTORY_SEPARATOR . $key);
		return $this;
	}

	/**
	 * Drop all matched files
	 *
	 * @param string $pattern [optional]
	 * @return $this
	 */
	public function clear(string $pattern = self::DEFAULT_FILES_PATTERN): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Delete all files (even hidden) and var storage
		$files = $this->getFiles($pattern);
		foreach($files as $path => $key) {
			if(isset($this->datas[$key])) {
				unset($this->datas[$key]);
			}
			@unlink($path);
		}
		return $this;
	}

	/**
	 * Drop fron storage only
	 *
	 * @param string $key
	 * @return $this
	 */
	public function drop(string $key): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }

		# Delete var storage
		$this->clean($key);
		if(isset($this->datas[$key])) {
			unset($this->datas[$key]);
		}
		return $this;
	}

	/**
	 * Drop all matched files
	 *
	 * @param string $pattern [optional]
	 * @return $this
	 */
	public function expire(string $pattern = self::DEFAULT_FILES_PATTERN): Raw
	{
		# Cache disable
		$this->filemtime = -1;
		if(!$this->state) { return $this; }
		if(!$this->maxLifeTime) { return $this; }

		# Delete all expired files
		$t = time();
		$files = $this->getFiles($pattern);
		foreach($files as $path => $key) {
			$filemtime = (int) filemtime($path);
			if($t > $filemtime + $this->maxLifeTime) {
				if(isset($this->datas[$key])) {
					unset($this->datas[$key]);
				}
				@unlink($path);
			}
		}
		return $this;
	}
}