<?php namespace XfToolkit;

use Illuminate\Console\Application as App;
use Illuminate\Container\Container;

class Toolkit extends App {

	protected $xfPath;

	protected $xfLib;

	protected $container;

	public function __construct(Container $container)
	{
		parent::__construct('XenForo Developer Toolkit', '1.0-dev');

		$this->setContainer($container);

		$container['app'] = $this;
		$container->alias('app', 'XfToolkit\Toolkit');
	}

	/**
	 * Start a new Console application.
	 *
	 * @return BigElephant\XF-Console\Application
	 */
	public static function start($app = null)
	{
		return require __DIR__.'/start.php';
	}

	public function registerServices($providers)
	{
		$providers = is_array($providers) ? $providers : func_get_args();

		foreach ($providers AS $provider)
		{
			$provider->register($this);
		}
	}

	public function detectXenForo()
	{
		$dir = getcwd();

		// Check for a library folder
		// TODO: Possibly other checks here later if needed
		if (file_exists($dir.'/library'))
		{
			$this->xfLib = $dir.'/library';
		}

		if (is_null($this->xfLib))
		{
			throw new \Exception('Couldn\'t locate XenForo install.');
		}

		$this->xfPath = realpath($this->xfLib).'/../';
	}

	public function getXfLibPath()
	{
		return $this->xfLib;
	}

	public function getXfPath()
	{
		return $this->xfPath;
	}

	public function setContainer(Container $container)
	{
		$this->container = $container;
		$this->setLaravel($container);
	}

	public function getContainer()
	{
		return $this->container;
	}
}
