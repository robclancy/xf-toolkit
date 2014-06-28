<?php namespace Robbo\XfToolkit\Command;

use Robbo\XfToolkit\XenForo;
use Symfony\Component\Console\Input\InputOption;

class DevSetup extends Base {
    
    protected $xenforo;
    
    protected $name = 'dev:setup';
    
    protected $description = 'Setup development';
    
    public function __construct(XenForo $xenforo)
    {
        $this->xenforo = $xenforo;
        
        parent::__construct();
    }
    
    public function fire()
    {
        if ( ! $this->xenforo->model('AddOn')->getAddOnById($this->xenforo->addon()->id))
        {
            $addon = $this->xenforo->addon();
            $dw = $this->xenforo->datawriter('AddOn');
            $dw->bulkSet([
                'addon_id'          => $addon->id,
                'title'             => $addon->title,
                'version_string'    => $addon->version,
                'version_id'        => $addon->version_id,
                'url'               => $addon->url
            ]);
            $dw->save();
        }
        
        // force guard to run through everything to sync it
        exec('echo | bundle exec guard');
    }
}