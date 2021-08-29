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
 * Controller for customer.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerCustomer extends StripelaControllerBase
{
	/**
	 * Get customers.
	 *
	 * @since  1.0.0
	 */
	public function getCustomers()
	{
		$data = [];

		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');
		$limit = $config->get('limit', 20);

		$input = $this->input;
		$startingAfter = $input->get('starting_after');
		$endingBefore = $input->get('ending_before');
		$email = $input->getEmail('email');

		$stripe = new \Stripe\StripeClient($secretKey);

		$params = ['limit' => $limit];

		if ($startingAfter)
			$params['starting_after'] = $startingAfter;

		if ($endingBefore)
			$params['ending_before'] = $endingBefore;

		if ($email)
			$params['email'] = $email;

		try {
			$response = $stripe->customers->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$customers = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			foreach ($response->data as $customer)
			{
				$customers[] = [
					'id'			=> $customer->id,
					'name'			=> $customer->name,
					'created'		=> HTMLHelper::_('stripela.date', $customer->created),
					'description'	=> $customer->description,
					'email'			=> $customer->email,
				];
			}

			$first = $customers[0];
			$last = $customers[count($customers) - 1];

			if (!$startingAfter && !$endingBefore && $response->has_more)
			{
				$newStartingAfter = $last['id'];
			}
			else
			{
				if ($startingAfter)
				{
					if ($response->has_more)
						$newStartingAfter = $last['id'];
	
					$newEndingBefore = $first['id'];
				}
	
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

		$data['items'] = $customers;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get customer detail.
	 *
	 * @since  1.0.0
	 */
	public function getCustomer()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$customerId = $input->get('id');

		if (empty($customerId))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_CUSTOMER_ID'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$r = $stripe->customers->retrieve($customerId);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$customer = [
			'id'			=> $r->id,
			'name'			=> $r->name,
			'email'			=> $r->email,
			'currency'		=> strtoupper($r->currency),
			'created'		=> HTMLHelper::_('stripela.date', $r->created),
			'description'	=> $r->description,
			'phone'			=> $r->phone,
			'address'		=> $r->address,
		];

		echo new JsonResponse($customer);

		return true;
	}
}