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
	var Coupons = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				coupons: [],
				starting_after: '',
				ending_before: '',
				date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
				filter_from_menu: false,
				filter_to_menu: false,
				filter_coupon: '',
				filter_from: '',
				filter_to: '',
				dialog: false,
				loadingDetail: true,
				couponDetailError: '',
				coupon: null,
			}
		},
		template: '#coupons',
		watch: {
			dialog: function(val) {
				if (!val)
					this.coupon = null
			}
		},
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
				this.getCoupons()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_coupon = ''
				this.filter_from = ''
				this.filter_to = ''
				this.getCoupons()
			},
			getCoupons: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=coupon.getCoupons&' + token + '=1'

				// Go to previous page.
				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				if (_this.filter_coupon)
					url += '&coupon_id=' + _this.filter_coupon

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

						_this.coupons = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getCouponDetail: function(id) {
				let url = componentRoute + '&task=coupon.getCoupon&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.couponDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.couponDetailError = r.message

						if (r.success && r.data)
							_this.coupon = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getCoupons()
		}
	})
</script>
<script type="text/x-template" id="coupons">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_COUPONS') ; ?></h2>

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
						<span v-if="coupon && coupon.name">{{ coupon.name }}</span>
						<span v-else-if="coupon && coupon.id">{{ coupon.id }}</span>
					</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="couponDetailError"
							type="error"
							dense
							outlined
						>
							{{ couponDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<v-simple-table v-if="coupon !== null">
							<template v-slot:default>
								<tbody>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
										<td>{{ coupon.id }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
										<td>{{ coupon.name }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_TERMS'); ?></th>
										<td>{{ coupon.terms }}</td>
									</tr>
									<tr v-show="coupon.max_redemptions">
										<th><?php echo Text::_('COM_STRIPELA_MAX_REDEMPTIONS'); ?></th>
										<td>{{ coupon.max_redemptions }}</td>
									</tr>
									<tr v-show="coupon.redeem_by">
										<th><?php echo Text::_('COM_STRIPELA_REDEEM_BY'); ?></th>
										<td>{{ coupon.redeem_by }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
										<td>{{ coupon.created }}</td>
									</tr>
								</tbody>
							</template>
						</v-simple-table>

						<div v-if="coupon !== null && coupon.promotion_codes.length > 0">
							<span class="text-h6">
								<?php echo Text::_('COM_STRIPELA_PROMOTION_CODES'); ?>
							</span>

							<div v-for="code in coupon.promotion_codes" :key="code.id">
								<strong>{{ code.code }}</strong>
								<v-simple-table>
									<template v-slot:default>
										<tbody>
											<tr>
												<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
												<td>{{ code.id }}</td>
											</tr>
											<tr>
												<th><?php echo Text::_('COM_STRIPELA_ACTIVE'); ?></th>
												<td>
													<v-icon v-if="code.valid">fas fa-check</v-icon>
													<v-icon v-else>fas fa-times</v-icon>
												</td>
											</tr>
											<tr v-show="code.customer_name || code.customer_email">
												<th><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
												<td>
													<div v-if="code.customer_name && code.customer_email">
														{{ code.customer_name }} ({{ code.customer_email }})
													</div>
													<div v-else-if="code.customer_name && !code.customer_email">
														{{ code.customer_name }}
													</div>
													<div v-else>
														{{ code.customer_email }}
													</div>
												</td>
											</tr>
											<tr v-show="code.expires_at">
												<th><?php echo Text::_('COM_STRIPELA_EXPIRES_AT'); ?></th>
												<td>{{ code.expires_at }}</td>
											</tr>
											<tr v-show="code.first_time_transaction">
												<th><?php echo Text::_('COM_STRIPELA_FIRST_TIME_TRANSACTION'); ?></th>
												<td>
													<v-icon v-if="code.first_time_transaction">fas fa-check</v-icon>
													<v-icon v-else>fas fa-times</v-icon>
												</td>
											</tr>
											<tr v-show="code.minimum_amount">
												<th><?php echo Text::_('COM_STRIPELA_MINIMUM_AMOUNT'); ?></th>
												<td>{{ code.minimum_amount }}</td>
											</tr>
											<tr v-show="code.max_redemptions">
												<th><?php echo Text::_('COM_STRIPELA_MAX_REDEMPTIONS'); ?></th>
												<td>{{ code.max_redemptions }}</td>
											</tr>
											<tr>
												<th><?php echo Text::_('COM_STRIPELA_REDEMPTIONS'); ?></th>
												<td>{{ code.times_redeemed }}</td>
											</tr>
										</tbody>
									</template>
								</v-simple-table>
							</div>
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
					md="4"
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
					md="4"
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_TERMS'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_MAX_REDEMPTIONS'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_REDEEM_BY'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_VALID'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_PROMOTION_CODES'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in coupons" :key="item.id">
							<td>{{ item.name }}</td>
							<td>{{ item.terms }}</td>
							<td>{{ item.max_redemptions }}</td>
							<td>{{ item.redeem_by }}</td>
							<td>
								<v-icon v-if="item.valid">fas fa-check</v-icon>
								<v-icon v-else>fas fa-times</v-icon>
							</td>
							<td>{{ item.promotion_codes }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn
									x-small
									@click="getCouponDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-if="ending_before" v-on:click="getCoupons(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-if="starting_after" v-on:click="getCoupons(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>