<?php namespace XfToolkit\FileSync\Console;

use XfToolkit\Console\Command;

use Illuminate\Filesystem\Filesystem;
use XfToolkit\FileSync\XenForo\Template;

abstract class BaseCommand extends Command {

	protected $templateModel;

	protected $fileSystem;

	public function __construct(Template $template, Filesystem $fileSystem)
	{
		$this->templateModel = $template;

		$this->fileSystem = $fileSystem;

		parent::__construct();
	}
}