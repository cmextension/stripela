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
 * Controller for product.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerProduct extends StripelaControllerBase
{
	/**
	 * Get products.
	 *
	 * @since  1.0.0
	 */
	public function getProducts()
	{
		$data = [];

		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');
		$limit = $config->get('limit', 20);

		$input = $this->input;
		$startingAfter = $input->get('starting_after');
		$endingBefore = $input->get('ending_before');
		$active = $input->getBool('active', true);

		$stripe = new \Stripe\StripeClient($secretKey);

		$params = ['limit' => $limit, 'active' => $active];

		if ($startingAfter)
			$params['starting_after'] = $startingAfter;

		if ($endingBefore)
			$params['ending_before'] = $endingBefore;

		try {
			$response = $stripe->products->all($params);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$products = [];
		$newStartingAfter = '';
		$newEndingBefore = '';

		if (count($response->data) > 0)
		{
			foreach ($response->data as $p)
			{
				$product = [
					'id'			=> $p->id,
					'name'			=> $p->name,
					'created'		=> HTMLHelper::_('stripela.date', $p->created),
					'updated'		=> HTMLHelper::_('stripela.date', $p->updated),
					'price_info'	=> [],
				];

				$prices = $stripe->prices->all(['product' => $p->id, 'expand' => ['data.tiers']]);

				foreach ($prices->autoPagingIterator() as $i)
				{
					if ($i->billing_scheme == 'per_unit')
					{
						// Package pricing.
						if ($i->transform_quantity)
						{
							if ($i->recurring)
							{
								$product['price_info'][] = HTMLHelper::_('stripela.recurring_package_info',
									$i->unit_amount,
									$i->currency,
									$i->transform_quantity->divide_by,
									$i->recurring->interval_count,
									$i->recurring->interval);
							}
							else
							{
								$product['price_info'][] = HTMLHelper::_('stripela.package_info',
									$i->unit_amount,
									$i->currency,
									$i->transform_quantity->divide_by);
							}
						}
						else
						{
							$product['price_info'][] = HTMLHelper::_('stripela.amount', $i->unit_amount, $i->currency, true);
						}
					}
					elseif ($i->billing_scheme == 'tiered')
					{
						$tiers = $i->tiers;

						if (count($tiers) > 1)
						{
							usort($tiers, function($a, $b) {
								return $a->unit_amount > $b->unit_amount;
							});
						}

						$product['price_info'][] = HTMLHelper::_('stripela.tier_info',
							$tiers[0]->unit_amount,
							$tiers[0]->flat_amount,
							$i->currency,
							$i->recurring->interval_count,
							$i->recurring->interval);
					}
				}

				$products[] = $product;
			}

			$first = $products[0];
			$last = $products[count($products) - 1];

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

		$data['items'] = $products;
		$data['starting_after'] = $newStartingAfter;
		$data['ending_before'] = $newEndingBefore;
		
		echo new JsonResponse($data);

		return true;
	}

	/**
	 * Get product detail.
	 *
	 * @since  1.0.0
	 */
	public function getProduct()
	{
		$config = ComponentHelper::getParams('com_stripela');
		$secretKey = $config->get('stripe_secret_key');

		$input = $this->input;
		$id = $input->get('id');

		if (empty($id))
		{
			echo new JsonResponse(null, Text::_('COM_STRIPELA_NO_PRODUCT_ID'), true);

			return false;
		}

		$stripe = new \Stripe\StripeClient($secretKey);

		try {
			$p = $stripe->products->retrieve($id, [
				'expand' => ['tax_code']
			]);
		} catch (Exception $e) {
			echo new JsonResponse(null, $e->getMessage(), true);

			return false;
		}

		$product = [
			'id'			=> $p->id,
			'name'			=> $p->name,
			'active'		=> $p->active,
			'description'	=> $p->description,
			'metadata'		=> $p->metadata,
			'images'		=> $p->images,
			'url'			=> $p->url,
			'created'		=> HTMLHelper::_('stripela.date', $p->created),
			'updated'		=> HTMLHelper::_('stripela.date', $p->updated),
		];

		echo new JsonResponse($product);

		return true;
	}
}