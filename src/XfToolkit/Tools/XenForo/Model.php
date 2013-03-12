<?php namespace XfToolkit\Tools\XenForo;

use XenForo_DataWriter;
use XenForo_Model;
use XenForo_Application;

abstract class Model {

	protected function getDw($dw)
	{
		if (strpos($dw, '_') === false)
		{
			$dw = 'XenForo_DataWriter_'.$dw;
		}

		return XenForo_DataWriter::create($dw);
	}

	protected function getModel($model)
	{
		if (strpos($model, '_') === false)
		{
			$model = 'XenForo_Model_'.$model;
		}

		return XenForo_Model::create($model);
	}

	protected function getConfig()
	{
		return XenForo_Application::getConfig();
	}

	protected function getDb()
	{
		return XenForo_Application::getDb();
	}

	protected function getApp()
	{
		return XenForo_Application::getInstance();
	}
}