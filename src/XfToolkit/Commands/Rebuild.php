<?php namespace XfToolkit\Commands;

use XfToolkit\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Rebuild extends Command {

	protected $name = 'rebuild';

	protected $description = 'Rebuild XenForo caches.';

	public function fire()
	{
		$status = '';
		$caches = [
			'Permission', 'Phrase', 'TemplateReparse', 'Template', 'AdminTemplateReparse', 
			'AdminTemplate', 'EmailTemplateReparse', 'EmailTemplate'
		];

		$this->info('Rebuilding caches: '.implode(', ', $caches));

		foreach ($caches as $deferred)
		{
			\XenForo_Deferred_Abstract::create($deferred)->execute([], [], null, $status);
			$this->line($status);
		}
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
