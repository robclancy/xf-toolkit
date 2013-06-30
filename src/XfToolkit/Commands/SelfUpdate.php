<?php namespace XfToolkit\Commands;

use XfToolkit\Command;

class SelfUpdate extends Command {

	protected $name = 'self-update';

	protected $description = 'Updates XenForo Developer Toolkit to the latest version.';

	protected $help = 'The <info>self-update</info> command checks github.com for newer
versions of the toolkit and if found, installs the latest.

<info>php xf self-update</info>';

	public function fire()
	{
		$this->comment('Updating XenForo Developer Toolkit to latest');
		$this->info('Running git pull && composer install...');
		system('cd '.realpath(__DIR__.'/../../../').' && git pull && composer install');
	}
}
