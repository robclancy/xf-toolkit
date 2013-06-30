<?php namespace XfToolkit\Commands;

use XfToolkit\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Rebuild extends Command {

	protected $name = 'rebuild';

	protected $description = 'Rebuild XenForo caches.';

	public function fire()
	{
		$t = microtime(true);
		$m = memory_get_usage();

		$caches = $this->argument('caches');
		if (empty($caches))
		{
			throw new \RuntimeException('Not enough arguments.');
		}

		$validCaches = array();
		$allCaches = $this->getAllCaches();
		foreach ($caches AS $cache)
		{
			switch ($cache)
			{
				case 'all': $validCaches = $allCaches; break 2;
				case 'master': $validCaches += $this->getMasterCaches(); break;
				case 'addon': $validCaches += $this->getAddOnCaches(); break;
				default: 
					if (in_array($cache, $allCaches) AND ! in_array($cache, $validCaches))
					{ 
						$validCaches[] = $cache;
					}
			}
		}

		if (empty($validCaches))
		{
			throw new \RuntimeException('No caches were specified.');
		}

		$this->info('Caches to rebuild: '.implode(', ', $validCaches));

		foreach ($validCaches AS $cache)
		{
			// Special case and todo: search index is a bit different
			if ($cache == 'XenForo_CacheRebuilder_SearchIndex')
			{
				continue;
			}

			$this->write('  - Rebuilding <info>'.\XenForo_CacheRebuilder_Abstract::getCacheRebuilder($cache)->getRebuildMessage().'</info>..');
			$this->rebuild($cache, 0, array(), '');
			$this->line();
		}

		$t = abs(microtime(true) - $t);
		$m = abs(memory_get_usage() - $m);
		$m = $m / 1024 / 1024;

		$this->line(' Execution time: <comment>' . number_format($t, 2) . ' seconds</comment>');
		$this->line(' Memory usage: <comment>' . number_format($m, 2) . ' mb</comment>');
	}

	protected function rebuild($cache, $position, $options, $message, $t = null, $m = null)
	{
		if ($t === null)
		{
			$t = microtime(true);
			$m = memory_get_usage();
		}

		$this->write('.');

		$rebuilt = \XenForo_CacheRebuilder_Abstract::getCacheRebuilder($cache)->rebuild($position, $options, $message);

		if (is_int($rebuilt))
		{
			$this->rebuild($cache, $rebuilt, $options, $message, $t, $m);
		}
		else
		{
			$t = abs(microtime(true) - $t);
			$m = memory_get_usage() - $m;
			$m = $m / 1024 / 1024;

			$this->line();
			$this->line('    (<comment>' . number_format($t, 2). ' sec</comment>, <comment>' . number_format($m, 2) . ' mb</comment>)', false);
		}
	}

	// TODO: following 3 methods should be in the model
	protected function getAllCaches()
	{
		return array_keys(\XenForo_CacheRebuilder_Abstract::$builders);
	}

	protected function getMasterCaches()
	{
		return array(
			'ImportMasterData', 'Permission',
			'ImportPhrase', 'Phrase',
			'ImportTemplate', 'Template',
			'ImportAdminTemplate', 'AdminTemplate',
			'ImportEmailTemplate', 'EmailTemplate'
		);
	}

	protected function getAddOnCaches()
	{
		return array(
			'Permission', 
			'Phrase', 
			'Template', 
			'AdminTemplate', 
			'EmailTemplate'
		);
	}

	protected function getArguments()
	{
		return array(
			array('caches', InputArgument::IS_ARRAY, 'Caches to rebuild (list them or specify "master", "addon" or "all")'),
		);
	}

	protected function getOptions()
	{
		return array();
	}
}
