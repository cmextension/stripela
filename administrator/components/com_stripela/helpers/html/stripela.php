<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * HTML utility class for Stripela component.
 *
 * @since  1.0.0
 */
abstract class JHtmlStripela
{
	/**
	 * Format date and time.
	 *
	 * @param   string  $dateTime  Date time string to format.
	 *
	 * @return  string  Formatted date time.
	 *
	 * @since   1.0.0
	 */
	public static function date($dateTime)
	{
		$params		= ComponentHelper::getParams('com_stripela');
		$dateFormat	= Text::_($params->get('date_format', 'COM_STRIPELA_DATE_FORMAT_1'));
		$timeFormat	= Text::_($params->get('time_format', 'COM_STRIPELA_TIME_FORMAT_1'));

		return HTMLHelper::_('date', $dateTime, $dateFormat . ' ' . $timeFormat);
	}

	/**
	 * Format amount. Need improvement.
	 *
	 * @param   float|string  $amount    Amount.
	 * @param   string        $currency  Currency code.
	 *
	 * @return  float  Formatted amount.
	 *
	 * @since   1.0.0
	 */
	public static function amount($amount, $currency)
	{
		$zeroDecimalCurrencies = StripelaHelper::getZeroDecimalCurrencies();
		$amount = in_array($currency, $zeroDecimalCurrencies) ? $amount : $amount / 100;
		$amount = number_format($amount, 2, '.', ',');

		return $amount;
	}
}