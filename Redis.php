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

	/** @var \Redis|\Predis\Client */
	private $_oConnection = null;

	/** @var RedisAdapter */
	private $_oRedis = null;

	/** @var bool */
	private $_bConnectionError = false;

	/** @var bool */
	private $_bGetError = false;

	/** @var bool */
	private $_bSetError = false;

	/** @var DateInterval */
	private $_oExpireDelay = null;

	/**
	 * Redis constructor.
	 *
	 * @param string $sNamespace
	 * @param string $sDsn [optional]
	 * @param array $aOptions [optional]
	 */
	public function __construct($sNamespace, $sDsn = 'redis://localhost', $aOptions = []) {

		try {

			# CREATE REDIS CONNEXION
			$this->_oConnection = RedisAdapter::createConnection($sDsn, $aOptions);

			# INIT CACHE
			$this->_oRedis = new RedisAdapter($this->_oConnection, strtolower($sNamespace));

			# INIT DELAY
			$this->setExpireDelay();

		}

		# BACKUP
		catch(Exception $oException) {

			$this->_bConnectionError = true;

		}

	}

	/**
	 * VERIFY REDIS CONNECTION
	 *
	 * @return bool
	 */
	public function isConnected() {
		return null !== $this->_oConnection && null !== $this->_oRedis && !$this->_bConnectionError;
	}

	/**
	 * ERROR
	 *
	 * @return bool
	 */
	public function isError() {
		return $this->_bConnectionError || $this->_bGetError || $this->_bSetError;
	}

	/**
	 * SET EXPIRE DELAY
	 *
	 * @param string $sDateIntervalDelay [optional]
	 * @return $this
	 */
	public function setExpireDelay($sDateIntervalDelay = 'PT15M') {
		$this->_oExpireDelay = new DateInterval($sDateIntervalDelay);
		return $this;
	}

	/**
	 * GET
	 *
	 * @param string $sName
	 * @return mixed|null
	 */
	public function get($sName) {

		# Clear
		$this->_bGetError = false;

		try {
			# Init cache item
			$oCache = $this->_oRedis->getItem($sName);

			# Tr retrieve datas or null
			return $oCache->isHit() ? $oCache->get() : null;
		}
		catch(Exception $oException) {

			$this->_bGetError = true;
			return null;

		}

	}

	/**
	 * SET
	 *
	 * @param string $sName
	 * @param mixed $mDatas
	 * @param string $sTime [optional]
	 * @return $this
	 */
	public function set($sName, $mDatas, $sTime = '') {

		# Clear
		$this->_bSetError = false;

		# Expire Delay
		$oDelay = $sTime ? new DateInterval($sTime) : $this->_oExpireDelay;

		try {
			# Init cache item
			$oCache = $this->_oRedis->getItem($sName);

			# Set datas
			$oCache->set($mDatas);

			# Set expire delay
			$oCache->expiresAfter($oDelay);

			# Save
			$this->_oRedis->save($oCache);
		}
		catch(Exception $oException) {

			$this->_bSetError = true;

		}

		return $this;
	}

	/**
	 * CLEAR
	 *
	 * @return $this
	 */
	public function clear() {
		$this->_oRedis->clear();
		return $this;
	}

}