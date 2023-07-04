<?

namespace Uplab\Core\Traits;


trait SingletonTrait
{

	protected static $instance = null;

	// function __construct() {}

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new static();
		}

		return self::$instance;
	}

}