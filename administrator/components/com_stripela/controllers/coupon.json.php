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
 * Controller for coupon.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerCoupon extends StripelaControllerBase
{
	/**
	 * Get coupons.
	 *
	 * @since  1.0.0
	 */
	public function getCoupons()
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

		$stripe = new \Stripe\StripeClient($secretKey);

		$params = [
			'limit'		=> $limit,
			'expand'	=> ['data.applies_to'],
		];

		if ($startingAfter)
			$params['starting_after'] = $startingAfter;

		if ($endingBefore)
			$params['ending_before'] = $endingBefore;

		if ($from || $to)
		{
			$params['created'] = [];

			if ($from)
				$params['created']['gte'] = strtotime($from);

			if ($to)
				$params['created']['lte'] = strtotime($to . ' 23:59:59');
		}

		try {
			$response = $stripe->coupons->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$coupons = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			foreach ($response->data as $coupon)
			{
				try {
					$promotionCodes = $stripe->promotionCodes->all([
						'coupon' => $coupon->id,
						'expand' => ['data.customer']
					]);
				} catch (Exception $e) {
					echo new JsonResponse(null, $e->getMessage(), true);
		
					return false;
				}

				$codeCount = 0;

				foreach ($promotionCodes->autoPagingIterator() as $pc)
				{
					$codeCount++;
				}

				$terms = HTMLHelper::_('stripela.terms',
					$coupon->amount_off,
					$coupon->percent_off,
					$coupon->currency,
					$coupon->duration,
					$coupon->duration_in_months,
				);

				$redeemBy = $coupon->redeem_by ? HTMLHelper::_('stripela.date', $coupon->redeem_by) : null;

				$coupons[] = [
					'id'				=> $coupon->id,
					'name'				=> $coupon->name,
					'terms'				=> $terms,
					'max_redemptions'	=> $coupon->max_redemptions,
					'valid'				=> $coupon->valid,
					'redeem_by'			=> $redeemBy,
					'promotion_codes'	=> $codeCount,
					'created'			=> HTMLHelper::_('stripela.date', $coupon->created),
				];
			}

			$first = $coupons[0];
			$last = $coupons[count($coupons) - 1];

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

		$data['items'] = $coupons;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get coupon detail.
	 *
	 * @since  1.0.0
	 */
	public function getCoupon()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$couponId = $input->get('id');

		if (empty($couponId))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_COUPON_IDS'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$r = $stripe->coupons->retrieve($couponId);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		try {
			$promotionCodes = $stripe->promotionCodes->all([
				'coupon' => $r->id,
				'expand' => ['data.customer']
			]);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$codes = [];

		foreach ($promotionCodes->autoPagingIterator() as $pc)
		{
			$expiresAt = $pc->expires_at ? HTMLHelper::_('stripela.date', $pc->expires_at) : null;
			$firstTimeTransaction = isset($pc->restrictions->first_time_transaction) ?
				$pc->restrictions->first_time_transaction : null;

			$customerName = null;
			$customerEmail = null;
			$minimumAmount = null;

			if (isset($pc->restrictions->minimum_amount))
			{
				$minimumAmount = HTMLHelper::_('stripela.amount',
					$pc->restrictions->minimum_amount,
					$pc->restrictions->minimum_amount_currency,
					true
				);
			}

			if (isset($pc->customer->name))
			{
				$customerName = $pc->customer->name;
			}

			if (isset($pc->customer->email))
			{
				$customerEmail = $pc->customer->email;
			}

			$codes[] = [
				'id'						=> $pc->id,
				'code'						=> $pc->code,
				'active'					=> $pc->active,
				'customer_name'				=> $customerName,
				'customer_email'			=> $customerEmail,
				'expires_at'				=> $expiresAt,
				'max_redemptions'			=> $pc->max_redemptions,
				'metadata'					=> $pc->metadata,
				'first_time_transaction'	=> $firstTimeTransaction,
				'minimum_amount'			=> $minimumAmount,
				'times_redeemed'			=> $pc->times_redeemed,
			];
		}

		$terms = HTMLHelper::_('stripela.terms',
			$r->amount_off,
			$r->percent_off,
			$r->currency,
			$r->duration,
			$r->duration_in_months,
		);

		$redeemBy = $r->redeem_by ? HTMLHelper::_('stripela.date', $r->redeem_by) : null;

		$coupon = [
			'id'				=> $r->id,
			'name'				=> $r->name,
			'terms'				=> $terms,
			'max_redemptions'	=> $r->max_redemptions,
			'valid'				=> $r->valid,
			'promotion_codes'	=> $codes,
			'redeem_by'			=> $redeemBy,
			'created'			=> HTMLHelper::_('stripela.date', $r->created),
		];

		echo new JsonResponse($coupon);

		return true;
	}
}