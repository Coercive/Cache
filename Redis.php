<?php
namespace Coercive\Utility\Cache;

use Exception;
use DateInterval;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * REDIS HANDLER
 *
 * @package		Coercive\Utility\Cache
 * @link		@link https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2017 - 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class Redis {

	/**
	 * Redis error : Cache key contains reserved characters {}()/\@:
	 * Replace by decimal code &#...;
	 */
	const RESERVED_CHARS = [
		'#123' => '{',
		'#125' => '}',
		'#40' => '(',
		'#41' => ')',
		'#47' => '/',
		'#92' => '\\',
		'#64' => '@',
		'#58' => ':',
	];

	/** @var \Redis|\Predis\Client */
	private $connection = null;

	/** @var RedisAdapter */
	private $redis = null;

	/** @var DateInterval Cache expire delay */
	private $delay = null;

	/** @var bool */
	private $_bConnectionError = false;

	/** @var bool */
	private $_bGetError = false;

	/** @var bool */
	private $_bSetError = false;

	/**
	 * CLEAN KEY
	 *
	 * @param string $key
	 */
	private function clean(string &$key)
	{
		foreach (self::RESERVED_CHARS as $sDecId => $sChar) {
			$key = str_replace($sChar, $sDecId, $key);
		}
	}

	/**
	 * Redis constructor.
	 *
	 * @param string $namespace
	 * @param string $dsn [optional]
	 * @param array $options [optional]
	 */
	public function __construct(string $namespace, string $dsn = 'redis://localhost', $options = [])
	{
		try {
			# CREATE REDIS CONNEXION
			$this->connection = RedisAdapter::createConnection($dsn, $options);

			# INIT CACHE
			$this->redis = new RedisAdapter($this->connection, strtolower($namespace));

			# INIT DELAY
			$this->setExpireDelay();
		}
		catch(Exception $e) {
			$this->_bConnectionError = true;
		}
	}

	/**
	 * VERIFY REDIS CONNECTION
	 *
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return null !== $this->connection && null !== $this->redis && !$this->_bConnectionError;
	}

	/**
	 * ERROR
	 *
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->_bConnectionError || $this->_bGetError || $this->_bSetError;
	}

	/**
	 * SET EXPIRE DELAY
	 *
	 * @param string $delay [optional]
	 * @return $this
	 */
	public function setExpireDelay(string $delay = 'PT15M'): Redis
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

		# Delete {}()/\@:
		$this->clean($key);

		try {
			# Init cache item
			$cache = $this->redis->getItem($key);

			# Try retrieve datas or null
			return $cache->isHit() ? $cache->get() : null;
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
	public function set(string $key, $data, $delay = ''): Redis
	{
		# Clear
		$this->_bSetError = false;

		# Expire Delay
		$expire = $delay ? new DateInterval($delay) : $this->delay;

		# Delete {}()/\@:
		$this->clean($key);

		try {
			# Init cache item
			$cache = $this->redis->getItem($key);

			# Set datas
			$cache->set($data);

			# Set expire delay
			$cache->expiresAfter($expire);

			# Save
			$this->redis->save($cache);
		}
		catch(Exception $e) {
			$this->_bSetError = true;
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * CLEAR
	 *
	 * @return $this
	 */
	public function clear(): Redis
	{
		$this->redis->clear();
		return $this;
	}

}