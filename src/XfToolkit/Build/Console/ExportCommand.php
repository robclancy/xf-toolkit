<?php namespace XfToolkit\Build\Console;

use XfToolkit\Console\Command;
use XfToolkit\Console\Application;
use XfToolkit\AddOn\XenForo\AddOn;
use Illuminate\Filesystem\Filesystem;
use XfToolkit\FileSync\XenForo\Template;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExportCommand extends Command {

	protected $name = 'export';

	protected $description = 'Export add-on data to a directory';

	protected $fileSystem;

	protected $addonModel;

	protected $templateModel;

	protected $addonId;

	protected $directory;

	public function __construct(Application $app, Filesystem $fileSystem, AddOn $addonModel, Template $templateModel)
	{
		$this->fileSystem = $fileSystem;
		$this->addonModel = $addonModel;
		$this->templateModel = $templateModel;

		parent::__construct($app);
	}

	public function fire()
	{
		$this->addonId = $this->argument('addon-id');
		$directory = rtrim($this->argument('directory'), '/');
		$config = $this->getConfig($directory);
		$this->dataDirectory = rtrim($config->data, '/').'/';
		$this->templateDirectory = rtrim($config->templates, '/').'/';

		if ( ! $addon = $this->addonModel->getById($this->addonId))
		{
			throw new \RuntimeException('Add-on doesn\'t exist');
		}

		if ( ! file_exists($this->dataDirectory))
		{
			throw new \RuntimeException('Data Directory doesn\'t exist');
		}

		if ( ! file_exists($this->templateDirectory))
		{
			throw new \RuntimeException('Template Directory doesn\'t exist');
		}

		if ($lib = $this->argument('library'))
		{
			// TODO: info
			$this->fileSystem->copyDirectory($lib, $directory.'/'.$config->library);
		}

		$this->fileSystem->cleanDirectory($this->dataDirectory);

		$dataTypes = array(
			'Admin Navigation',
			'Admin Permissions',
			'Code Events',
			'Code Event Listeners',
			'Cron',
			'Email Templates',
			'Options',
			'Permissions',
			'Phrases',
			'Route Prefixes',
		);

		$this->line('Exporting add-on data for <info>'.$addon['title'].'</info> into <comment>'.$this->dataDirectory.'</comment>');
		foreach ($dataTypes AS $type)
		{
			$this->line('  - Exporting <info>'.$type.'</info>');

			$type = str_replace(' ', '', $type);
			$method = 'export'.$type;
			if (method_exists($this, $method))
			{
				$file = $this->$method();
			}
			else
			{
				$file = $this->exportGeneric($type);
			}

			//if ($file) $this->line("    <comment>$file</comment>");

			//$this->line();
		}

		$this->fileSystem->cleanDirectory($this->templateDirectory);

		$this->line();
		$this->line('Exporting templates for <info>'.$addon['title'].'</info> into <comment>'.$this->templateDirectory.'</comment>');
		foreach (array('Admin Templates', 'Templates') AS $type)
		{
			$this->line('  - Exporting <info>'.$type.'</info>');
			$this->{'export'.str_replace(' ', '', $type)}();
		}
	}

	protected function getConfig($directory)
	{
		if ( ! file_exists($directory))
		{
			throw new \RuntimeException('Directory doesn\'t exist');
		}

		// We require a build.json file to get information about the add-on from
		if ( ! file_exists($directory.'/xenbuild.json'))
		{
			throw new \RuntimeException('xenbuild.json not found, can\'t import without it');
		}

		$config = json_decode(file_get_contents($directory.'/xenbuild.json'));
		if (is_null($config))
		{
			throw new \RuntimeException('xenbuild.json doesn\'t contain valid json');
		}

		$required = array('id', 'name', 'version');
		foreach ($required AS $r)
		{
			if ( ! isset($config->$r))
			{
				throw new \RuntimeException('build.json is invalid, '.$r.' needs to be defined');
			}
		}

		$defaults = array(
			'version_id' => '{revision}',
			'library' => false,
			'installer' => false,
			'website' => '',
			'data' => $directory.'/data',
			'templates' => $directory.'/templates',
			'composer' => false,
		);

		foreach ($defaults AS $key => $value)
		{
			if ( ! isset($config->$key))
			{
				$config->$key = $value;
			}
		}

		if ($config->library AND ! $config->installer AND file_exists($directory.'/'.$config->library.'/Installer.php'))
		{
			$config->installer = $config->library.'/Installer.php';
		}

		return $config;
	}

	// Some types can use this instead if the method names and models match up
	protected function exportGeneric($type)
	{
		$snake = strtolower(\Zend_Filter::filterStatic($type, 'Word_CamelCaseToUnderscore'));
		list ($dom, $root) = $this->createNewDom($snake);

		$model = $type;
		if (substr($model, strlen($model)-1) == 's')
		{
			$model = substr($model, 0, -1);
		}
		$model = $this->addonModel->getModel($model);

		$model->{'append'.$type.'AddOnXml'}($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.$snake.'.xml', $dom);
	}

	protected function exportAdminPermissions()
	{
		$model = $this->addonModel->getModel('Admin');
		list ($dom, $root) = $this->createNewDom('admin_permissions');

		$model->appendAdminPermissionsAddOnXml($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.'admin_permissions.xml', $dom);
	}

	protected function exportCodeEvents()
	{
		$model = $this->addonModel->getModel('CodeEvent');
		list ($dom, $root) = $this->createNewDom('code_events');

		$model->appendEventsAddOnXml($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.'code_events.xml', $dom);
	}

	protected function exportCodeEventListeners()
	{
		$model = $this->addonModel->getModel('CodeEvent');
		list ($dom, $root) = $this->createNewDom('code_event_listeners');

		$model->appendEventListenersAddOnXml($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.'code_event_listeners.xml', $dom);
	}

	protected function exportCron()
	{
		$model = $this->addonModel->getModel('Cron');
		list ($dom, $root) = $this->createNewDom('cron');

		$model->appendCronEntriesAddOnXml($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.'cron.xml', $dom);
	}

	protected function exportOptions()
	{
		$model = $this->addonModel->getModel('Option');
		list ($dom, $root) = $this->createNewDom('optiongroups');

		$model->appendOptionsAddOnXml($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.'options.xml', $dom);
	}

	//array('route_prefixes', 'XenForo_Model_RoutePrefix', 'appendPrefixesAddOnXml'),
	protected function exportRoutePrefixes()
	{
		$model = $this->addonModel->getModel('RoutePrefix');
		list ($dom, $root) = $this->createNewDom('route_prefixes');

		$model->appendPrefixesAddOnXml($root, $this->addonId);

		return $this->saveDom($this->dataDirectory.'route_prefixes.xml', $dom);
	}

	protected function exportAdminTemplates()
	{
		return $this->writeTemplates(true);
	}

	protected function exportTemplates()
	{
		return $this->writeTemplates();
	}

	protected function writeTemplates($admin = false)
	{
		$templates = $this->templateModel->getTemplates($admin ? -1 : 0);

		foreach ($templates AS $template)
		{
			if ($template['addon_id'] != $this->addonId)
			{
				continue;
			}

			$path = $this->templateDirectory.($admin ? 'admin/' : '').$template['title'].'.xml';

			if ( ! $this->fileSystem->exists(dirname($path)))
			{
				$this->fileSystem->makeDirectory(dirname($path), 0777, true);
			}

			$xml = '<template title="'.$template['title'].'"';
			if ( ! $admin)
			{
				$xml .= ' version_id="'.$template['version_id'].'" version_string="'.$template['version_string'].'"';
			}

			$xml .= '><![CDATA['.$template['template'].']]></template>';

			$this->fileSystem->put($path, $xml);
		}

		return $this->templateDirectory.($admin ? 'admin/' : '');
	}

	protected function createNewDom($rootName)
	{
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$root = $dom->createElement($rootName);
		$dom->appendChild($root);

		return array($dom, $root);
	}

	protected function saveDom($path, $dom)
	{
		$this->fileSystem->put($path, $dom->saveXml());

		return $path;
	}

	protected function getArguments()
	{
		return array(
			array('addon-id', InputArgument::REQUIRED, 'Add-on ID you wish to export'),
			array('directory', InputArgument::REQUIRED, 'Directory where the xenbuild.json file is located'),
			array('library', InputArgument::REQUIRED, ''),
		);
	}

	protected function getOptions()
	{
		return array();
	}
}


