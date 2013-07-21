<?php namespace XfToolkit\AddOn;

use XfToolkit\Console\Application;
use XfToolkit\Console\ServiceProvider;

class AddOnServiceProvider implements ServiceProvider {

	public function register(Application $application)
	{
		$application->resolveCommands(
			'XfToolkit\AddOn\Console\InstallCommand',
			'XfToolkit\AddOn\Console\CreateCommand'
		);
	}
}
