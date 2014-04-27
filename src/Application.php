<?php namespace Robbo\XfToolkit;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Application as App;

class Application extends App {

    protected $container;

    public function __construct(Container $container)
    {
        parent::__construct('XenForo Developer Toolkit', '1.0-dev');
        
        $this->setContainer($container);
        
        $container['app'] = $this;
        $container->alias('app', 'Robbo\XfToolkit\Application');
        
        $container->bindShared('xenforo', function($container) { return new XenForo($this, new Filesystem); });
        $container->alias('xenforo', 'Robbo\XfToolkit\XenForo');
        
        $this->registerBundledCommands();
    }
    
    public static function start($app)
    {
        return require __DIR__.'/start.php';
    }

    public function registerBundledCommands()
    {
        foreach (glob(__DIR__.'/Command/*.php') as $file)
        {
            $class = basename($file, '.php');
            if ($class == 'Base') continue;

            $this->resolve('Robbo\XfToolkit\Command\\'.$class);
        }
    }
    
    public function setContainer(Container $container)
    {
        $this->container = $container;
        $this->setLaravel($container);
    }

    public function getContainer()
    {
        return $this->container;
    }
}