<?php
namespace Coercive\Utility\Cache;

use Closure;
use Exception;

/**
 * ABSTRACT CACHE
 *
 * @package		Coercive\Utility\Cache
 * @link		https://github.com/Coercive/Cache
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2022 Anthony Moral
 * @license 	MIT
 */
abstract class AbstractCache
{
	/**
	 * Get cached data
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	abstract public function get(string $key);

	/**
	 * Set data in cache
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $delay [optional]
	 * @return $this
	 */
	abstract public function set(string $key, $data, string $delay = ''): AbstractCache;

	/**
	 * Delete cached data
	 *
	 * @param string $key
	 * @return $this
	 */
	abstract public function delete(string $key): AbstractCache;

	/**
	 * Clear cached data
	 *
	 * @return $this
	 */
	abstract public function clear(): AbstractCache;

	/**
	 * @var Exception[] list of errors throwed in process
	 */
	protected array $exceptions = [];

	/**
	 * @var Closure|null customer debug function that get Exception as parameter like : function(Exception $e) { ... }
	 */
	protected ? Closure $debug = null;

	/**
	 * Add Exception for external debug handler
	 *
	 * @param Exception $e
	 * @return void
	 */
	protected function addException(Exception $e)
	{
		$this->exceptions[] = $e;
		if(null !== $this->debug) {
			($this->debug)($e);
		}
	}

	/**
	 * Set a debug function
	 *
	 * It will log all given exceptions like :
	 * function(Exception $e) { ... }
	 *
	 * Can be reset with give no parameter
	 *
	 * @param Closure|null $function
	 * @return $this
	 */
	public function debug(? Closure $function = null): AbstractCache
	{
		$this->debug = $function;
		return $this;
	}

	/**
	 * Get Exception list for external debug
	 *
	 * @return Exception[]
	 */
	public function getExceptions(): array
	{
		return $this->exceptions;
	}
}