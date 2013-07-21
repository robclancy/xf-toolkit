<?php namespace XfToolkit\FileSync;

use XfToolkit\Console\ServiceProvider;
use XfToolkit\Console\Application;

class FileSyncServiceProvider implements ServiceProvider {

	public function register(Application $application)
	{
		$commands = array('WriteFilesCommand', 'SyncCommand', 'SetupCommand');

		foreach ($commands AS $command)
		{
			$application->resolve('XfToolkit\FileSync\Console\\'.$command);
		}
	}
}
