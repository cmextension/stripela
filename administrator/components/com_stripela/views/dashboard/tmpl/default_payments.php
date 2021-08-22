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
				date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
				filter_from_menu: false,
				filter_to_menu: false,
				filter_customer: '',
				filter_from: '',
				filter_to: '',
			}
		},
		template: '#payments',
		computed: {
			computedFromDateFormatted() {
				return this.formatDate(this.filter_from)
			},
			computedToDateFormatted() {
				return this.formatDate(this.filter_to)
			}
		},
		methods: {
			formatDate: function(date) {
				if (!date) return null

				const [year, month, day] = date.split('-')

				if (dateFormat == 'Y/m/d')		return `${year}/${month}/${day}`
				else if (dateFormat == 'Y-m-d')	return `${year}-${month}-${day}`
				else if (dateFormat == 'd/m/Y')	return `${day}/${month}/${year}`
				else if (dateFormat == 'd-m-Y')	return `${day}-${month}-${year}`
				else if (dateFormat == 'm/d/Y')	return `${month}/${day}/${year}`
				else if (dateFormat == 'm-d-Y')	return `${month}-${day}-${year}`
				else if (dateFormat == 'd.m.Y')	return `${day}.${month}.${year}`
				else if (dateFormat == 'Y.m.d')	return `${year}.${month}.${day}`
			},
			filter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.getPayments()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_customer = ''
				this.filter_from = ''
				this.filter_to = ''
				this.getPayments()
			},
			getPayments: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=payment.getPayments&' + token + '=1'

				// Go to previous page.
				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				if (_this.filter_customer)
					url += '&customer_id=' + _this.filter_customer

				if (_this.filter_from)
					url += '&from=' + _this.filter_from

				if (_this.filter_to)
					url += '&to=' + _this.filter_to

				_this.loading = true

				$.ajax({
					url: url,
					dataType: 'json',
					success: function(r) {
						_this.loading = false

						if (!r.success)
							return

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
			<v-row class="mb-2">
				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="3"
				>
					<v-text-field
						v-model="filter_customer"
						label="Customer ID"
						prepend-icon="fas fa-user fa-fw"
					></v-text-field>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="3"
				>
					<v-menu
						v-model="filter_from_menu"
						:close-on-content-click="false"
						:nudge-right="40"
						transition="scale-transition"
						offset-y
						min-width="auto"
					>
						<template v-slot:activator="{ on, attrs }">
							<v-text-field
								v-model="computedFromDateFormatted"
								label="<?php echo Text::_('COM_STRIPELA_FROM'); ?>"
								prepend-icon="fas fa-calendar fa-fw"
								readonly
								v-bind="attrs"
								v-on="on"
							></v-text-field>
						</template>
						<v-date-picker
							v-model="filter_from"
							@input="filter_from_menu = false"
						></v-date-picker>
					</v-menu>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="3"
				>
					<v-menu
						v-model="filter_to_menu"
						:close-on-content-click="false"
						:nudge-right="40"
						transition="scale-transition"
						offset-y
						min-width="auto"
					>
						<template v-slot:activator="{ on, attrs }">
							<v-text-field
								v-model="computedToDateFormatted"
								label="<?php echo Text::_('COM_STRIPELA_TO'); ?>"
								prepend-icon="fas fa-calendar fa-fw"
								readonly
								v-bind="attrs"
								v-on="on"
							></v-text-field>
						</template>
						<v-date-picker
							v-model="filter_to"
							@input="filter_to_menu = false"
						></v-date-picker>
					</v-menu>
				</v-col>

				<v-col
					cols="12"
					sm="6"
					md="3"
					class="d-flex align-content-center flex-wrap"
				>
					<v-btn
						v-on:click="filter()"
						color="primary"
					>
						<?php echo Text::_('COM_STRIPELA_FILTER'); ?>
					</v-btn>
					<v-btn
						v-on:click="clearFilter()"
					>
						<?php echo Text::_('COM_STRIPELA_CLEAR'); ?>
					</v-btn>
				</v-col>
			</v-row>

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
</script>