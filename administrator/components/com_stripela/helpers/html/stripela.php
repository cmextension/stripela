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
	 * @param   float|string  $amount           Amount.
	 * @param   string        $currency         Currency code.
	 * @param   boolean       $includeCurrency  True to show currency code befere amount.
	 *
	 * @return  string  Formatted amount.
	 *
	 * @since   1.0.0
	 */
	public static function amount($amount, $currency, $includeCurrency = false)
	{
		$zeroDecimalCurrencies = StripelaHelper::getZeroDecimalCurrencies();
		$amount = in_array($currency, $zeroDecimalCurrencies) ? $amount : $amount / 100;
		$amount = number_format($amount, 2, '.', ',');

		if ($includeCurrency)
			$amount = Text::sprintf('COM_STRIPELA_AMOUNT_FORMAT', strtoupper($currency), $amount);

		return $amount;
	}

	/**
	 * Show tier's price info.
	 *
	 * @param   float     $unitAmount      Per unit price.
	 * @param   float     $flatAmount      Price for entire tier.
	 * @param   string    $currency        Currency code.
	 * @param   integer   $intervalCount   Number of intervals.
	 * @param   string    $interval        Frequency at which a subscription is billed.
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public static function tier_info($unitAmount, $flatAmount, $currency, $intervalCount, $interval)
	{
		$unitPrice = self::amount($unitAmount, $currency, true);
		$flatPrice = self::amount($flatAmount, $currency, true);

		if ($intervalCount == 1)
		{
			$intervalStr = Text::_('COM_STRIPELA_INTERVAL_'. $interval);
		}
		else
		{
			$intervalStr = Text::sprintf('COM_STRIPELA_INTERVAL_EVERY_'. $interval, $intervalCount);
		}

		return Text::sprintf('COM_STRIPELA_TIER_INFO', $unitPrice, $flatPrice, $intervalStr);
	}

	/**
	 * Show package pricing info.
	 *
	 * @param   float     $unitAmount   Per unit price.
	 * @param   string    $currency     Currency code.
	 * @param   integer   $group        Bucket size.
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public static function package_info($unitAmount, $currency, $group)
	{
		$unitPrice = self::amount($unitAmount, $currency, true);

		return Text::sprintf('COM_STRIPELA_PACKAGE_INFO', $unitPrice, $group);
	}

	/**
	 * Show recurring package pricing info.
	 *
	 * @param   float     $unitAmount      Per unit price.
	 * @param   string    $currency        Currency code.
	 * @param   integer   $group           Bucket size.
	 * @param   integer   $intervalCount   Number of intervals.
	 * @param   string    $interval        Frequency at which a subscription is billed.
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public static function recurring_package_info($unitAmount, $currency, $group, $intervalCount, $interval)
	{
		$unitPrice = self::amount($unitAmount, $currency, true);

		if ($intervalCount == 1)
		{
			$intervalStr = Text::_('COM_STRIPELA_INTERVAL_'. $interval);
		}
		else
		{
			$intervalStr = Text::sprintf('COM_STRIPELA_INTERVAL_EVERY_'. $interval, $intervalCount);
		}

		return Text::sprintf('COM_STRIPELA_RECURRING_PACKAGE_INFO', $unitPrice, $group, $intervalStr);
	}

	/**
	 * Show recurring package pricing info.
	 *
	 * @param   interger|null   $amountOff          Amount taken off subtotal of invoice.
	 * @param   float|null      $percentOff         Percent taken off subtotal of invoice.
	 * @param   string          $currency           Currency code.
	 * @param   string          $duration           "once", "repeating" or "forever"
	 * @param   integer         $durationInMonths   Number of months.
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public static function terms($amountOff, $percentOff, $currency, $duration, $durationInMonths)
	{
		$terms = ($amountOff === null) ? $percentOff . '%' : self::amount($amountOff, $currency, true);

		if ($duration == 'repeating')
		{
			if ($durationInMonths > 1)
				$terms = Text::sprintf('COM_STRIPELA_COUPON_TERMS_REPEATING', $terms, $durationInMonths);
			else
				$terms = Text::sprintf('COM_STRIPELA_COUPON_TERMS_REPEATING_1', $terms);
		}
		elseif ($duration == 'once')
		{
			$terms = Text::sprintf('COM_STRIPELA_COUPON_TERMS_ONCE', $terms);
		}
		elseif ($duration == 'forever')
		{
			$terms = Text::sprintf('COM_STRIPELA_COUPON_TERMS_FOREVER', $terms);
		}

		return $terms;
	}
}