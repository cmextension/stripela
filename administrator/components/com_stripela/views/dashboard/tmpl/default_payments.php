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
				starting_after: '',
				ending_before: '',
			}
		},
		template: '#payments',
		computed: {},
		methods: {
			getPayments: function($directionKey) {
				let _this = this
				let url = componentRoute + '&task=payment.getPayments&' + token + '=1'

				// Go to previous page.
				if ($directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if ($directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				_this.loading = true

				$.ajax({
					url: url,
					dataType: 'json',
					success: function(r) {
						_this.loading = false

						if (!r.success)
							return;

						_this.payments = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_AMOUNT'); ?></th>
							<th></th>
							<th></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_DESCRIPTION'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in payments" :key="item.id">
							<td>{{ item.amount }}</td>
							<td>{{ item.currency }}</td>
							<td>
								<span v-if="item.status === 'succeeded'" class="badge bg-success">
									{{ item.status_formatted }}
								</span>
								<span v-else-if="item.status === 'canceled'" class="badge bg-danger">
									{{ item.status_formatted }}
								</span>
								<span v-else class="badge bg-primary">
									{{ item.status_formatted }}
								</span>
							</td>
							<td>{{ item.description }}</td>
							<td>{{ item.customer }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn x-small :to="'/payments/' + item.id">
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
					<v-btn v-if="ending_before" v-on:click="getPayments(-1)">
						<v-icon>fas fa-chevron-left</v-icon>
					</v-btn>

					<v-btn v-if="starting_after" v-on:click="getPayments(1)">
						<v-icon>fas fa-chevron-right</v-icon>
					</v-btn>
				</div>
			</div>
		</div>
	</div>
</script>