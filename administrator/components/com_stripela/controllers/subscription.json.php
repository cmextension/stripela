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
 * Controller for subscription.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerSubscription extends StripelaControllerBase
{
	/**
	 * Cache product name to reduce API request.
	 * 
	 * @var     array
	 * @since   1.0.0
	 */
	private $productNames = [];

	/**
	 * Get product and pricing plan.
	 *
	 * @param   object   Stripe\Subscription object.
	 * @param   object   Stripe object.
	 * 
	 * @return  array
	 * 
	 * @since   1.0.0
	 */
	private function __getSubscriptionItems($subscription, $stripe, $includePricing = false)
	{
		$subItems = [];

		if (!$subscription['items']->has_more)
		{
			$items = $subscription['items'];
		}
		else
		{
			$subscriptionItems = $stripe->subscriptionItems->all([
				'subscription' => $subscription->id,
			]);

			$items = [];

			foreach ($items->autoPagingIterator() as $item)
			{
				$items[] = $item;
			}
		}

		foreach ($items as $item)
		{
			if (isset($this->productNames[$item->plan->product]))
			{
				$productName = $this->productNames[$item->plan->product];
			}
			else
			{
				$product = $stripe->products->retrieve($item->plan->product);

				if (isset($product->name))
				{
					$this->productNames[$item->plan->product] = $product->name;
					$productName = $product->name;
				}
				else
				{
					$productName = $item->plan->product;
				}
			}

			if ($includePricing)
			{
				$price = $item->price;
				$pricing = '';

				if ($price->billing_scheme == 'per_unit')
				{
					// Package pricing.
					if ($price->transform_quantity)
					{
						if ($price->recurring)
						{
							$pricing = HTMLHelper::_('stripela.recurring_package_info',
								$price->unit_amount,
								$price->currency,
								$price->transform_quantity->divide_by,
								$price->recurring->interval_count,
								$price->recurring->interval);
						}
						else
						{
							$pricing = HTMLHelper::_('stripela.package_info',
								$price->unit_amount,
								$price->currency,
								$price->transform_quantity->divide_by,
								$price->recurring->interval_count,
								$price->recurring->interval);
						}
					}
					else
					{
						if ($price->recurring)
						{
							$pricing = HTMLHelper::_('stripela.recurring_package_info_2',
								$price->unit_amount,
								$price->currency,
								$price->recurring->interval_count,
								$price->recurring->interval);
						}
						else
						{
							$pricing = HTMLHelper::_('stripela.amount', $price->unit_amount, $price->currency, true);
						}
					}
				}
				elseif ($price->billing_scheme == 'tiered')
				{
					$tiers = $price->tiers;

					if (count($tiers) > 1)
					{
						usort($tiers, function($a, $b) {
							return $a->unit_amount > $b->unit_amount;
						});
					}

					$pricing = HTMLHelper::_('stripela.tier_info',
						$tiers[0]->unit_amount,
						$tiers[0]->flat_amount,
						$price->currency,
						$price->recurring->interval_count,
						$price->recurring->interval);
				}
			}

			$plan = !empty($item->plan->nickname) ? $item->plan->nickname : $item->plan->id;
			$subItem = [
				'product'	=> $productName,
				'plan'		=> $plan,
			];

			if ($includePricing)
			{
				$subItem['pricing'] = $pricing;
			}

			$subItems[] = $subItem;
		}

		return $subItems;
	}

	/**
	 * Get subscriptions.
	 *
	 * @since  1.0.0
	 */
	public function getSubscriptions()
	{
		$data = [];

		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');
		$limit = $config->get('limit', 20);

		$input = $this->input;
		$startingAfter = $input->get('starting_after');
		$endingBefore = $input->get('ending_before');
		$from = $input->get('from');
		$to = $input->get('to');
		$customerId = $input->get('customer');
		$status = $input->get('status');

		$stripe = new \Stripe\StripeClient($secretKey);

		$params = [
			'limit'		=> $limit,
			'expand'	=> ['data.customer'],
		];

		$validStatuses = ['active', 'past_due', 'unpaid', 'canceled',
			'incomplete', 'incomplete_expired', 'trialing', 'all', 'ended'];

		if ($startingAfter)
			$params['starting_after'] = $startingAfter;

		if ($endingBefore)
			$params['ending_before'] = $endingBefore;

		if ($customerId)
			$params['customer'] = $customerId;

		if ($status && in_array($status, $validStatuses))
			$params['status'] = $status;

		if ($from || $to)
		{
			$params['created'] = [];

			if ($from)
				$params['created']['gte'] = strtotime($from);

			if ($to)
				$params['created']['lte'] = strtotime($to . ' 23:59:59');
		}

		try {
			$response = $stripe->subscriptions->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$subscriptions = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			foreach ($response->data as $subscription)
			{
				$customer = !empty($subscription->customer['name']) ? $subscription->customer['name'] : $subscription->customer['email'];
				$billing = Text::_('COM_STRIPELA_SUBSCRIPTION_BILLING_' . strtoupper($subscription->billing));
				$subItems = $this->__getSubscriptionItems($subscription, $stripe);

				$subscriptions[] = [
					'id'		=> $subscription->id,
					'customer'	=> $customer,
					'status'	=> Text::_('COM_STRIPELA_SUBSCRIPTION_' . strtoupper($subscription->status)),
					'billing'	=> $billing,
					'items'		=> $subItems,
					'created'	=> HTMLHelper::_('stripela.date', $subscription->created),
				];
			}

			$first = $subscriptions[0];
			$last = $subscriptions[count($subscriptions) - 1];

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

		$data['items'] = $subscriptions;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get subscription detail.
	 *
	 * @since  1.0.0
	 */
	public function getSubscription()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$subscriptionId = $input->get('id');

		if (empty($subscriptionId))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_SUBSCRIPTION_IDS'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$r = $stripe->subscriptions->retrieve($subscriptionId, [
				'expand' => ['customer']
			]);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$customer = !empty($r->customer['name']) ? $r->customer['name'] : $r->customer['email'];
		$billing = Text::_('COM_STRIPELA_SUBSCRIPTION_BILLING_' . strtoupper($r->billing));
		$subItems = $this->__getSubscriptionItems($r, $stripe, true);

		$subscription = [
			'id'		=> $r->id,
			'customer'	=> $customer,
			'status'	=> Text::_('COM_STRIPELA_SUBSCRIPTION_' . strtoupper($r->status)),
			'billing'	=> $billing,
			'items'		=> $subItems,
			'created'	=> HTMLHelper::_('stripela.date', $r->created),
		];

		echo new JsonResponse($subscription);

		return true;
	}
}