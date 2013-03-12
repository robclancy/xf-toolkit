<?php namespace XfToolkit\FileSync\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Illuminate\Filesystem\Filesystem;
use XfToolkit\FileSync\XenForo\Template;

class WriteFilesCommand extends BaseCommand {

	protected $name = 'files:write';

	protected $description = 'Write Xenforo templates and phrases to the file system';

	public function fire()
	{
		$this->writeFiles(-1);
		$this->writeFiles(0);
	}

	protected function writeFiles($styleId)
	{
		$type = 'style:'.$styleId;
		if ($styleId == -1)
		{
			$type = 'admin';
		}
		else if ($styleId == 0)
		{
			$type = 'master';
		}

		$this->info("Writing $type templates");

		$templates = $this->templateModel->getTemplates($styleId);

		$path = $this->templateModel->getRootTemplatePath();
		if ( ! $this->fileSystem->exists($path))
		{
			$this->fileSystem->makeDirectory($path, 0777, true);
		}

		$progress = $this->getHelperSet()->get('progress');
		$progress->start($this->output, count($templates));
		foreach ($templates AS $template)
		{
			$this->write("  - Writing <info>$template[addon_id] $template[title]</info>...", 2);
			
			$path = $this->templateModel->getTemplatePath($template);
			if ( ! $this->fileSystem->exists(dirname($path)))
			{
				$this->fileSystem->makeDirectory(dirname($path), 0777, true);
			}

			$this->fileSystem->put($path, $template['template']);
			$this->templateModel->updateModified($template, $this->fileSystem->lastModified($path));

			if ($this->getVerbosity() > 1)
			{
				$this->line(' done (<comment>'. $this->templateModel->displayPath($path) .'</comment>)');
			}
			else
			{
				$progress->advance();
			}
		}

		$progress->finish();
	}
}