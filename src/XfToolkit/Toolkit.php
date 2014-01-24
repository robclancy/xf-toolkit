<?php namespace XfToolkit;

use Illuminate\Console\Application as App;
use Illuminate\Container\Container;

class Toolkit extends App {

	protected $xfPath;

	protected $xfLib;

	protected $container;

	protected $config;

	public function __construct(Container $container)
	{
		parent::__construct('XenForo Developer Toolkit', '1.0-dev');

		$this->setContainer($container);

		$container['app'] = $this;
		$container->alias('app', 'XfToolkit\Toolkit');

		$this->registerToolkitCommands();
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

	public function registerToolkitCommands()
	{
		$this->resolveCommands(
			'XfToolkit\Commands\SelfUpdate',
			'XfToolkit\Commands\Rebuild',
			'XfToolkit\Commands\Dev\Build',
			'XfToolkit\Commands\Dev\Sync'
		);
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

		if (file_exists($dir.'/addon.json'))
		{
			$this->loadConfig($dir);
		}

		if (is_null($this->xfLib))
		{
			throw new \Exception('Couldn\'t locate XenForo install.');
		}

		$this->xfPath = realpath($this->xfLib).'/../';
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function loadConfig($directory)
	{
		if ( ! file_exists($directory))
		{
			throw new \RuntimeException('Directory doesn\'t exist');
		}

		// We require a build.json file to get information about the add-on from
		if ( ! file_exists($directory.'/addon.json'))
		{
			throw new \RuntimeException('addon.json not found.');
		}

		$config = json_decode(file_get_contents($directory.'/addon.json'));
		if (is_null($config))
		{
			throw new \RuntimeException('addon.json doesn\'t contain valid json');
		}

		$required = array('id', 'name', 'version');
		foreach ($required AS $r)
		{
			if ( ! isset($config->$r))
			{
				throw new \RuntimeException('build.json is invalid, '.$r.' needs to be defined');
			}
		}

		$defaults = array(
			'version_id' => '{revision}',
			'files' => array(),
			'installer' => false,
			'website' => '',
			'data' => $directory.'/addon',
			'templates' => $directory.'/addon/templates',
			'composer' => false,
			'installer' => false,
			'includes' => array(),
		);

		foreach ($defaults AS $key => $value)
		{
			if ( ! isset($config->$key))
			{
				$config->$key = $value;
			}
		}

		/*if ($config->library AND ! $config->installer AND file_exists($directory.'/'.$config->library.'/Installer.php'))
		{
			$config->installer = $config->library.'/Installer.php';
		}*/

		$this->config = $config;
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
