<?php namespace Robbo\XfToolkit\Command;

use Robbo\XfToolkit\XenForo;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TemplateUpdate extends Base {
    
    protected $xenforo;
    
    protected $name = 'template:update';
    
    protected $description = 'Update a xenforo template';
    
    protected $arguments = [
        ['title', InputArgument::REQUIRED, 'Title|Name of the template'],
        ['template', InputArgument::OPTIONAL, 'Contents of the template']
    ];
    
    // $name, $shortcut, $mode, $description, $default
    protected $options = [
        ['file', null, InputOption::VALUE_REQUIRED, 'Template file, if specified the template argument is ignored', null],
        ['admin', null, InputOption::VALUE_NONE, 'Template is for admin', null],
        ['addon-id', null, InputOption::VALUE_REQUIRED, 'AddOn this template belongs to', null],
        ['create-if-not-exists', null, InputOption::VALUE_NONE, 'Create template if it doesn\'t exist.', null]
    ];
    
    public function __construct(XenForo $xenforo)
    {
        $this->xenforo = $xenforo;
        
        parent::__construct();
    }
    
    public function fire()
    {
        if ($this->option('admin'))
        {
            return $this->updateAdminTemplate();
        }
        
        return $this->updateTemplate();
    }
    
    public function updateTemplate()
    {
        $templateModel = $this->xenforo->model('Template');
        
        $template = $templateModel->getTemplateInStyleByTitle($this->argument('title'));
        if ($template)
        {
            return $this->line($this->xenforo->updateTemplate($template['template_id'], $this->argument('title'), $this->getTemplateContents(), $this->option('addon-id')));
        }
        
        if ( ! $this->option('create-if-not-exists'))
        {
            return $this->line('Template doesn\'t exist, aborting');
        }
        
        //$this->line('Template doesn\'t exist, creating');
        
        return $this->line($this->xenforo->createTemplate($this->argument('title'), $this->getTemplateContents(), $this->option('addon-id')));
    }
    
    public function updateAdminTemplate()
    {
        $templateModel = $this->xenforo->model('AdminTemplate');
        
        $template = $templateModel->getAdminTemplateByTitle($this->argument('title'));
        if ($template)
        {
            return $this->line($this->xenforo->updateAdminTemplate($template['template_id'], $this->argument('title'), $this->getTemplateContents(), $this->option('addon-id')));
        }
        
        if ( ! $this->option('create-if-not-exists'))
        {
            return $this->line('Admin Template doesn\'t exist, aborting');
        }
        
        //$this->line('Template doesn\'t exist, creating');
        
        return $this->line($this->xenforo->createAdminTemplate($this->argument('title'), $this->getTemplateContents(), $this->option('addon-id')));
    }
    
    protected function getTemplateContents()
    {
        if ($this->option('file'))
        {
            return $this->file->get($this->option('file'));
        }
        
        return $this->argument('template');
    }
}


