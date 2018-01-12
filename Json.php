<?php
namespace Coercive\Utility\Cache;

use DateTime;
use Exception;
use DateInterval;

/**
 * JSON CACHE
 *
 * @package		Coercive\Utility\Cache
 * @link		@link https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2017 - 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class Json {

	/** @var string Cache filepath */
	private $path = '';

	/** @var DateInterval Expire delay */
	private $delay = null;

	/** @var bool Class load error */
	private $_bLoadError = false;

	/** @var bool Getter error */
	private $_bGetError = false;

	/** @var bool Setter error */
	private $_bSetError = false;

	/**
	 * CLEAN KEY
	 *
	 * @param string $key
	 * @return void
	 */
	private function clean(&$key)
	{
		$key = preg_replace('`[^a-z0-9]+`i', '_', $key);
	}

	/**
	 * Json constructor.
	 *
	 * @param string $path
	 * @param string $delay [optional]
	 */
	public function __construct(string $path, $delay = 'P7D')
	{
		try {
			# Set global default delay
			$this->setExpireDelay($delay);

			# Set the cache filepath
			$this->path = realpath($path);
			if (!is_dir($this->path)) {
				# Create directory
				if (!@mkdir($path, 0777, true)) {
					throw new Exception("Can't create cache directory : $path");
				}
				$this->path = realpath($path);
			}
		}
		catch(Exception $oException) {
			$this->_bLoadError = true;
		}
	}

	/**
	 * ERROR
	 *
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->_bLoadError || $this->_bGetError || $this->_bSetError;
	}

	/**
	 * SET EXPIRE DELAY
	 *
	 * @param string $delay [optional] Date interval format
	 * @return $this
	 */
	public function setExpireDelay(string $delay = 'PT15M'): Json
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
		$this->_bGetError = false;

		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.json';
		if (!is_file($path)) { return null; }

		try {
			# Read datas
			$read = file_get_contents($path);
			if(!$read) { throw new Exception("File content error : $path"); }

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
		catch(Exception $oException) {
			$this->_bGetError = true;
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
	public function set(string $key, $data, string $delay = ''): Json
	{
		# Clear
		$this->_bSetError = false;

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
		catch(Exception $oException) {
			$this->_bSetError = true;
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
		# Clean key
		$this->clean($key);

		# Filepath
		$path = $this->path . DIRECTORY_SEPARATOR . $key . '.json';
		if (!is_file($path)) { return $this; }

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
		# Get all files (even hidden)
		$files = glob($this->path . '/{,.}*', GLOB_BRACE);

		# Delete all
		foreach($files as $file) {
			if(is_file($file)) { unlink($file); }
		}

		# Maintain chainability
		return $this;
	}

}
