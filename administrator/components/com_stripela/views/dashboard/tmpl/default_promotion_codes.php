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
	var PromotionCodes = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				codes: [],
				starting_after: '',
				ending_before: '',
				date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
				filter_from_menu: false,
				filter_to_menu: false,
				filter_active: '',
				filter_code: '',
				filter_customer: '',
				filter_from: '',
				filter_to: '',
				dialog: false,
				loadingDetail: true,
				codeDetailError: '',
				code: null,
				statuses: [
					{ text: '<?php echo Text::_('COM_STRIPELA_ACTIVE'); ?>', value: 1 },
					{ text: '<?php echo Text::_('COM_STRIPELA_INACTIVE'); ?>', value: 0 }
				]
			}
		},
		template: '#codes',
		watch: {
			dialog: function(val) {
				if (!val)
					this.code = null
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
				this.getCodes()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_customer = ''
				this.filter_active = ''
				this.filter_code = ''
				this.filter_from = ''
				this.filter_to = ''
				this.getCodes()
			},
			getCodes: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=promotioncode.getPromotionCodes&' + token + '=1'
				url += '&active=' + _this.filter_active

				// Go to previous page.
				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				if (_this.filter_code)
					url += '&code=' + _this.filter_code

				if (_this.filter_customer)
					url += '&customer=' + _this.filter_customer

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

						_this.codes = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getCodeDetail: function(id) {
				let url = componentRoute + '&task=promotioncode.getPromotionCode&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.codeDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.codeDetailError = r.message

						if (r.success && r.data)
							_this.code = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getCodes()
		}
	})
</script>
<script type="text/x-template" id="codes">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_PROMOTION_CODES') ; ?></h2>

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
						<span class="text-h5" v-if="code">{{ code.id }}</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="codeDetailError"
							type="error"
							dense
							outlined
						>
							{{ codeDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<v-simple-table v-if="code !== null">
							<template v-slot:default>
								<tbody>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
										<td>{{ code.id }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_CODE'); ?></th>
										<td>{{ code.code }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_ACTIVE'); ?></th>
										<td>
											<v-icon v-if="code.active">fas fa-check</v-icon>
											<v-icon v-else>fas fa-times</v-icon>
										</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_COUPON'); ?></th>
										<td>{{ code.coupon }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_TERMS'); ?></th>
										<td>{{ code.terms }}</td>
									</tr>
									<tr v-show="code.customer">
										<th><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
										<td>{{ code.customer }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_MAX_REDEMPTIONS'); ?></th>
										<td>{{ code.max_redemptions }}</td>
									</tr>
									<tr v-show="code.metadata.length > 0">
										<th><?php echo Text::_('COM_STRIPELA_METADATA'); ?></th>
										<td>
											<div v-for="(value, key) in code.metadata">
												{{ key }}: {{ value }}
											</div>
										</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_FIRST_TIME_TRANSACTION'); ?></th>
										<td>
											<v-icon v-if="code.first_time_transaction">fas fa-check</v-icon>
											<v-icon v-else>fas fa-times</v-icon>
										</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_MINIMUM_AMOUNT'); ?></th>
										<td>{{ code.minimum_amount }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_REDEMPTIONS'); ?></th>
										<td>{{ code.times_redeemed }}</td>
									</tr>
									<tr v-show="code.expires_at">
										<th><?php echo Text::_('COM_STRIPELA_EXPIRES_AT'); ?></th>
										<td>{{ code.expires_at }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
										<td>{{ code.created }}</td>
									</tr>
								</tbody>
							</template>
						</v-simple-table>
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
					<v-text-field
						v-model="filter_code"
						label="<?php echo Text::_('COM_STRIPELA_CODE'); ?>"
						prepend-icon="fas fa-tag fa-fw"
					></v-text-field>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="2"
				>
					<v-select
						v-model="filter_active"
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
							next-icon="fa fa-chevron-right"
							prev-icon="fa fa-chevron-left"
						></v-date-picker>
					</v-menu>
				</v-col>

				<v-col
					cols="12"
					sm="6"
					md="2"
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CODE'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_ACTIVE'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_COUPON'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_EXPIRES_AT'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in codes" :key="item.id">
							<td>{{ item.code }}</td>
							<td>
								<v-icon v-if="item.active">fas fa-check</v-icon>
								<v-icon v-else>fas fa-times</v-icon>
							</td>
							<td>{{ item.coupon }}</td>
							<td>{{ item.customer }}</td>
							<td>{{ item.expires_at }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn
									x-small
									@click="getCodeDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-if="ending_before" v-on:click="getCodes(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-if="starting_after" v-on:click="getCodes(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>