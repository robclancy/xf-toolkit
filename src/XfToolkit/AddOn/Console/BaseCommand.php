<?php namespace XfToolkit\AddOn\Console;

use XfToolkit\Console\Command;

use XfToolkit\AddOn\XenForo\AddOn;

abstract class BaseCommand extends Command {

	protected $addOnModel;

	public function __construct(AddOn $addOnModel)
	{
		$this->addOnModel = $addOnModel;

		parent::__construct();
	}
}