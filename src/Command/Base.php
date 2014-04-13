<?php namespace Robbo\XfToolkit\Command;

use Illuminate\Container\Container;
use Illuminate\Console\Command;

abstract class Base extends Command {
    
    protected $arguments = [];
    
    protected $options = [];
    
    public function call($command, array $arguments = [], array $options = [])
    {
        foreach ($options as $option => $value)
        {
            $arguments['--'.$option] = $value;
        }
        
        return parent::call($command, $arguments);
    }
    
    protected function getArguments()
    {
        return $this->arguments;   
    }
    
    protected function getOptions()
    {
        return $this->options;
    }
}
