<?

namespace Uplab\Core\Traits;

/**
 * Trait RegistryTrait
 * Скелет оберток для хранилищ заменяющих глобальные переменные
 *
 * @package Uplab\Core\Traits
 */
trait RegistryTrait
{
	protected static $instance;
	protected        $resources = array();

	protected function __construct()
	{
	}

	public static function init()
	{
		return new static();
	}

	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function get($key)
	{
		if (isset($this->resources[$key])) {
			return $this->resources[$key];
		}

		return null;
	}

	public function getAll()
	{
		return $this->resources;
	}

	public function set($key, $value)
	{
		if (is_null($key)) {
			$this->resources[] = $value;
		} else {
			$this->resources[$key] = $value;
		}

		return $this;
	}

	public function setArray(array $values)
	{
		foreach ($values as $key => $value) {
			if (is_numeric($key)) {
				$this->resources[] = $value;
			} else {
				$this->resources[$key] = $value;
			}
		}

		return $this;
	}

	public function remove($key)
	{
		unset($this->resources[$key]);

		return $this;
	}
}
