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
 * Controller for promotion code.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerPromotionCode extends StripelaControllerBase
{
	/**
	 * Get promotion codes.
	 *
	 * @since  1.0.0
	 */
	public function getPromotionCodes()
	{
		$data = [];

		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');
		$limit = $config->get('limit', 20);

		$input = $this->input;
		$startingAfter = $input->get('starting_after');
		$endingBefore = $input->get('ending_before');
		$active = $input->get('active');
		$code = $input->get('code');
		$customerId = $input->get('customer');
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

		if ($active == 1 || $active == 0)
			$params['active'] = (bool) $active;

		if ($code)
			$params['code'] = $code;

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
			$response = $stripe->promotionCodes->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$codes = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			$zeroDecimalCurrencies = StripelaHelper::getZeroDecimalCurrencies();

			foreach ($response->data as $code)
			{
				$customer = '';

				if (!empty($code->customer))
					$customer = !empty($code->customer['name']) ? $code->customer['name'] : $code->customer['email'];

				$expiresAt = $code->expires_at ? HTMLHelper::_('stripela.date', $code->expires_at) : null;

				$codes[] = [
					'id'		=> $code->id,
					'code'		=> $code->code,
					'active'	=> $code->active,
					'coupon'	=> $code->coupon->name,
					'customer'	=> $customer,
					'expires_at'=> $expiresAt,
					'created'	=> HTMLHelper::_('stripela.date', $code->created),
				];
			}

			$first = $codes[0];
			$last = $codes[count($codes) - 1];

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

		$data['items'] = $codes;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get promotion code detail.
	 *
	 * @since  1.0.0
	 */
	public function getPromotionCode()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$codeId = $input->get('id');

		if (empty($codeId))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_PROMOTION_CODE_IDS'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$r = $stripe->promotionCodes->retrieve($codeId, [
				'expand' => ['customer']
			]);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$coupon = $r->coupon;
		$customer = null;
		$canceledAt = null;
		$minimumAmount = null;
		
		if (isset($r->customer->name))
			$customer = $r->customer->name;
		elseif (isset($r->customer->email))
			$customer = $r->customer->email;

		if ($r->expires_at)
			$expiresAt = HTMLHelper::_('stripela.date', $r->expires_at);

		$terms = HTMLHelper::_('stripela.terms',
			$coupon->amount_off,
			$coupon->percent_off,
			$coupon->currency,
			$coupon->duration,
			$coupon->duration_in_months,
		);

		$firstTimeTransaction = isset($r->restrictions->first_time_transaction) ?
			$r->restrictions->first_time_transaction : null;

		if (isset($r->restrictions->minimum_amount))
		{
			$minimumAmount = HTMLHelper::_('stripela.amount',
				$r->restrictions->minimum_amount,
				$r->restrictions->minimum_amount_currency,
				true
			);
		}

		$code = [
			'id'					=> $r->id,
			'active'				=> $r->active,
			'code'					=> $r->code,
			'coupon'				=> $coupon->name,
			'coupon_terms'			=> $terms,
			'max_redemptions'		=> $r->max_redemptions,
			'metadata'				=> $r->metadata,
			'first_time_transaction'=> $firstTimeTransaction,
			'minimum_amount'		=> $minimumAmount,
			'times_redeemed'		=> $r->times_redeemed,
			'customer'				=> $customer,
			'expires_at'			=> $expiresAt,
			'created'				=> HTMLHelper::_('stripela.date', $r->created),
		];

		echo new JsonResponse($code);

		return true;
	}
}