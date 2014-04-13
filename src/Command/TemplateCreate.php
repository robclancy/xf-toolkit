<?php namespace Robbo\XfToolkit\Command;

use Robbo\XfToolkit\XenForo;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TemplateCreate extends Base {
    
    protected $xenforo;
    
    protected $name = 'template:create';
    
    protected $description = 'Create a xenforo template';
    
    protected $arguments = [
        ['title', InputArgument::REQUIRED, 'Title|Name of the template'],
        ['template', InputArgument::OPTIONAL, 'Contents of the template']
    ];
    
    // $name, $shortcut, $mode, $description, $default
    protected $options = [
        ['admin', null, InputOption::VALUE_NONE, 'Template is for admin', null],
        ['addon-id', null, InputOption::VALUE_REQUIRED, 'AddOn this template belongs to', null],
        ['update-if-exists', null, InputOption::VALUE_NONE, 'Update template if it already exists.', null]
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
            return $this->createAdminTemplate();
        }
        
        return $this->createTemplate();
    }
    
    public function createTemplate()
    {
        $templateModel = $this->xenforo->model('Template');
        
        $template = $templateModel->getTemplateInStyleByTitle($this->argument('title'));
        if ($template)
        {
            if ( ! $this->option('update-if-exists'))
            {
                return $this->line('Template already exists, aborting');
            }
            
            //$this->line('Template already exists, updating');
            
            return $this->line($this->xenforo->updateTemplate($template['template_id'], $this->argument('title'), $this->argument('template'), $this->option('addon-id')));
        }
        
        return $this->line($this->xenforo->createTemplate($this->argument('title'), $this->argument('template'), $this->option('addon-id')));
    }
    
    public function createAdminTemplate()
    {
        $templateModel = $this->xenforo->model('AdminTemplate');
        
        $template = $templateModel->getAdminTemplateByTitle($this->argument('title'));
        if ($template)
        {
            if ( ! $this->option('update-if-exists'))
            {
                return $this->line('Admin Template already exists, aborting');
            }
            
            //$this->line('Template already exists, updating');
            
            return $this->line($this->xenforo->updateAdminTemplate($template['template_id'], $this->argument('title'), $this->argument('template'), $this->option('addon-id')));
        }
        
        return $this->line($this->xenforo->createAdminTemplate($this->argument('title'), $this->argument('template'), $this->option('addon-id')));
    }
}


