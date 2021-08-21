<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<script type="text/javascript">
	var Payments = Vue.extend({
		data: function() {
			return {
				loading: true,
				payments: [],
			}
		},
		template: '#payments',
		computed: {},
		methods: {
			getPayments: function() {
				let _this = this

				$.ajax({
					url: componentRoute + '&task=payment.getPayments&' + token + '=1',
					dataType: 'json',
					success: function(r) {
						_this.loading = false

						if (!r.success)
							return;

						_this.payments = r.data.items
					}
				})
			}
		},
		mounted: function() {
			this.getPayments()
		}
	})
</script>
<script type="text/x-template" id="payments">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_PAYMENTS') ; ?></h2>

		<v-divider></v-divider>

		<div v-show="loading" class="text-center">
			<v-progress-circular indeterminate></v-progress-circular>
		</div>

		<div v-show="!loading">
			<v-simple-table>
				<template v-slot:default>
					<thead>
						<tr>
							<th class="text-left">Amount</th>
							<th></th>
							<th></th>
							<th class="text-left">Description</th>
							<th class="text-left">Customer</th>
							<th class="text-left">Created</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in payments" :key="item.id">
							<td>{{ item.amount }}</td>
							<td>{{ item.currency }}</td>
							<td>{{ item.status }}</td>
							<td>{{ item.description }}</td>
							<td>{{ item.customer }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-menu offset-x bottom left>
									<template v-slot:activator="{ on, attrs }">
										<v-btn
											x-small
											v-bind="attrs"
											v-on="on"
										>
											<v-icon x-small>fas fa-ellipsis-h</v-icon>
										</v-btn>
									</template>
									<v-list>
										<v-list-item-group>
											<v-list-item>
												<v-list-item-title>Refund Payment</v-list-item-title>
											</v-list-item>
											<v-list-item>
												<v-list-item-title>Send Receipt</v-list-item-title>
											</v-list-item>
											<v-list-item>
												<v-list-item-title>View Customer</v-list-item-title>
											</v-list-item>
											<v-list-item>
												<v-list-item-title>View Payment Details</v-list-item-title>
											</v-list-item>
										</v-list-item-group>
									</v-list>
								</v-menu>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>
		</div>
	</div>
</script>