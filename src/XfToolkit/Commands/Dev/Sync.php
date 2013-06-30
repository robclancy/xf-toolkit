<?php namespace XfToolkit\Commands\Dev;

use XfToolkit\Command;
use XfToolkit\Toolkit;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Sync extends Command {

	protected $name = 'dev:sync';

	protected $description = 'Install/update an add-on from within an add-on repository. Follows the {name me} standard.';

	protected $fileSystem;

	public function __construct(Toolkit $app, Filesystem $fileSystem)
	{
		$this->fileSystem = $fileSystem;

		parent::__construct($app);
	}

	public function fire()
	{
		$this->info('Running composer install...');
		system('composer install');
		$this->line('');

		$xmlPath = $this->buildAddOn($this->application->getConfig()->data, $this->application->getConfig());

		$this->info('Installing (or updating) add-on from created addon.xml...');
		$this->call('addon:install', array('file' => array($xmlPath), '--update-if-exists' => true));

		$this->fileSystem->delete($xmlPath);
	}

	protected function buildAddOn($directory, $config)
	{
		$this->info('Building addon.xml for install/update');

		$this->line('  - adding add-on meta data from xenbuild.json');

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
			$this->line('  - adding data '.str_replace($config->data.'/', '', $file));
			$xml .= str_replace('<?xml version="1.0" encoding="utf-8"?>', '', file_get_contents($file));
		}

		$xml .= "\n<templates>";
		foreach ($this->fileSystem->glob($config->templates.'/*.xml') AS $file)
		{
			$this->line('  - adding template '.str_replace($config->templates.'/', '', $file));
			$xml .= "\n".str_replace('<?xml version="1.0" encoding="utf-8"?>', '', file_get_contents($file));
		}
		$xml .= "\n</templates>";

		$xml .= "\n<admin_templates>";
		foreach ($this->fileSystem->glob($config->templates.'/admin/*.xml') AS $file)
		{
			$this->line('  - adding admin template '.str_replace($config->templates.'/admin/', '', $file));
			$xml .= "\n".str_replace('<?xml version="1.0" encoding="utf-8"?>', '', file_get_contents($file));
		}
		$xml .= "\n</admin_templates>";

		$xml .= "\n\n</addon>";

		$xmlPath = $directory.'/addon.xml';
		$this->fileSystem->put($xmlPath, $xml);

		$this->line('');

		return $xmlPath;
	}
}
