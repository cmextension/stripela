<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Stripela helper.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaHelper
{
	public static function throwException($errorMessage, $errorCode)
	{
		if (version_compare(JVERSION, '4.0.0-alpha1', '<'))
		{
			JError::raiseError($errorCode, $errorMessage);
		}
		else
		{
			throw new GenericDataException($errorMessage, $errorCode);
		}

		return false;
	}
}