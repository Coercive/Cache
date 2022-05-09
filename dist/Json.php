<?php
namespace Coercive\Utility\Cache;

use DateTime;
use Exception;
use DateInterval;

/**
 * JSON CACHE
 *
 * @package		Coercive\Utility\Cache
 * @link		https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2022 Anthony Moral
 * @license 	MIT
 */
class Json
{
	/** @var string Cache filepath */
	private string $source;

	/** @var string Cache filepath */
	private string $path = '';

	/** @var DateInterval Expire delay */
	private DateInterval $delay;

	/** @var Exception|null Class load error */
	private ? Exception $_LoadException = null;

	/** @var Exception|null Getter error */
	private ? Exception $_GetException = null;

	/** @var Exception|null Setter error */
	private ? Exception $_SetException = null;

	/** @var bool Enable cache system */
	private bool $enabled = false;

	/**
	 * CLEAN KEY
	 *
	 * @param string $key
	 * @return void
	 */
	private function clean(string &$key)
	{
		$key = preg_replace('`[^a-z0-9]+`i', '_', $key);
	}

	/**
	 * Json constructor.
	 *
	 * @param string $path [optional]
	 * @param string $delay [optional]
	 * @return void
	 */
	public function __construct(string $path, string $delay = 'P7D')
	{
		try {
			# Set global default delay
			$this->setExpireDelay($delay);

			# Set the cache filepath
			$this->source = $path;
		}
		catch(Exception $e) {
			$this->_LoadException = $e;
		}
	}

	/**
	 * Verify if cache system is active
	 *
	 * @return bool
	 */
	public function isEnable(): bool
	{
		return $this->enabled;
	}

	/**
	 * Enable cache system
	 *
	 * @return Json
	 * @throws Exception
	 */
	public function enable(): Json
	{
		$this->setStatus(true);
		return $this;
	}

	/**
	 * Disable cache system
	 *
	 * @return Json
	 * @throws Exception
	 */
	public function disable(): Json
	{
		$this->setStatus(false);
		return $this;
	}

	/**
	 * Enable/Disable cache system
	 *
	 * @param bool $enable
	 * @return Json
	 * @throws Exception
	 */
	public function setStatus(bool $enable): Json
	{
		$this->enabled = $enable;
		if($enable && !$this->path) {
			if(!$this->source) {
				throw new Exception('Cache directory is not set.');
			}
			if(!is_dir($this->source)) {
				if(!mkdir($this->source, 0777, true)) {
					throw new Exception('Can\'t create cache directory: ' . $this->source);
				}
			}
			$this->path = (string) realpath($this->source);
			if(!$this->path) {
				throw new Exception('Can\'t use realpath cache directory: ' . $this->source);
			}
		}
		return $this;
	}

	/**
	 * ERROR
	 *
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->_LoadException || $this->_GetException || $this->_SetException;
	}

	/**
	 * @return Exception|null
	 */
	public function getException(): ? Exception
	{
		if($this->_LoadException) {
			return $this->_LoadException;
		}
		if($this->_GetException) {
			return $this->_GetException;
		}
		if($this->_SetException) {
			return $this->_SetException;
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage(): string
	{
		if($e = $this->getException()) {
			return $e->getMessage();
		}
		return '';
	}

	/**
	 * SET EXPIRE DELAY
	 *
	 * @param string $delay [optional] Date interval format
	 * @return $this
	 * @throws Exception
	 */
	public function setExpireDelay(string $delay = 'P7D'): Json
	{
		$this->delay = new DateInterval($delay);
		return $this;
	}

	/**
	 * GET
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function get(string $key)
	{
		# Clear
		$this->_GetException = null;

		# Cache disable
		if(!$this->isEnable()) {
			return null;
		}

		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.json';
		if (!is_file($path)) {
			return null;
		}

		try {
			# Read datas
			$read = file_get_contents($path);
			if(!$read) {
				throw new Exception('File content error : ' . $path);
			}

			# Decode
			$data = json_decode($read, true);

			# Cache expire
			if (!isset($data['expire']) || time() > $data['expire']) {
				unlink($path);
				return null;
			}

			# Get value
			return $data['value'] ?? null;
		}
		catch(Exception $e) {
			$this->_GetException = $e;
			return null;
		}
	}

	/**
	 * SET
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $delay [optional]
	 * @return $this
	 * @throws Exception
	 */
	public function set(string $key, $data, string $delay = ''): Json
	{
		# Clear
		$this->_SetException = null;

		# Cache disable
		if(!$this->isEnable()) {
			return $this;
		}

		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.json';
		$tmp = $this->path . DIRECTORY_SEPARATOR . $key . '.json' . uniqid('_temp_', true);

		# Expire Delay
		$expire = (new DateTime)
			->add($delay ? new DateInterval($delay) : $this->delay)
			->getTimestamp();

		try {
			# Encode
			$json = json_encode(['expire' => $expire, 'value' => $data]);

			# Write to temp file first to ensure atomicity
			// @link https://blogs.msdn.microsoft.com/adioltean/2005/12/28/how-to-do-atomic-writes-in-a-file
			if (!file_put_contents($tmp, $json, LOCK_EX)) {
				throw new Exception("Can't write data in file : $tmp");
			}
			rename($tmp, $path);

			# All access right for all
			chmod($path, 0777);
		}
		catch(Exception $e) {
			$this->_SetException = $e;
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * DELETE
	 *
	 * @param string $key
	 * @return $this
	 */
	public function delete(string $key): Json
	{
		# Cache disable
		if(!$this->isEnable()) { return $this; }

		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.json';
		if (!is_file($path)) {
			return $this;
		}

		# Delete
		unlink($path);

		# Maintain chainability
		return $this;
	}

	/**
	 * CLEAR
	 *
	 * @return $this
	 */
	public function clear(): Json
	{
		# Cache disable
		if(!$this->isEnable()) {
			return $this;
		}

		# Get all files (even hidden)
		$files = glob($this->path . '/{,.}*', GLOB_BRACE);

		# Delete all
		foreach($files as $file) {
			if(is_file($file)) {
				unlink($file);
			}
		}

		# Maintain chainability
		return $this;
	}
}