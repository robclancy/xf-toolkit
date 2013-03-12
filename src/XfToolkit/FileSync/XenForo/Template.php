<?php namespace XfToolkit\FileSync\XenForo;

use XfToolkit\Tools\XenForo\Model;
use Illuminate\Filesystem\Filesystem; // TODO: use a better filesystem component
use XenForo_Application;

class Template extends Model {

	protected $fileSystem;

	public function __construct(Filesystem $fileSystem)
	{
		$this->fileSystem = $fileSystem;
	}

	public function updateSchema($table)
	{
		$row = $this->getDb()->fetchRow('SELECT * FROM ' . $table);
		if (isset($row['last_modified']))
		{
			return false;
		}

		$this->getDb()->query('ALTER TABLE ' . $table . ' ADD COLUMN last_modified INT NOT NULL DEFAULT 0');
		return true;
	}

	public function getTemplates($styleId)
	{
		if ($styleId == -1)
		{
			return $this->getDb()->fetchAll('
				SELECT *, -1 AS style_id
				FROM xf_admin_template
			');
		}

		return $this->getDb()->fetchAll('
			SELECT *
			FROM xf_template
			WHERE style_id = ?
		', $styleId);
	}

	public function getRootTemplatePath()
	{
		return XenForo_Application::getInstance()->getRootDir() . '/templates/';
	}

	public function displayPath($path)
	{
		// Just remove the root path
		$root = XenForo_Application::getInstance()->getRootDir();
		if (strpos($path, $root) === 0)
		{
			$path = substr($path, strlen($root));
		}

		return $path;
	}

	public function getStylePath($styleId)
	{
		$path = $this->getRootTemplatePath();

		switch ($styleId)
		{
			case -1: $path .= 'admin'; break;
			case 0: $path .= 'master'; break;
			default: $path .= 'style_' . $styleId; break;
		}

		return $path;
	}

	public function updateModified(array $template, $time = null)
	{
		$table = $template['style_id'] == -1 ? 'xf_admin_template' : 'xf_template';

		$this->getDb()->query('
			UPDATE ' . $table . ' 
			SET last_modified = ? 
			WHERE template_id = ?
		', array($time === null ? time() : $time, $template['template_id']));
	}

	public function getTemplatePath(array $template)
	{	
		$path = $this->getStylePath($template['style_id']);

		$path .= '/' . $template['addon_id'] . '/' . $template['title'];

		if (strpos($template['title'], '.css') === false)
		{
			$path .= '.html';
		}

		return $path;
	}

	public function templateUpdated($filePath, $template)
	{
		return $this->fileSystem->lastModified($filePath) > $template['last_modified'];
	}

	public function update(array $template)
	{
		$dw = $this->getDw($template['style_id'] == -1 ? 'AdminTemplate' : 'Template');
		$dw->setExistingData($template);
		$dw->set('template', $this->fileSystem->get($this->getTemplatePath($template)));
		$dw->save();

		$this->updateModified($template);
	}

	public function backup(array $template)
	{
		$path = $this->getTemplatePath($template);

		$backupPath = dirname($path) . '/.backup/' . pathinfo($path, PATHINFO_FILENAME) . '.' . $template['template_id'] . '.' . pathinfo($path, PATHINFO_EXTENSION);
		if ( ! $this->fileSystem->exists(dirname($backupPath)))
		{
			$this->fileSystem->makeDirectory(dirname($backupPath));
		}

		$this->fileSystem->put($backupPath, $template['template']);

		return $this->displayPath($backupPath);
	}

	public function delete(array $template)
	{
		$dw = $this->getDw($template['style_id'] == -1 ? 'AdminTemplate' : 'Template');
		$dw->setExistingData($template);
		$dw->delete();
	}

	public function create(array $template)
	{
		if ($template['style_id'] == -1)
		{
			$dw = $this->getDw('AdminTemplate');
			unset($template['style_id']);
		}
		else
		{
			$dw = $this->getDw('Template');
		}

		$dw->bulkSet($template);
		$dw->save();

		$template = $dw->getMergedData();
		if ( ! isset($template['style_id']))
		{
			$template['style_id'] = -1;
		}

		$this->updateModified($template);
	}

	public function getTemplateInfoFromFile($path)
	{
		$folders = array_reverse(explode('/', $path));

		switch ($folders[2])
		{
			case 'admin': $styleId = -1; break;
			case 'master': $styleId = 0; break;
			default: list ($meh, $styleId) = explode('_', $folders[2]);
		}

		return array(
			'title' => str_replace('.html', '', $folders[0]),
			'addon_id' => $folders[1],
			'style_id' => $styleId,
			'template' => $this->fileSystem->get($path),
		);
	}
}