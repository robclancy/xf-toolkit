<?php namespace XfToolkit\Tools;

use XfToolkit\Console\ServiceProvider;
use XfToolkit\Console\Application;

class ToolsServiceProvider implements ServiceProvider {

	public function register(Application $application)
	{
		$application->resolveCommands(
			'XfToolkit\Tools\Console\PhraseCommand',
			'XfToolkit\Tools\Console\RebuildCommand'
		);
	}
}