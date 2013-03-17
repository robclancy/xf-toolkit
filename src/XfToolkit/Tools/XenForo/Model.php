<?php namespace XfToolkit\Tools\XenForo;

use XenForo_DataWriter;
use XenForo_Model;
use XenForo_Application;

abstract class Model {

	public function getDw($dw)
	{
		if (strpos($dw, '_') === false)
		{
			$dw = 'XenForo_DataWriter_'.$dw;
		}

		return XenForo_DataWriter::create($dw);
	}

	public function getModel($model)
	{
		if (strpos($model, '_') === false)
		{
			$model = 'XenForo_Model_'.$model;
		}

		return XenForo_Model::create($model);
	}

	public function getConfig()
	{
		return XenForo_Application::getConfig();
	}

	public function getDb()
	{
		return XenForo_Application::getDb();
	}

	public function getApp()
	{
		return XenForo_Application::getInstance();
	}
}