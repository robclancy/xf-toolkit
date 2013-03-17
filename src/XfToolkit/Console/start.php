<?php

use XfToolkit\Console\Application;
use Illuminate\Container\Container;
use Symfony\Component\Console\Output\ConsoleOutput;

$application = new Application(new Container);

// FIXME: detect XenForo somewhere else so we can add $skipXenForo to a command instance instead
$hackySkipXenForo = array(
	'build'
);
if ( ! isset($_SERVER['argv'][1]) OR in_array($_SERVER['argv'][1], $hackySkipXenForo))
{
	return $application;
}

try 
{
	$application->detectXenForo();

	require_once $application->getXfLibPath().'/XenForo/Autoloader.php';
	XenForo_Autoloader::getInstance()->setupAutoloader($application->getXfLibPath());

	XenForo_Application::setDebugMode(true);
	XenForo_Application::initialize($application->getXfLibPath(), $application->getXfLibPath().'/../');

	$dependencies = new XenForo_Dependencies_Public();
	$dependencies->preLoadData();
}
catch (Exception $e)
{
	$application->renderException($e, new ConsoleOutput);
	exit;
}

return $application;