<?php namespace XfToolkit\AddOn\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateCommand extends BaseCommand {

	protected $name = 'addon:create';

	protected $description = 'Create the add-on folder structure';

	public function fire()
	{
		$addonId = $this->argument('addonId');
		if ( ! $addonId)
		{
			$addonId = $this->ask('Addon Id:');
		}

		$title = $this->argument('title');
		if ( ! $title)
		{
			$title = $this->ask('Title:');
		}

		$author = $this->argument('author');
		/*if ( ! $author)
		{
			$author = $this->ask('Author:');
		}*/

		$this->info('Creating directory structure');
		$this->createDirectory('build');
		$this->createDirectory('data');
		$this->createDirectory('src');
		$this->createDirectory('templates');
		$this->createDirectory('tests');
		$this->createDirectory('xf');

		$this->info('Creating default files');
		$this->createFromStub('.gitignore', 'stubs/.gitignore');
		$this->createFromStub('xenbuild.json', 'stubs/xenbuild.json', array('id' => $addonId, 'title' => $title, 'author' => $author));
		// Later $this->createFromStub('export.sh', 'stubs/export.sh', array('
	}

	protected function createDirectory($path)
	{
		if ( ! $this->files->isDirectory($path))
		{
			$this->files->makeDirectory($path, 0777, true);
		}
	}

	protected function createFromStub($path, $stub, array $replaces = array())
	{
		$stub = $this->files->get(__DIR__.'/../'.$stub);
		foreach ($replaces AS $search => $replace)
		{
			$stub = str_replace('{{'.$search.'}}', $replace, $stub);
		}

		$this->files->put($path, $stub);
	}

	protected function getArguments()
	{
		return array(
			array('addonId', InputArgument::OPTIONAL, 'The addon ID'),
			array('title', InputArgument::OPTIONAL, 'Addon Title'),
			array('author', InputArgument::OPTIONAL, 'The addon ID'),
		);
	}

	protected function getOptions()
	{
		return array();
	}
}