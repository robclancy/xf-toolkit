<?php namespace XfToolkit\FileSync\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use Illuminate\Filesystem\Filesystem;
use XfToolkit\FileSync\XenForo\Template;

abstract class BaseCommand extends Command {

	protected $templateModel;

	protected $fileSystem;

	public function __construct(Application $app, Template $template, Filesystem $fileSystem)
	{
		$this->templateModel = $template;

		$this->fileSystem = $fileSystem;

		parent::__construct($app);
	}
}