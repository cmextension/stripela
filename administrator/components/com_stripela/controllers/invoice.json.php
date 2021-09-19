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
 * Controller for invoice.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerInvoice extends StripelaControllerBase
{
	/**
	 * Get invoices.
	 *
	 * @since  1.0.0
	 */
	public function getInvoices()
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
		$subscriptionId = $input->get('subscription');
		$dueDateFrom = $input->get('due_date_from');
		$dueDateTo = $input->get('due_date_to');
		$status = $input->get('status');

		$stripe = new \Stripe\StripeClient($secretKey);

		$params = [
			'limit'		=> $limit,
			'expand'	=> ['data.customer'],
		];

		$validStatuses = ['draft', 'open', 'paid', 'uncollectible', 'void'];

		if ($startingAfter)
			$params['starting_after'] = $startingAfter;

		if ($endingBefore)
			$params['ending_before'] = $endingBefore;

		if ($customerId)
			$params['customer'] = $customerId;

		if ($subscriptionId)
			$params['subscription'] = $subscriptionId;

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

		if ($dueDateFrom || $dueDateTo)
		{
			$params['due_date'] = [];

			if ($dueDateFrom)
				$params['due_date']['gte'] = strtotime($dueDateFrom);

			if ($dueDateTo)
				$params['due_date']['lte'] = strtotime($dueDateTo . ' 23:59:59');
		}

		try {
			$response = $stripe->invoices->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$invoices = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			foreach ($response->data as $invoice)
			{
				$total = HTMLHelper::_('stripela.amount',
					$invoice->total,
					$invoice->currency,
					true
				);

				$customer = '';

				if (isset($invoice->customer->name))
					$customer = $invoice->customer->name;
				elseif (isset($invoice->customer->email))
					$customer = $invoice->customer->email;

				$dueDate = $invoice->due_date ? HTMLHelper::_('stripela.date', $invoice->due_date) : null;

				$invoices[] = [
					'id'			=> $invoice->id,
					'total'			=> $total,
					'status'		=> Text::_('COM_STRIPELA_INVOICE_' . strtoupper($invoice->status)),
					'number'		=> $invoice->number,
					'customer_name'	=> $invoice->customer_name,
					'due_date'		=> $dueDate,
					'created'		=> HTMLHelper::_('stripela.date', $invoice->created),
				];
			}

			$first = $invoices[0];
			$last = $invoices[count($invoices) - 1];

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

		$data['items'] = $invoices;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get invoice detail.
	 *
	 * @since  1.0.0
	 */
	public function getInvoice()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$invoiceId = $input->get('id');

		if (empty($invoiceId))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_INVOICE_IDS'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$r = $stripe->invoices->retrieve($invoiceId, [
				'expand' => ['customer', 'subscription', 'payment_intent']
			]);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$total = HTMLHelper::_('stripela.amount',
			$r->amount_total,
			$r->currency,
			true
		);

		$dueDate = $r->due_date ? HTMLHelper::_('stripela.date', $r->due_date) : null;

		$subscriptionId = isset($r->subscription->id) ? $r->subscription->id : '';
		$paymentId = isset($r->payment_intent->id) ? $r->payment_intent->id : '';
		$url = isset($r->hosted_invoice_url) ? $r->hosted_invoice_url : '';

		$invoice = [
			'id'				=> $r->id,
			'total'				=> $total,
			'status'			=> Text::_('COM_STRIPELA_INVOICE_' . strtoupper($r->status)),
			'number'			=> $r->number,
			'customer_name'		=> $r->customer_name,
			'customer_address'	=> $r->customer_address,
			'customer_email'	=> $r->customer_email,
			'customer_phone'	=> $r->customer_phone,
			'customer_shipping'	=> $r->customer_shipping,
			'subscription_id'	=> $subscriptionId,
			'payment_id'		=> $paymentId,
			'description'		=> $r->description,
			'url'				=> $url,
			'metadata'			=> $r->metadata,
			'due_date'			=> $dueDate,
			'created'			=> HTMLHelper::_('stripela.date', $r->created),
		];

		echo new JsonResponse($invoice);

		return true;
	}
}