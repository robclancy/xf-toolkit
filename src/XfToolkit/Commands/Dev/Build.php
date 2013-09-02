<?php namespace XfToolkit\Commands\Dev;

use XfToolkit\Command;
use XfToolkit\Toolkit;
use Illuminate\Filesystem\Filesystem;
use XenForo_Helper_Hash as FileHasher;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Build extends Command {

    protected $name = 'dev:build';

    protected $description = 'Build add-on from within add-on repository. Follows the {name me} standard.';

    protected $fileSystem;

    public function __construct(Toolkit $app, Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;

        parent::__construct($app);
    }

    public function fire()
    {
        $config = $this->application->getConfig();

        // TODO: composer

        $this->info('Creating upload directory');

        $this->fileSystem->cleanDirectory('build/upload');

        $libraryPath = 'library/'.ucfirst(camel_case($config->vendor));
        if ($this->fileSystem->exists($libraryPath))
        {
            $this->line('   - copying '.$libraryPath.' into build/upload/'.$libraryPath);
            $this->fileSystem->copyDirectory($libraryPath, 'build/upload/'.$libraryPath);
        }

        $jsPath = 'js/'.snake_case($config->vendor);
        if ($this->fileSystem->exists($jsPath))
        {
            $this->line('   - copying '.$jsPath.' into build/upload/'.$jsPath);
            $this->fileSystem->copyDirectory($jsPath, 'build/upload/'.$jsPath);
        }

        $stylePath = 'styles/default/'.snake_case($config->vendor);
        if ($this->fileSystem->exists($stylePath))
        {
            $this->line('   - copying '.$stylePath.' into build/upload/'.$stylePath);
            $this->fileSystem->copyDirectory($stylePath, 'build/upload/'.$stylePath);
        }

        if ($config->files)
        {
            foreach ($config->files as $file)
            {
                if ($this->fileSystem->exists($file))
                {
                    $this->line('   - copying '.$file.' into build/upload/'.$file);
                    $this->fileSystem->copy($file, 'build/upload/'.$file);
                }
            }
        }

        if ($config->file_health_class)
        {
            $file = 'build/upload/library/'.str_replace(['\\', '_'], '/', $config->file_health_class).'.php';
            $contents = FileHasher::getHashClassCode($config->file_health_class, FileHasher::hashDirectory('build/upload', ['.php', '.js']), [$file]);
            
            $this->line('   - writing file sums to '.$file);
            $this->fileSystem->put($file, str_replace(['build/upload/', '  ', ');'], ['', "\t\t\t", "\t\t);"], $contents));
        }

        $this->info('Creating add-on XML');
        $this->line('   - writing add-on xml to build/addon.xml');

        $this->fileSystem->put('build/addon.xml', $this->buildAddOn($this->application->getConfig()));
    }

    protected function buildAddOn($config)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $addon = $dom->createElement('addon');
        $dom->appendChild($addon);

        // NOTE: we start at 1000+ to use nicer longer numbers in the installer and to avoid collisions with old->new system.
        $revision = 1000+(int)trim(shell_exec('git rev-list HEAD | wc -l'));

        $addon->setAttribute('addon_id', $config->id);
        $addon->setAttribute('title', $config->name);
        $addon->setAttribute('version_string', $config->version);
        $addon->setAttribute('version_id', $revision);
        $addon->setAttribute('url', $config->website);
        $addon->setAttribute('install_callback_class', $config->installer);
        $addon->setAttribute('install_callback_method', $config->installer ? 'install' : '');
        $addon->setAttribute('uninstall_callback_class', $config->installer);
        $addon->setAttribute('uninstall_callback_method', $config->installer ? 'uninstall' : '');

        $xml = str_replace('/>', '>', $dom->saveXML());
        foreach ($this->fileSystem->glob($config->data.'/*.xml') AS $file)
        {
            $xml .= str_replace('<?xml version="1.0" encoding="utf-8"?>', '', file_get_contents($file));
        }

        $xml .= "\n<templates>";
        foreach ($this->fileSystem->glob($config->templates.'/*.xml') AS $file)
        {
            $xml .= "\n".file_get_contents($file);
        }
        $xml .= "\n</templates>";

        $xml .= "\n<admin_templates>";
        foreach ($this->fileSystem->glob($config->templates.'/admin/*.xml') AS $file)
        {
            $xml .= "\n".file_get_contents($file);
        }
        $xml .= "\n</admin_templates>";

        $xml .= "\n\n</addon>";

        return $xml;
    }
}