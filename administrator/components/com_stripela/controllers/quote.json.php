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
 * Controller for quote.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerQuote extends StripelaControllerBase
{
	/**
	 * Get quotes.
	 *
	 * @since  1.0.0
	 */
	public function getQuotes()
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

		$validStatuses = ['draft', 'open', 'accepted', 'canceled'];

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
			$response = $stripe->quotes->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$quotes = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			foreach ($response->data as $quote)
			{
				$amountTotal = HTMLHelper::_('stripela.amount',
					$quote->amount_total,
					$quote->currency,
					true
				);

				$customer = '';

				if (isset($quote->customer->name))
					$customer = $quote->customer->name;
				elseif (isset($quote->customer->email))
					$customer = $quote->customer->email;

				$expiresAt = $quote->expires_at ? HTMLHelper::_('stripela.date', $quote->expires_at) : null;

				$quotes[] = [
					'id'			=> $quote->id,
					'amount_total'	=> $amountTotal,
					'status'		=> Text::_('COM_STRIPELA_QUOTE_' . strtoupper($quote->status)),
					'number'		=> $quote->number,
					'customer'		=> $customer,
					'expires_at'	=> $expiresAt,
					'created'		=> HTMLHelper::_('stripela.date', $quote->created),
				];
			}

			$first = $quotes[0];
			$last = $quotes[count($quotes) - 1];

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

		$data['items'] = $quotes;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get quote detail.
	 *
	 * @since  1.0.0
	 */
	public function getQuote()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$quoteId = $input->get('id');

		if (empty($quoteId))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_QUOTE_IDS'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$r = $stripe->quotes->retrieve($quoteId, [
				'expand' => ['customer']
			]);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$amountTotal = HTMLHelper::_('stripela.amount',
			$r->amount_total,
			$r->currency,
			true
		);

		$customer = '';

		if (isset($r->customer->name))
			$customer = $r->customer->name;
		elseif (isset($r->customer->email))
			$customer = $r->customer->email;

		$expiresAt = $r->expires_at ? HTMLHelper::_('stripela.date', $r->expires_at) : null;

		$quote = [
			'id'			=> $r->id,
			'amount_total'	=> $amountTotal,
			'status'		=> Text::_('COM_STRIPELA_QUOTE_' . strtoupper($r->status)),
			'number'		=> $r->number,
			'customer'		=> $customer,
			'metadata'		=> $r->metadata,
			'expires_at'	=> $expiresAt,
			'created'		=> HTMLHelper::_('stripela.date', $r->created),
		];

		echo new JsonResponse($quote);

		return true;
	}
}