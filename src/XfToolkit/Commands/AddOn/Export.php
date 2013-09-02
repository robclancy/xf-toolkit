<?php namespace XfToolkit\Commands\AddOn;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Export extends Base {

    protected $name = 'addon:export';

    protected $description = 'Export add-on data to XML file';
}