/*
<?php

class CLI_Xf_Buildexport extends CLI
{
	protected $_help = 'xf buildexport addonid path';

	public function run($addonId, $path)
	{
		$addOn = XenForo_Model::create('XenForo_Model_AddOn')->getAddonById($addonId);

		$fileExport = new ExportHelper('addon');
		$rootNode = $fileExport->getRootNode();
		$rootNode->setAttribute('addon_id', $addOn['addon_id']);
		$rootNode->setAttribute('title', $addOn['title']);
		$rootNode->setAttribute('version_string', 'Build: {@revision}');
		$rootNode->setAttribute('version_id', '{@revision}');
		$rootNode->setAttribute('url', $addOn['url']);
		$rootNode->setAttribute('install_callback_class', $addOn['install_callback_class']);
		$rootNode->setAttribute('install_callback_method', $addOn['install_callback_method']);
		$rootNode->setAttribute('uninstall_callback_class', $addOn['uninstall_callback_class']);
		$rootNode->setAttribute('uninstall_callback_method', $addOn['uninstall_callback_method']);
		$fileExport->save($path . '/addon.xml');

		$exports = array(
			'admin_navigation',
			array('admin_permissions', 'XenForo_Model_Admin'),
			'admin_templates',
			array('code_events', false, 'appendEventsAddOnXml'),
			array('code_event_listeners', 'XenForo_Model_CodeEvent', 'appendEventListenersAddOnXml'),
			array('cron', false, 'appendCronEntriesAddOnXml'),
			'email_templates',
			array('options', false, false, 'optiongroups'),
			'permissions',
			'phrases',
			array('route_prefixes', 'XenForo_Model_RoutePrefix', 'appendPrefixesAddOnXml'),
			'templates'
		);

		foreach ($exports AS $export)
		{
			$model = false;
			$method = false;
			$name = false;
			if (is_array($export))
			{
				if ( ! empty($export[1]))
				{
					$model = $export[1];
				}
				if ( ! empty($export[2]))
				{
					$method = $export[2];
				}
				if ( ! empty($export[3]))
				{
					$name = $export[3];
				}
				$export = $export[0];
			}

			$camel = Zend_Filter::filterStatic($export, 'Word_UnderscoreToCamelCase');
			if ( ! $model)
			{
				$model = 'XenForo_Model_' . $camel;
				if (substr($model, strlen($model) - 1) == 's')
				{
					$model = substr($model, 0, -1);
				}
			}
			$model = XenForo_Model::create($model);
			$fileExport = new ExportHelper($name ? $name : $export);
			if ( ! $method)
			{
				$method = 'append' . $camel . 'AddonXml';
			}
			$model->$method($fileExport->getRootNode(), $addonId);
			$fileExport->save($path . '/' . $export . '.xml');
		}

		// Well that code was meant to be better than that... oh well, these following ones are better done seperate
		foreach (array(-1 => 'admin_', 0 => '') AS $styleId => $prefix)
		{
			$model = XenForo_Model::create('XenForo_Model_StyleProperty');
			$fileExport = new ExportHelper($prefix . 'style_properties');
			$model->appendStylePropertyXml($fileExport->getRootNode(), $styleId, $addonId);
			$fileExport->save($path . '/' . $prefix . 'style_properties.xml');
		}

		// Hardcode for now
		$file = $path . '/../library/Merc/' . str_replace('merc', '', $addonId) . '/FileSums.php';
		if (file_exists($file))
		{
			$hashes = XenForo_Helper_Hash::hashDirectory(realpath($path . '/../'), array('.js', '.php'));

			$remove = substr(realpath(dirname($file)), 0, strpos(realpath(dirname($file)), 'library'));
			foreach ($hashes AS $k => $h)
			{
				unset($hashes[$k]);
				$hashes[str_replace($remove, '', $k)] = $h;
			}

			file_put_contents($file, XenForo_Helper_Hash::getHashClassCode('Merc_' . str_replace('merc', '', $addonId) . '_FileSums', $hashes));
		}
	}
}

class ExportHelper
{
	protected $_dom;
	protected $_rootNode;

	public function __construct($rootNodeName)
	{
		$this->_dom = new DOMDocument('1.0', 'utf-8');
		$this->_dom->formatOutput = true;
		$this->_rootNode = $this->_dom->createElement($rootNodeName);
		$this->_dom->appendChild($this->_rootNode);
	}

	public function getRootNode()
	{
		return $this->_rootNode;
	}

	public function getDom()
	{
		return $this->_dom;
	}

	public function save($filename)
	{
		XenForo_Helper_File::makeWritableByFtpUser(dirname($filename));
		file_put_contents($filename, $this->_dom->saveXml());
	}
}*/