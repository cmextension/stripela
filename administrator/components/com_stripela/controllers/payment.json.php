<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Controller for payment intent.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerPayment extends BaseController
{
	/**
	 * Get payments.
	 *
	 * @since  1.0.0
	 */
	public function getPayments()
	{
		$response = null;

		$params = ComponentHelper::getParams('com_stripela');
		$secretKey = $params->get('stripe_secret_key');

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$response = $stripe->paymentIntents->all();
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}
		
		echo new JsonResponse($response);

		return true;
	}
}