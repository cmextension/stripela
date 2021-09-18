<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Stripela helper.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaHelper
{
	/**
	 * Get Vue.js components.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public static function getComponents()
	{
		$components = [];

		$components[] = [
			'route'		=> '#/payments',
			'icon'		=> 'fa-copy',
			'layout'	=> 'payments',
			'name'		=> Text::_('COM_STRIPELA_PAYMENTS'),
		];

		$components[] = [
			'route'		=> '#/customers',
			'icon'		=> 'fa-users',
			'layout'	=> 'customers',
			'name'		=> Text::_('COM_STRIPELA_CUSTOMERS'),
		];

		$components[] = [
			'route'		=> '#/products',
			'icon'		=> 'fa-archive',
			'layout'	=> 'products',
			'name'		=> Text::_('COM_STRIPELA_PRODUCTS'),
		];

		$components[] = [
			'route'		=> '#/coupons',
			'icon'		=> 'fa-tags',
			'layout'	=> 'coupons',
			'name'		=> Text::_('COM_STRIPELA_COUPONS'),
		];

		$components[] = [
			'route'		=> '#/promotion-codes',
			'icon'		=> 'fa-percentage',
			'layout'	=> 'promotion_codes',
			'name'		=> Text::_('COM_STRIPELA_PROMOTION_CODES'),
		];

		$components[] = [
			'route'		=> '#/quotes',
			'icon'		=> 'fa-file-alt',
			'layout'	=> 'quotes',
			'name'		=> Text::_('COM_STRIPELA_QUOTES'),
		];

		$components[] = [
			'route'		=> '#/invoices',
			'icon'		=> 'fa-file-signature',
			'layout'	=> 'invoices',
			'name'		=> Text::_('COM_STRIPELA_INVOICES'),
		];

		$components[] = [
			'route'		=> '#/plans',
			'icon'		=> 'fa-th-list',
			'layout'	=> 'plans',
			'name'		=> Text::_('COM_STRIPELA_PLANS'),
		];

		$components[] = [
			'route'		=> '#/subscriptions',
			'icon'		=> 'fa-address-book',
			'layout'	=> 'subscriptions',
			'name'		=> Text::_('COM_STRIPELA_SUBSCRIPTIONS'),
		];

		return $components;
	}

	/**
	 * Get Stripe's zero-decimal currencies.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public static function getZeroDecimalCurrencies()
	{
		return [
			'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga',
			'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'
		];
	}

	/**
	 * Throw error based on Joomla! version.
	 *
	 * @param   string   $errorMessage  Error message.
	 * @param   integer  $errorCode     Error code.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
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