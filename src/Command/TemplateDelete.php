<?php namespace Robbo\XfToolkit\Command;

use Robbo\XfToolkit\XenForo;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TemplateDelete extends Base {
    
    protected $xenforo;
    
    protected $name = 'template:delete';
    
    protected $description = 'Delete a xenforo template';
    
    protected $arguments = [
        ['title', InputArgument::REQUIRED, 'Title|Name of the template'],
    ];
    
    protected $options = [
        ['admin', null, InputOption::VALUE_NONE, 'Is an admin template', null],
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
            $template = $this->xenforo->model('AdminTemplate')->getAdminTemplateByTitle($this->argument('title'));
        }
        else
        {
            $template = $this->xenforo->model('Template')->getTemplateInStyleByTitle($this->argument('title'));
        }
        
        if ($template)
        {
            $dw = $this->xenforo->dataWriter($this->option('admin') ? 'AdminTemplate' : 'Template');
            $dw->setExistingData($template['template_id']);
            $dw->delete();
            
            $this->line('Deleted '.($this->option('admin') ? 'admin ' :'').'template '.$template['title']);
        }
    }
}