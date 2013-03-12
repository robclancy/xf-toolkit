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

	public function getAll()
	{
		return $this->getModel('AddOn')->getAllAddOns();
	}

	public function install(SimpleXMLElement $xml, $upgradeIfExists = false)
	{
		return $this->getModel('AddOn')->installAddOnXml($xml, $upgradeIfExists);
	}
}