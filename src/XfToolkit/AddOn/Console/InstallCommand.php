<?php namespace XfToolkit\AddOn\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends BaseCommand {

	protected $name = 'addon:install';

	protected $description = 'Install an add-on from an XML file';

	public function fire()
	{
		$this->info('Reading add-on package information');
		
		$files = $this->argument('file');
		if (empty($files))
		{
			throw new \RuntimeException('Not enough arguments.');
		}

		$noneInstalled = true;
		$addOns = $this->addOnModel->getAll();
		foreach ($files AS $file)
		{
			$xml = $this->addOnModel->getXml($file);
			if (isset($addOns[(string)$xml['addon_id']]))
			{
				if ($this->option('update-if-exists'))
				{
					$noneInstalled = false;
					$this->updateFromXml($xml, $addOns[(string)$xml['addon_id']]);
				}
				else
				{
					$this->line('  - Installing <info>'.$xml['title'].'</info> (<comment>'.$xml['version_id'].'</comment>)');
					$this->line('    Add-on already exists, set <comment>--update-if-exists</comment> if you wish to update');
				}
			}
			else
			{
				$noneInstalled = false;
				$this->installFromXml($xml);
			}
		}

		$this->line();

		if ($noneInstalled)
		{
			return;
		}

		if ( ! $this->option('skip-rebuild'))
		{
			$this->call('rebuild', array('caches' => array('addon')));
		}
	}

	protected function installFromXml($xml)
	{
		$this->line('  - Installing <info>'.$xml['title'].'</info> (<comment>'.$xml['version_string'].'</comment>)');
		$this->addOnModel->install($xml);
	}

	protected function updateFromXml($xml, $addon)
	{
		$this->line('  - Updating <info>'.$xml['title'].'</info> (<comment>'.$addon['version_string'].'</comment> => <comment>'.$xml['version_string'].'</comment>)');
		$this->addOnModel->install($xml, true);
	}

	protected function getArguments()
	{
		return array(
			array('file', InputArgument::IS_ARRAY, 'XML file/s to install from'),
		);
	}

	protected function getOptions()
	{
		return array(
			array('skip-rebuild', null, InputOption::VALUE_NONE, 'Skip rebuilding the caches.', null),
			array('update-if-exists', null, InputOption::VALUE_NONE, 'Update add-on if it already exists.', null)
		);
	}
}