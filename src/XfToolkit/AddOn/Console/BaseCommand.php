<?php namespace XfToolkit\AddOn\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use XfToolkit\AddOn\XenForo\AddOn;

abstract class BaseCommand extends Command {

	protected $addOnModel;

	public function __construct(Application $app, AddOn $addOnModel)
	{
		$this->addOnModel = $addOnModel;

		parent::__construct($app);
	}
}