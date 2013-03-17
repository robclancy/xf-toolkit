<?php namespace XfToolkit\Tools\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use XfToolkit\Tools\XenForo\Phrase;

class PhraseCommand extends Command {

	protected $name = 'phrase:create';

	protected $description = 'Add a phrase to your install';

	protected $phraseModel;

	public function __construct(Application $app, Phrase $phrase)
	{
		parent::__construct($app);

		$this->phraseModel = $phrase;
	}

	public function fire()
	{
		$title = $this->argument('title');
		if ( ! $title)
		{
			$title = $this->ask('Phrase Title:');
		}

		// TODO: check if phrase exists here instead of when inserting

		$text = $this->argument('text');
		if ( ! $text)
		{
			$text = $this->ask('Text:');
		}

		$global = strtolower($this->ask('Globally Cached? [N, y]')) == 'y';

		$this->info('Adding phrase...');
		$this->info($this->phraseModel->insert($title, $text,  $global, $this->option('addon_id')));
	}

	protected function getArguments()
	{
		return array(
			array('title', InputArgument::OPTIONAL, 'The title for the phrase'),
			array('text', InputArgument::OPTIONAL, 'The text for the phrase'),
		);
	}

	protected function getOptions()
	{
		return array(
			array('addon_id', null, InputOption::VALUE_OPTIONAL, 'AddOn ID.', null)
		);
	}
}