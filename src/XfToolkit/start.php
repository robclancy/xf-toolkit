<?php

use Illuminate\Container\Container;
use Symfony\Component\Console\Output\ConsoleOutput;

$toolkit = new XfToolkit\Toolkit(new Container);

try 
{
	$toolkit->detectXenForo();

	require_once $toolkit->getXfLibPath().'/XenForo/Autoloader.php';
	XenForo_Autoloader::getInstance()->setupAutoloader($toolkit->getXfLibPath());

	// We try for the config in the library folder, if not try the root (one up)
	$configPath = $toolkit->getXfLibPath();
	if ( ! file_exists($configPath.'/config.php'))
	{
		$configPath .= '/../';
	}

	XenForo_Application::setDebugMode(true);
	XenForo_Application::initialize($configPath, $toolkit->getXfLibPath().'/../');

	$dependencies = new XenForo_Dependencies_Public();
	$dependencies->preLoadData();
}
catch (Exception $e)
{
	$toolkit->renderException($e, new ConsoleOutput);
	exit;
}

return $toolkit;
