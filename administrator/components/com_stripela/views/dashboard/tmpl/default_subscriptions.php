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
	var Subscriptions = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				subscriptions: [],
				starting_after: '',
				ending_before: '',
				date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
				filter_from_menu: false,
				filter_to_menu: false,
				filter_customer: '',
				filter_status: 'active',
				filter_from: '',
				filter_to: '',
				dialog: false,
				loadingDetail: true,
				subscriptionDetailError: '',
				subscription: null,
				statuses: [
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_ALL'); ?>', value: 'all' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_ACTIVE'); ?>', value: 'active' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_PAST_DUE'); ?>', value: 'past_due' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_UNPAID'); ?>', value: 'unpaid' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_CANCELED'); ?>', value: 'canceled' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_INCOMPLETE'); ?>', value: 'incomplete' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_INCOMPLETE_EXPIRED'); ?>', value: 'incomplete_expired' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_TRAILING'); ?>', value: 'trialing' },
					{ text: '<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_ENEDED'); ?>', value: 'ended' },
				]
			}
		},
		template: '#subscriptions',
		watch: {
			dialog: function(val) {
				if (!val)
					this.subscription = null
			}
		},
		computed: {
			computedFromDateFormatted() {
				return this.formatDate(this.filter_from)
			},
			computedToDateFormatted() {
				return this.formatDate(this.filter_to)
			},
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
				this.getSubscriptions()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_customer = ''
				this.filter_status = ''
				this.filter_from = ''
				this.filter_to = ''
				this.getSubscriptions()
			},
			getSubscriptions: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=subscription.getSubscriptions&' + token + '=1'

				// Go to previous page.
				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				if (_this.filter_customer)
					url += '&customer=' + _this.filter_customer

				if (_this.filter_status)
					url += '&status=' + _this.filter_status

				if (_this.filter_from)
					url += '&from=' + _this.filter_from

				if (_this.filter_to)
					url += '&to=' + _this.filter_to

				_this.loadingList = true

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						_this.loadingList = false

						if (!r.success)
							return

						_this.subscriptions = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getSubscriptionDetail: function(id) {
				let url = componentRoute + '&task=subscription.getSubscription&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.subscriptionDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.subscriptionDetailError = r.message

						if (r.success && r.data)
							_this.subscription = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getSubscriptions()
		}
	})
</script>
<script type="text/x-template" id="subscriptions">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_SUBSCRIPTIONS') ; ?></h2>

		<v-divider></v-divider>

		<div v-show="loadingList" class="text-center">
			<v-progress-circular indeterminate></v-progress-circular>
		</div>

		<div v-show="!loadingList">
			<v-dialog
				v-model="dialog"
				width="800px"
				style='z-index: 2001;'
			>
				<v-card>
					<v-card-title>
					<span class="text-h5">
						<span v-if="subscription && subscription.name">{{ subscription.name }}</span>
						<span v-else-if="subscription && subscription.id">{{ subscription.id }}</span>
					</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="subscriptionDetailError"
							type="error"
							dense
							outlined
						>
							{{ subscriptionDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<div v-if="subscription !== null">
							<v-simple-table>
								<template v-slot:default>
									<tbody>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
											<td>{{ subscription.id }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
											<td>{{ subscription.customer }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_STATUS'); ?></th>
											<td>{{ subscription.status }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_BILLING'); ?></th>
											<td>{{ subscription.billing }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
											<td>{{ subscription.created }}</td>
										</tr>
									</tbody>
								</template>
							</v-simple-table>

							<span class="text-h6"><?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_ITEMS'); ?></span>

							<v-simple-table>
								<template v-slot:default>
									<thead>
										<tr>
											<th class="text-left"><?php echo Text::_('COM_STRIPELA_PRODUCT'); ?></th>
											<th class="text-left"><?php echo Text::_('COM_STRIPELA_PLAN'); ?></th>
											<th class="text-left"><?php echo Text::_('COM_STRIPELA_PRICING'); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(item, index) in subscription.items" :key="index">
											<td>{{ item.product }}</td>
											<td>{{ item.plan }}</td>
											<td>{{ item.pricing }}</td>
										</tr>
									</tbody>
								</template>
							</v-simple-table>
						</div>
					</v-card-text>
					<v-card-actions>
						<v-spacer></v-spacer>
						<v-btn
							@click="dialog = false"
						>
							<?php echo Text::_('COM_STRIPELA_CLOSE'); ?>
						</v-btn>
					</v-card-actions>
				</v-card>
			</v-dialog>

			<v-row class="mb-2">
				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="2"
				>
					<v-text-field
						v-model="filter_customer"
						label="<?php echo Text::_('COM_STRIPELA_CUSTOMER_ID'); ?>"
						prepend-icon="fas fa-user fa-fw"
					></v-text-field>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="2"
				>
					<v-select
						v-model="filter_status"
						label="<?php echo Text::_('COM_STRIPELA_STATUS'); ?>"
						:items="statuses"
						prepend-icon="fas fa-check fa-fw"
					>
					</v-select>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="2"
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
								label="<?php echo Text::_('COM_STRIPELA_CREATED_FROM'); ?>"
								prepend-icon="fas fa-calendar fa-fw"
								readonly
								v-bind="attrs"
								v-on="on"
							></v-text-field>
						</template>
						<v-date-picker
							v-model="filter_from"
							@input="filter_from_menu = false"
							next-icon="fa fa-chevron-right"
							prev-icon="fa fa-chevron-left"
						></v-date-picker>
					</v-menu>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="2"
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
								label="<?php echo Text::_('COM_STRIPELA_CREATED_TO'); ?>"
								prepend-icon="fas fa-calendar fa-fw"
								readonly
								v-bind="attrs"
								v-on="on"
							></v-text-field>
						</template>
						<v-date-picker
							v-model="filter_to"
							@input="filter_to_menu = false"
							next-icon="fa fa-chevron-right"
							prev-icon="fa fa-chevron-left"
						></v-date-picker>
					</v-menu>
				</v-col>

				<v-col
					cols="12"
					sm="6"
					md="4"
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_STATUS'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_BILLING'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_PRODUCT_N_PRICING_PLAN'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in subscriptions" :key="item.id">
							<td>{{ item.customer }}</td>
							<td>{{ item.status }}</td>
							<td>{{ item.billing }}</td>
							<td>
								<div v-for="(item, index) in item.items" :key="index">
									{{ item.product }} ({{ item.plan }})<br>
								</div>
							</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn
									x-small
									@click="getSubscriptionDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-if="ending_before" v-on:click="getSubscriptions(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-if="starting_after" v-on:click="getSubscriptions(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>