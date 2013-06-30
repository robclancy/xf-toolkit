<?php namespace XfToolkit\Commands\AddOn;

use XfToolkit\Command;
use XfToolkit\Toolkit;
use XfToolkit\XenForo\AddOn;
use Illuminate\Filesystem\Filesystem;

abstract class Base extends Command {

	protected $addOnModel;

	protected $files;

	public function __construct(Toolkit $app, AddOn $addOnModel, FileSystem $files)
	{
		$this->addOnModel = $addOnModel;

		$this->files = $files;

		parent::__construct($app);
	}
}
