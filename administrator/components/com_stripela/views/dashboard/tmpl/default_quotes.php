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
	var Quotes = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				quotes: [],
				starting_after: '',
				ending_before: '',
				date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
				filter_from_menu: false,
				filter_to_menu: false,
				filter_customer: '',
				filter_from: '',
				filter_to: '',
				dialog: false,
				loadingDetail: true,
				quoteDetailError: '',
				quote: null,
			}
		},
		template: '#quotes',
		watch: {
			dialog: function(val) {
				if (!val)
					this.quote = null
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
				this.getQuotes()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_customer = ''
				this.filter_from = ''
				this.filter_to = ''
				this.getQuotes()
			},
			getQuotes: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=quote.getQuotes&' + token + '=1'

				// Go to previous page.
				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

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

						_this.quotes = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getQuoteDetail: function(id) {
				let url = componentRoute + '&task=quote.getQuote&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.quoteDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.quoteDetailError = r.message

						if (r.success && r.data)
							_this.quote = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getQuotes()
		}
	})
</script>
<script type="text/x-template" id="quotes">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_QUOTES') ; ?></h2>

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
						<span v-if="quote && quote.name">{{ quote.name }}</span>
						<span v-else-if="quote && quote.id">{{ quote.id }}</span>
					</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="quoteDetailError"
							type="error"
							dense
							outlined
						>
							{{ quoteDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<v-simple-table v-if="quote !== null">
							<template v-slot:default>
								<tbody>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
										<td>{{ quote.id }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_TOTAL'); ?></th>
										<td>{{ quote.amount_total }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
										<td>{{ quote.customer }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_QUOTE_NUMBER'); ?></th>
										<td>{{ quote.number }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_STATUS'); ?></th>
										<td>{{ quote.status }}</td>
									</tr>
									<tr v-show="quote.metadata.length > 0">
										<th><?php echo Text::_('COM_STRIPELA_METADATA'); ?></th>
										<td>
											<div v-for="(value, key) in quote.metadata">
												{{ key }}: {{ value }}
											</div>
										</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_EXPIRES_AT'); ?></th>
										<td>{{ quote.expires_at }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
										<td>{{ quote.created }}</td>
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
					md="3"
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_TOTAL'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CUSTOMER'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_QUOTE_NUMBER'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_STATUS'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_EXPIRES_AT'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in quotes" :key="item.id">
							<td>{{ item.amount_total }}</td>
							<td>{{ item.customer }}</td>
							<td>{{ item.number }}</td>
							<td>{{ item.status }}</td>
							<td>{{ item.expires_at }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn
									x-small
									@click="getQuoteDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-if="ending_before" v-on:click="getQuotes(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-if="starting_after" v-on:click="getQuotes(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>