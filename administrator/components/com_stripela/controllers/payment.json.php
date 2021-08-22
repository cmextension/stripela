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
use Joomla\CMS\HTML\HTMLHelper;

require_once __DIR__ . '/base.php';

/**
 * Controller for payment intent.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerPayment extends StripelaControllerBase
{
	/**
	 * Get payments.
	 *
	 * @since  1.0.0
	 */
	public function getPayments()
	{
		$data = [];

		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');
		$limit = $config->get('limit', 20);

		$input = $this->input;
		$startingAfter = $input->get('starting_after');
		$endingBefore = $input->get('ending_before');
		$customerId = $input->get('customer_id');
		$from = $input->get('from');
		$to = $input->get('to');

		$stripe = new \Stripe\StripeClient($secretKey);

		$params = [
			'limit'		=> $limit,
			'expand'	=> ['data.customer'],
		];

		if ($startingAfter)
			$params['starting_after'] = $startingAfter;

		if ($endingBefore)
			$params['ending_before'] = $endingBefore;

		if ($customerId)
			$params['customer'] = $customerId;

		if ($from || $to)
		{
			$params['created'] = [];

			if ($from)
				$params['created']['gte'] = strtotime($from);

			if ($to)
				$params['created']['lte'] = strtotime($to . ' 23:59:59');
		}

		try {
			$response = $stripe->paymentIntents->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$payments = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			$zeroDecimalCurrencies = StripelaHelper::getZeroDecimalCurrencies();

			foreach ($response->data as $payment)
			{
				$customer = !empty($payment->customer['name']) ? $payment->customer['name'] : $payment->customer['email'];

				$amount = in_array($payment->currency, $zeroDecimalCurrencies) ? $payment->amount : $payment->amount / 100;

				// Will improve this later.
				$amount = number_format($amount, 2, '.', ',');

				$payments[] = [
					'id'				=> $payment->id,
					'amount'			=> $amount,
					'currency'			=> strtoupper($payment->currency),
					'status'			=> $payment->status,
					'status_formatted'	=> Text::_('COM_STRIPELA_PAYMENT_STATUS_' . strtoupper($payment->status)),
					'description'		=> $payment->description,
					'customer'			=> $customer,
					'created'			=> HTMLHelper::_('stripela.date', $payment->created),
				];
			}

			$first = $payments[0];
			$last = $payments[count($payments) - 1];

			// First page.
			if (!$startingAfter && !$endingBefore && $response->has_more)
			{
				$newStartingAfter = $last['id'];
			}
			else
			{
				// Go to next page.
				if ($startingAfter)
				{
					if ($response->has_more)
						$newStartingAfter = $last['id'];
	
					$newEndingBefore = $first['id'];
				}
	
				// Go to previous page.
				if ($endingBefore)
				{
					if ($response->has_more)
						$newEndingBefore = $first['id'];
	
					$newStartingAfter = $last['id'];
				}
			}
		}
		else
		{
			if ($startingAfter)
				$newEndingBefore = $startingAfter;
		}

		$data['items'] = $payments;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}
}