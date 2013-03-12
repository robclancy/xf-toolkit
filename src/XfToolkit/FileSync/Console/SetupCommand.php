<?php namespace XfToolkit\FileSync\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Illuminate\Filesystem\Filesystem;
use XfToolkit\FileSync\XenForo\Template;

class SetupCommand extends BaseCommand {

	protected $name = 'files:setup';

	protected $description = 'Add the extra database columns so we can store when we last updated from a file. Calls files:write when done';

	public function fire()
	{
		$this->info('Updating schema');
		$this->line('  - adding column <info>last_modified</info> to <info>xf_template</info>');
		if ( ! $this->templateModel->updateSchema('xf_template'))
		{
			$this->comment('    xf_template was already set up');
		}
		
		$this->line('  - adding column <info>last_modified</info> to <info>xf_admin_template</info>');
		if ( ! $this->templateModel->updateSchema('xf_admin_template'))
		{
			$this->comment('    xf_admin_template was already set up');
		}

		// setup phrases

		$this->line('');

		if ( ! $this->option('no-write'))
		{
			$this->call('files:write');
		}
	}

	protected function getOptions()
	{
		return array(
			array('no-write', null, InputOption::VALUE_NONE, 'Stop this from calling files:write', null)
		);
	}
}