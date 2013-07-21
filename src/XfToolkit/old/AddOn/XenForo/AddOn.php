<?php namespace XfToolkit\AddOn\XenForo;

use SimpleXMLElement;
use XfToolkit\Tools\XenForo\Model;

class AddOn extends Model {

	public function getXml($fileName)
	{
		if ( ! file_exists($fileName) OR ! is_readable($fileName))
		{
			throw new \XenForo_Exception(new \XenForo_Phrase('please_enter_valid_file_name_requested_file_not_read'), true);
		}

		try
		{
			$document = new SimpleXMLElement($fileName, 0, true);
		}
		catch (Exception $e)
		{
			throw new \XenForo_Exception(
				new \XenForo_Phrase('provided_file_was_not_valid_xml_file'), true
			);
		}

		if ($document->getName() != 'addon')
		{
			throw new \XenForo_Exception(new \XenForo_Phrase('provided_file_is_not_an_add_on_xml_file'), true);
		}

		return $document;
	}

	public function getById($id)
	{
		return $this->getModel('AddOn')->getAddOnById($id);
	}

	public function getAll()
	{
		return $this->getModel('AddOn')->getAllAddOns();
	}

	public function install(SimpleXMLElement $xml, $upgradeIfExists = false)
	{
		return $this->getModel('AddOn')->installAddOnXml($xml, $upgradeIfExists);
	}

	public function installScript(SimpleXMLElement $xml)
	{
		if ($xml->getName() != 'addon')
		{
			throw new \XenForo_Exception(new \XenForo_Phrase('provided_file_is_not_an_add_on_xml_file'), true);
		}

		$addOnData = array(
			'addon_id' => (string)$xml['addon_id'],
			'title' => (string)$xml['title'],
			'version_string' => (string)$xml['version_string'],
			'version_id' => (int)$xml['version_id'],
			'install_callback_class' => (string)$xml['install_callback_class'],
			'install_callback_method' => (string)$xml['install_callback_method'],
			'uninstall_callback_class' => (string)$xml['uninstall_callback_class'],
			'uninstall_callback_method' => (string)$xml['uninstall_callback_method'],
			'url' => (string)$xml['url'],
		);

		$existingAddOn = $this->getModel('AddOn')->verifyAddOnIsInstallable($addOnData, true);

		$db = $this->getDb();
		\XenForo_Db::beginTransaction($db);

		if ($addOnData['install_callback_class'] && $addOnData['install_callback_method'])
		{
			call_user_func(
				array($addOnData['install_callback_class'], $addOnData['install_callback_method']),
				$existingAddOn,
				$addOnData
			);
		}

		$addOnDw = \XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
		if ($existingAddOn)
		{
			$addOnDw->setExistingData($existingAddOn, true);
		}
		$addOnDw->bulkSet($addOnData);
		$addOnDw->save();

		\XenForo_Db::commit($db);
	}
}
