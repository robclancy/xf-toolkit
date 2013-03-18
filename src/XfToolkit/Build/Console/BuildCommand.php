<?php namespace XfToolkit\Build\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class BuildCommand extends Command {

	protected $name = 'build';

	protected $description = 'Build an addon from it\'s source and data';

	protected $fileSystem;

	public function __construct(Application $app, Filesystem $fileSystem)
	{
		$this->fileSystem = $fileSystem;

		parent::__construct($app);
	}

	public function fire()
	{
		if ( ! file_exists('xenbuild.json'))
		{
			throw new \RuntimeException('xenbuild.json needs to exist in the current directory');
		}

		$directory = trim($this->argument('directory'), '/');
		$config = $this->getConfig('.');

		if ($this->fileSystem->exists($directory.'/addon.xml'))
		{
			$this->fileSystem->cleanDirectory($directory);
		}

		$this->info('Building XML file');
		$xml = $this->buildAddOn('.', $config);
		
		$this->fileSystem->put($directory.'/addon.xml', $xml);

		$this->info('Creating upload directory');
		$this->fileSystem->makeDirectory($directory.'/upload');
		$this->fileSystem->copyDirectory($config->library.'/../../', $directory.'/upload/library/');

		$this->info('Updating dependencies');
		$libs = $this->getDependencies('.');
		$this->info('Copying dependencies into library');
		foreach ($libs AS $lib)
		{
			$this->fileSystem->copyDirectory($lib, $directory.'/upload/library');
		}

		$this->info('Creating zip');
		$zip = new \ZipArchive;
		if ($zip->open($directory.'/'.$config->name.'_'.$config->version.'.zip', \ZIPARCHIVE::CREATE) !== true)
		{
			throw new \Exception('Failed to create zip');
		}

		// TODO: the zippy
		// $this->info('Add-on built into '.$directory);
	}

	// FIXME: copy paste code from ImportCommand.php
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

	// FIXME: another copy
	protected function getDependencies($directory)
	{
		$handle = popen('cd '.$directory.' && composer update', 'r');
		while ( ! feof($handle))
		{
			$this->write(fread($handle, 1024));
		}
		fclose($handle);

		return require $directory.'/vendor/composer/autoload_namespaces.php';

		/*$this->info('Copying dependencies into library');
		foreach ($libs AS $lib)
		{
			$this->fileSystem->copyDirectory($lib, $this->application->getXfLibPath());
		}*/
	}

	// FIXME: also copied, mostly
	protected function buildAddOn($directory, $config)
	{
		//$this->info('Building add-on for install');

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
		$addon->setAttribute('install_callback_class', '');
		$addon->setAttribute('install_callback_method', '');
		$addon->setAttribute('uninstall_callback_class', '');
		$addon->setAttribute('uninstall_callback_method', '');

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

		return $xml;

		//$xmlPath = str_replace(dirname(dirname($config->library)), '', $config->library).'/addon.xml';
		//$this->fileSystem->put($this->application->getXfLibPath().$xmlPath, $xml);

		//return $this->application->getXfLibPath().$xmlPath;
	}

	protected function getArguments()
	{
		return array(
			array('directory', InputArgument::OPTIONAL, 'Directory to save the the build in', 'build'),
		);
	}

	protected function getOptions()
	{
		return array();
	}
}