<?php namespace XfToolkit\Build\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ImportFolderCommand extends Command {

	protected $name = 'import:folder';

	protected $description = 'Import an add-on from a folder on your file system';

	protected $fileSystem;

	public function __construct(Application $app, Filesystem $fileSystem)
	{
		$this->fileSystem = $fileSystem;

		parent::__construct($app);
	}

	public function fire()
	{
		$directory = trim($this->argument('directory'), '/');

		$config = $this->getConfig($directory);

		if ($this->option('git-pull'))
		{
			// TODO: handle passwords? might use keys instead
			shell_exec('cd '.$directory.' && git pull');
		}

		if ($config->library)
		{
			$this->copyLibrary($directory.'/'.$config->library);
		}

		if ($config->composer)
		{
			$this->copyDependencies($directory);
		}

		if ( ! empty($config->includes))
		{
			$this->copyIncludes($config->includes, $directory);
		}

		$xmlPath = $this->buildAddOn($directory, $config);
		
		$this->call('addon:install', array('file' => array($xmlPath), '--update-if-exists' => true));
	}

	protected function getConfig($directory)
	{
		if ( ! file_exists($directory))
		{
			throw new \RuntimeException('Directory doesn\'t exist');
		}

		// We require a build.json file to get information about the add-on from
		if ( ! file_exists($directory.'/xenbuild.json'))
		{
			throw new \RuntimeException('xenbuild.json not found, can\'t import without it');
		}

		$config = json_decode(file_get_contents($directory.'/xenbuild.json'));
		if (is_null($config))
		{
			throw new \RuntimeException('xenbuild.json doesn\'t contain valid json');
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
			'library' => false,
			'installer' => false,
			'website' => '',
			'data' => $directory.'/data',
			'templates' => $directory.'/templates',
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

		if ($config->library AND ! $config->installer AND file_exists($directory.'/'.$config->library.'/Installer.php'))
		{
			$config->installer = $config->library.'/Installer.php';
		}

		return $config;
	}

	protected function copyLibrary($libraryPath)
	{
		// TODO: symlink

		$this->info('Copying library files');
		$this->fileSystem->copyDirectory($libraryPath.'/../../', $this->application->getXfLibPath());
	}

	protected function copyDependencies($directory)
	{
		$handle = popen('cd '.$directory.' && composer update', 'r');
		while ( ! feof($handle))
		{
			$this->write(fread($handle, 1024));
		}
		fclose($handle);

		$libs = require $directory.'/vendor/composer/autoload_namespaces.php';

		$this->info('Copying dependencies into library');
		foreach ($libs AS $lib)
		{
			$this->fileSystem->copyDirectory($lib, $this->application->getXfLibPath());
		}
	}

	protected function copyIncludes($includes, $directory)
	{
		$this->info('Copying extra includes');
		foreach ($includes AS $dir)
		{
			$this->fileSystem->copyDirectory($directory.'/'.$dir, $this->application->getXfPath());
		}
	}

	protected function buildAddOn($directory, $config)
	{
		$this->info('Building add-on for install');

		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$addon = $dom->createElement('addon');
		$dom->appendChild($addon);

		// TODO: this kind of stuff I should be doing and pushing to the repo the number instead?
		$revision = (int)trim(shell_exec('git rev-list HEAD | wc -l'));
		if (empty($revision))
		{
			$revision = 'unknown';
		}

		$addon->setAttribute('addon_id', $config->id);
		$addon->setAttribute('title', $config->name);
		$addon->setAttribute('version_string', str_replace('{revision}', $revision, $config->version));
		$addon->setAttribute('version_id', str_replace('{revision}', $revision, $config->version_id));
		$addon->setAttribute('url', str_replace('{revision}', $revision, $config->website));
		$addon->setAttribute('install_callback_class', $config->installer);
		$addon->setAttribute('install_callback_method', $config->installer ? 'install' : '');
		$addon->setAttribute('uninstall_callback_class', $config->installer);
		$addon->setAttribute('uninstall_callback_method', $config->installer ? 'uninstall' : '');

		$xml = str_replace('/>', '>', $dom->saveXML());
		foreach ($this->fileSystem->glob($config->data.'/*.xml') AS $file)
		{
			$xml .= str_replace('<?xml version="1.0" encoding="utf-8"?>', '', file_get_contents($file));
		}

		$xml .= "\n<templates>";
		foreach ($this->fileSystem->glob($config->templates.'/*.xml') AS $file)
		{
			$xml .= "\n".file_get_contents($file);
		}
		$xml .= "\n</templates>";

		$xml .= "\n<admin_templates>";
		foreach ($this->fileSystem->glob($config->templates.'/admin/*.xml') AS $file)
		{
			$xml .= $xml .= "\n".file_get_contents($file);
		}
		$xml .= "\n</admin_templates>";

		$xml .= "\n\n</addon>";

		$xmlPath = str_replace(dirname(dirname($config->library)), '', $config->library).'/addon.xml';
		$this->fileSystem->put($this->application->getXfLibPath().$xmlPath, $xml);

		return $this->application->getXfLibPath().$xmlPath;
	}

	protected function getArguments()
	{
		return array(
			array('directory', InputArgument::REQUIRED, 'Directory to import from'),
		);
	}

	protected function getOptions()
	{
		return array(
			array('no-install', null, InputOption::VALUE_NONE, 'Skip installing or updating of the add-on.', null),
			array('symlink', null, InputOption::VALUE_NONE, 'Symlink instead of hard copy, windows no supported', null),
			array('git-pull', null, InputOption::VALUE_NONE, 'Run "git pull" before importing', null),
		);
	}
}