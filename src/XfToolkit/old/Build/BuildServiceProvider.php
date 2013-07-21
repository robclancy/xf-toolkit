<?php namespace XfToolkit\Build;

use XfToolkit\Console\Application;
use XfToolkit\Console\ServiceProvider;

class BuildServiceProvider implements ServiceProvider {

	public function register(Application $application)
	{
		$application->resolveCommands(
			'XfToolkit\Build\Console\ImportFolderCommand', 
			'XfToolkit\Build\Console\ComposerUpdateCommand',
			'XfToolkit\Build\Console\ExportCommand',
			'XfToolkit\Build\Console\BuildCommand'
		);
	}
}
