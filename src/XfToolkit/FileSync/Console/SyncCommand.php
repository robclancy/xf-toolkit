<?php namespace XfToolkit\FileSync\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Illuminate\Filesystem\Filesystem;
use XfToolkit\FileSync\XenForo\Template;

class SyncCommand extends BaseCommand {

	protected $name = 'files:sync';

	protected $description = 'Sync your templates and phrases between database and file system';

	public function fire()
	{
		if ($this->option('repeat')) $this->input->setOption('looping', true);

		do
		{
			$this->syncTemplates(-1);
			$this->syncTemplates(0);
		}
		while ($this->option('repeat') AND sleep(1) === 0);
	}

	protected function syncTemplates($styleId)
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

		if ( ! $this->option('looping'))
		{
			$this->info('Syncing ' . $type . ' templates');
		}

		$path = $this->templateModel->getStylePath($styleId);
		if ( ! $this->fileSystem->exists($path))
		{
			$this->error(ucfirst($type) . ' templates don\'t exist in ' . $path . ', maybe you need to create them with files:write');
			return;
		}

		$templates = $this->templateModel->getTemplates($styleId);

		// A check to see if we are setup
		if ( ! isset($templates[key($templates)]['last_modified']))
		{
			$this->error(ucfirst($type) . ' templates haven\'t been setup yet, you need to run files:setup');
			return;
		}

		$fileMap = array();
		foreach ($templates AS $templateId => $template)
		{
			$fileMap[$templateId] = $this->templateModel->getTemplatePath($template);
		}

		$newTemplates = array();
		foreach ($this->fileSystem->glob($path.'/*') AS $dir)
		{
			if ($this->fileSystem->isDirectory($dir))
			{
				foreach ($this->fileSystem->files($dir) AS $file)
				{
					if ( ! in_array($file, $fileMap))
					{
						$newTemplates[] = $file;
					}
				}
			}
		}

		foreach ($templates AS $template)
		{
			$filePath = $this->templateModel->getTemplatePath($template);

			if ( ! $this->fileSystem->exists($filePath))
			{
				$this->line('  - Detected deleted template <info>' . $template['title'] . '</info> (<comment>' . $template['addon_id'] . '</comment>)');

				$backupPath = $this->templateModel->backup($template);
				$this->line('    Backed up to <comment>' . $backupPath . '</comment>');

				$this->line('    Deleting from database');
				$this->line('');

				$this->templateModel->delete($template);

				continue;
			}

			if ($this->templateModel->templateUpdated($filePath, $template))
			{
				$this->line('  - Detected updates to <info>' . $template['title'] . '</info> (<comment>' . $template['addon_id'] . '</comment>)');
				$this->line('    Updating database from <comment>' . $this->templateModel->displayPath($filePath) . '</comment>');
				$this->line('');

				$this->templateModel->update($template);
			}
		}

		foreach ($newTemplates AS $file)
		{
			$this->line('  - Detected new file <info>' . $this->templateModel->displayPath($file) . '</info>');
			
			$template = $this->templateModel->getTemplateInfoFromFile($file);

			$this->line('    Adding ' . ($template['style_id'] == -1 ? 'admin template' : 'template') . ' <info>' . $template['title'] . '</info> to database (<comment>' . $template['addon_id'] . '</comment>)');
			$this->templateModel->create($template);
			
			$this->line('');
		}

		if ($this->option('repeat'))
		{

		}
	}

	protected function getOptions()
	{
		return array(
			array('looping', null, InputOption::VALUE_NONE, 'Use this for when repeating the sync to only show output for new changes.', null),
			array('repeat', null, InputOption::VALUE_NONE, 'Repeat the sync with a half second break in between', null),
		);
	}
}