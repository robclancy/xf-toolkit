<?php namespace Robbo\XfToolkit;

use XenForo_Model;
use XenForo_DataWriter;
use XenForo_Autoloader;
use XenForo_Application;
use XenForo_Dependencies_Public;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;

class XenForo {
    
    protected $application;
    
    protected $file;
    
    protected $path;
    
    public function __construct(Application $application, Filesystem $file)
    {
        $this->application = $application;
        $this->file = $file;

        if ( ! $this->detect())
        {
            throw new \Exception('Couldn\'t locate XenForo install.');
        }
        
        try
        {
            $this->bootstrap();
        }
        catch (\Exception $e)
        {
            $this->application->renderException($e, new ConsoleOutput);
            exit;
        }
    }
    
    public function model($class)
    {
        if (strpos($class, '\\') === false)
        {
            $class = 'XenForo_Model_'.$class;
        }
        
        return XenForo_Model::create($class);
    }
    
    public function dataWriter($class)
    {
        if (strpos($class, '\\') === false)
        {
            $class = 'XenForo_DataWriter_'.$class;
        }
        
        return XenForo_DataWriter::create($class);
    }
    
    protected function detect()
    {
        $dir = getcwd();
        
        if ($this->file->exists($dir.'/library/XenForo/Application.php'))
        {
            return $this->path = $dir;
        }
        
        if ($this->file->exists($dir.'/tests/xenforo/1.3.x/library/XenForo/Application.php'))
        {
            return $this->path = $dir.'/tests/xenforo/1.3.x';
        }
        
        return false;
    }
    
    protected function bootstrap()
    {
        if ($this->file->exists($this->path.'/vendor/autoload.php'))
        {
            require_once $this->path.'/vendor/autoload.php';
        }
        
        require_once $this->path.'/library/XenForo/Autoloader.php';
        XenForo_Autoloader::getInstance()->setupAutoloader($this->path.'/library');
        
        // Config file can be in 4 locations, try them in order
        //  - library/config.php
        //  - config.php
        //  - {current working directory}/config.php
        //  - {current working directory}/../config.php
        //  - {current working directory}/tests/xenforo/
        $cwd = exec('pwd'); // because getcwd() doesn't work properly with symlinks
        foreach ([$this->path.'/library', $this->path, $cwd, dirname($cwd), $cwd.'/tests/xenforo'] as $path)
        {
            if ($this->file->exists($path.'/config.php'))
            {
                $configPath = $path;
                break;
            }
        }
        
        if ( ! isset($configPath))
        {
            throw new \Exception('Couldn\'t locate XenForo config.php file.');
        }
        
        XenForo_Application::setDebugMode(true);
        XenForo_Application::initialize($configPath, $this->path);
        
        $dependencies = new XenForo_Dependencies_Public();
        $dependencies->preLoadData();
    }
    
    public function addOn()
    {
        return (object) require 'addon.php';
    }
    
    public function createTemplate($title, $template, $addon_id, $templateId = null)
    {
        $data = compact('title', 'template', 'addon_id');
        $data['style_id'] = 0;
        
        $writer = $this->dataWriter('Template');
        if ($templateId)
        {
            $writer->setExistingData($templateId);    
        }
        
        $writer->bulkSet($data);
        $writer->reparseTemplate();
        $writer->save();
        
        return $templateId ? 'Updated admin template '.$title : 'Created admin template '.$title;
    }
    
    public function updateTemplate($templateId, $title, $template, $addonId)
    {
        return $this->createTemplate($title, $template, $addonId, $templateId);
    }
    
    public function createAdminTemplate($title, $template, $addon_id, $templateId = null)
    {
        $data = compact('title', 'template', 'addon_id');
        
        $writer = $this->dataWriter('AdminTemplate');
        if ($templateId)
        {
            $writer->setExistingData($templateId);    
        }
        
        $writer->bulkSet($data);
        $writer->save();
        
        return $templateId ? 'Updated admin template '.$title : 'Created admin template '.$title;
    }
    
    public function updateAdminTemplate($templateId, $title, $template, $addonId)
    {
        return $this->createAdminTemplate($title, $template, $addonId, $templateId);
    }
}