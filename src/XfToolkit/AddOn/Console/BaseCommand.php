<?php namespace XfToolkit\AddOn\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use XfToolkit\AddOn\XenForo\AddOn;
use Illuminate\Filesystem\Filesystem;

abstract class BaseCommand extends Command {

	protected $addOnModel;

	protected $files;

	public function __construct(Application $app, AddOn $addOnModel, FileSystem $files)
	{
		$this->addOnModel = $addOnModel;

		$this->files = $files;

		parent::__construct($app);
	}
}