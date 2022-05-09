<?php
namespace Coercive\Utility\Cache;

use DateTime;
use Exception;
use DateInterval;

/**
 * PHP CACHE
 *
 * @package		Coercive\Utility\Cache
 * @link		https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2020 Anthony Moral
 * @license 	MIT
 */
class Php extends AbstractCache
{
	/** @var string Cache filepath (source) */
	private string $source;

	/** @var string Cache filepath (prepared) */
	private string $path = '';

	/** @var string Global Expire delay */
	private string $delay;

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
	 * Php constructor.
	 *
	 * @param string $path
	 * @param string $delay [optional]
	 * @return void
	 */
	public function __construct(string $path, string $delay = 'P7D')
	{
		# Set the cache filepath
		$this->source = $path;

		# Set global default delay
		$this->setExpireDelay($delay);
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
	 * @return bool
	 */
	public function enable(): bool
	{
		return $this->setStatus(true);
	}

	/**
	 * Disable cache system
	 *
	 * @return bool
	 */
	public function disable(): bool
	{
		return $this->setStatus(false);
	}

	/**
	 * Enable/Disable cache system
	 *
	 * @param bool $enable
	 * @return bool
	 */
	public function setStatus(bool $enable): bool
	{
		$this->enabled = $enable;
		if($enable && !$this->path) {
			if(!$this->source) {
				$this->addException(new Exception('Cache directory is not set.'));
				return false;
			}
			if(!is_dir($this->source)) {
				if(!mkdir($this->source, 0777, true)) {
					$this->addException(new Exception('Can\'t create cache directory: ' . $this->source));
					return false;
				}
			}
			$this->path = (string) realpath($this->source);
			if(!$this->path) {
				$this->addException(new Exception('Can\'t use realpath cache directory: ' . $this->source));
				return false;
			}
		}
		return true;
	}

	/**
	 * SET EXPIRE DELAY
	 *
	 * @param string $delay [optional] Date interval format
	 * @return $this
	 */
	public function setExpireDelay(string $delay = 'PT15M'): Php
	{
		$this->delay = $delay;
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
		# Cache disable
		if(!$this->isEnable()) {
			return null;
		}

		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.php';
		if (!is_file($path)) {
			return null;
		}

		try {
			# Load
			@include $path;

			# Cache expire
			if (!isset($expire) || time() > $expire) {
				unlink($path);
				return null;
			}

			# Get value
			return $value ?? null;
		}
		catch(Exception $e) {
			$this->addException($e);
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
	 */
	public function set(string $key, $data, string $delay = ''): Php
	{
		# Cache disable
		if(!$this->isEnable()) {
			return $this;
		}

		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.php';
		$tmp = $this->path . DIRECTORY_SEPARATOR . $key . '.php' . uniqid('_temp_', true);

		try {
			# Expire Delay
			$expire = (new DateTime)
				->add(new DateInterval($delay ?: $this->delay))
				->getTimestamp();
		}
		catch(Exception $e) {
			$this->addException($e);
			return $this;
		}

		# Encode
		$export = var_export($data, true);

		# HHVM fails at __set_state, so just use object cast for now
		$export = str_replace('stdClass::__set_state', '(object)', $export);

		# To php format
		$php = '<?php $value=' . $export . ';' . '$expire=' . $expire . ';';

		# Write to temp file first to ensure atomicity
		if (!file_put_contents($tmp, $php, LOCK_EX)) {
			$this->addException(new Exception("Can't write data in file : $tmp"));
			return $this;
		}
		rename($tmp, $path);

		# All access right for all
		chmod($path, 0777);

		# Maintain chainability
		return $this;
	}

	/**
	 * DELETE
	 *
	 * @param string $key
	 * @return $this
	 */
	public function delete(string $key): Php
	{
		# Cache disable
		if(!$this->isEnable()) {
			return $this;
		}

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
	public function clear(): Php
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