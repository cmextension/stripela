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
	var Invoices = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				invoices: [],
				starting_after: '',
				ending_before: '',
				date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
				filter_from_menu: false,
				filter_to_menu: false,
				filter_due_date_from_menu: false,
				filter_due_date_to_menu: false,
				filter_customer: '',
				filter_subscription: '',
				filter_due_date_from: '',
				filter_due_date_to: '',
				filter_from: '',
				filter_to: '',
				dialog: false,
				loadingDetail: true,
				invoiceDetailError: '',
				invoice: null,
			}
		},
		template: '#invoices',
		watch: {
			dialog: function(val) {
				if (!val)
					this.invoice = null
			}
		},
		computed: {
			computedFromDateFormatted() {
				return this.formatDate(this.filter_from)
			},
			computedToDateFormatted() {
				return this.formatDate(this.filter_to)
			},
			computedFromDueDateFormatted() {
				return this.formatDate(this.filter_due_date_from)
			},
			computedToDueDateFormatted() {
				return this.formatDate(this.filter_due_date_to)
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
				this.getInvoices()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_customer = ''
				this.filter_subscription = ''
				this.filter_due_date_from = ''
				this.filter_due_date_to = ''
				this.filter_from = ''
				this.filter_to = ''
				this.getInvoices()
			},
			getInvoices: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=invoice.getInvoices&' + token + '=1'

				// Go to previous page.
				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				// Go to next page.
				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				if (_this.filter_customer)
					url += '&customer=' + _this.filter_customer

				if (_this.filter_subscription)
					url += '&subscription=' + _this.filter_subscription

				if (_this.filter_due_date_from)
					url += '&due_date_from=' + _this.filter_due_date_from

				if (_this.filter_due_date_to)
					url += '&due_date_to=' + _this.filter_due_date_to

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

						_this.invoices = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getInvoiceDetail: function(id) {
				let url = componentRoute + '&task=invoice.getInvoice&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.invoiceDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.invoiceDetailError = r.message

						if (r.success && r.data)
							_this.invoice = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getInvoices()
		}
	})
</script>
<script type="text/x-template" id="invoices">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_INVOICES') ; ?></h2>

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
						<span v-if="invoice && invoice.name">{{ invoice.name }}</span>
						<span v-else-if="invoice && invoice.id">{{ invoice.id }}</span>
					</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="invoiceDetailError"
							type="error"
							dense
							outlined
						>
							{{ invoiceDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<div v-if="invoice !== null">
							<v-simple-table>
								<template v-slot:default>
									<tbody>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
											<td>{{ invoice.id }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_INVOICE_NUMBER'); ?></th>
											<td>{{ invoice.number }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_TOTAL'); ?></th>
											<td>{{ invoice.total }}</td>
										</tr>
										<tr v-show="invoice.description">
											<th><?php echo Text::_('COM_STRIPELA_DESCRIPTION'); ?></th>
											<td>{{ invoice.description }}</td>
										</tr>
										<tr v-show="invoice.subscription_id">
											<th><?php echo Text::_('COM_STRIPELA_SUBSCRIPTION'); ?></th>
											<td>{{ invoice.subscription_id }}</td>
										</tr>
										<tr v-show="invoice.payment_id">
											<th><?php echo Text::_('COM_STRIPELA_PAYMENT'); ?></th>
											<td>{{ invoice.payment_id }}</td>
										</tr>
										<tr v-show="invoice.url">
											<th><?php echo Text::_('COM_STRIPELA_URL'); ?></th>
											<td>
												<a :href="invoice.url" target="_blank">
													<?php echo Text::_('COM_STRIPELA_OPEN'); ?>
												</a>
											</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_STATUS'); ?></th>
											<td>{{ invoice.status }}</td>
										</tr>
										<tr v-show="invoice.metadata.length > 0">
											<th><?php echo Text::_('COM_STRIPELA_METADATA'); ?></th>
											<td>
												<div v-for="(value, key) in invoice.metadata">
													{{ key }}: {{ value }}
												</div>
											</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_DUE_DATE'); ?></th>
											<td>{{ invoice.due_date }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
											<td>{{ invoice.created }}</td>
										</tr>
									</tbody>
								</template>
							</v-simple-table>

							<span class="text-h6"><?php echo Text::_('COM_STRIPELA_CUSTOMER_INFO'); ?></span>

							<v-simple-table>
								<template v-slot:default>
									<tbody>
									<tr v-show="invoice.customer_name">
											<th><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
											<td>{{ invoice.customer_name }}</td>
										</tr>
										<tr>
											<th><?php echo Text::_('COM_STRIPELA_EMAIL'); ?></th>
											<td>{{ invoice.customer_email }}</td>
										</tr>
										<tr v-show="invoice.customer_phone">
											<th><?php echo Text::_('COM_STRIPELA_PHONE'); ?></th>
											<td>{{ invoice.customer_phone }}</td>
										</tr>
										<tr v-show="invoice.customer_address.line1">
											<th><?php echo Text::_('COM_STRIPELA_ADDRESS_LINE_1'); ?></th>
											<td>{{ invoice.customer_address.line1 }}</td>
										</tr>
										<tr v-show="invoice.customer_address.line2">
											<th><?php echo Text::_('COM_STRIPELA_ADDRESS_LINE_2'); ?></th>
											<td>{{ invoice.customer_address.line2 }}</td>
										</tr>
										<tr v-show="invoice.customer_address.city">
											<th><?php echo Text::_('COM_STRIPELA_CITY'); ?></th>
											<td>{{ invoice.customer_address.city }}</td>
										</tr>
										<tr v-show="invoice.customer_address.postal_code">
											<th><?php echo Text::_('COM_STRIPELA_POSTAL_CODE'); ?></th>
											<td>{{ invoice.customer_address.postal_code }}</td>
										</tr>
										<tr v-show="invoice.customer_address.state">
											<th><?php echo Text::_('COM_STRIPELA_STATE'); ?></th>
											<td>{{ invoice.customer_address.state }}</td>
										</tr>
										<tr v-show="invoice.customer_address.country">
											<th><?php echo Text::_('COM_STRIPELA_COUNTRY'); ?></th>
											<td>{{ invoice.customer_address.country }}</td>
										</tr>
									</tbody>
								</template>
							</v-simple-table>

							<div v-if="invoice.customer_shipping">
								<span class="text-h6"><?php echo Text::_('COM_STRIPELA_SHIPPING_INFO'); ?></span>

								<v-simple-table>
									<template v-slot:default>
										<tbody>
											<tr v-show="invoice.customer_shipping.name">
												<th><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
												<td>{{ invoice.customer_shipping.name }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.phone">
												<th><?php echo Text::_('COM_STRIPELA_PHONE'); ?></th>
												<td>{{ invoice.customer_shipping.phone }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.address.line1">
												<th><?php echo Text::_('COM_STRIPELA_ADDRESS_LINE_1'); ?></th>
												<td>{{ invoice.customer_shipping.address.line1 }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.address.line2">
												<th><?php echo Text::_('COM_STRIPELA_ADDRESS_LINE_2'); ?></th>
												<td>{{ invoice.customer_shipping.address.line2 }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.address.city">
												<th><?php echo Text::_('COM_STRIPELA_CITY'); ?></th>
												<td>{{ invoice.customer_shipping.address.city }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.address.postal_code">
												<th><?php echo Text::_('COM_STRIPELA_POSTAL_CODE'); ?></th>
												<td>{{ invoice.customer_shipping.address.postal_code }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.address.state">
												<th><?php echo Text::_('COM_STRIPELA_STATE'); ?></th>
												<td>{{ invoice.customer_shipping.address.state }}</td>
											</tr>
											<tr v-show="invoice.customer_shipping.address.country">
												<th><?php echo Text::_('COM_STRIPELA_COUNTRY'); ?></th>
												<td>{{ invoice.customer_shipping.address.country }}</td>
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
					<v-text-field
						v-model="filter_subscription"
						label="<?php echo Text::_('COM_STRIPELA_SUBSCRIPTION_ID'); ?>"
						prepend-icon="fas fa-users fa-fw"
					></v-text-field>
				</v-col>

				<v-col
					class="pb-0"
					cols="12"
					sm="6"
					md="3"
				>
					<v-menu
						v-model="filter_due_date_from_menu"
						:close-on-content-click="false"
						:nudge-right="40"
						transition="scale-transition"
						offset-y
						min-width="auto"
					>
						<template v-slot:activator="{ on, attrs }">
							<v-text-field
								v-model="computedFromDueDateFormatted"
								label="<?php echo Text::_('COM_STRIPELA_DUE_DATE_FROM'); ?>"
								prepend-icon="fas fa-calendar fa-fw"
								readonly
								v-bind="attrs"
								v-on="on"
							></v-text-field>
						</template>
						<v-date-picker
							v-model="filter_due_date_from"
							@input="filter_due_date_from_menu = false"
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
						v-model="filter_due_date_to_menu"
						:close-on-content-click="false"
						:nudge-right="40"
						transition="scale-transition"
						offset-y
						min-width="auto"
					>
						<template v-slot:activator="{ on, attrs }">
							<v-text-field
								v-model="computedToDueDateFormatted"
								label="<?php echo Text::_('COM_STRIPELA_DUE_DATE_TO'); ?>"
								prepend-icon="fas fa-calendar fa-fw"
								readonly
								v-bind="attrs"
								v-on="on"
							></v-text-field>
						</template>
						<v-date-picker
							v-model="filter_due_date_to"
							@input="filter_due_date_to_menu = false"
						></v-date-picker>
					</v-menu>
				</v-col>
			</v-row>

			<v-row class="mb-2">
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
						></v-date-picker>
					</v-menu>
				</v-col>

				<v-col
					cols="12"
					sm="6"
					md="6"
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_INVOICE_NUMBER'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_STATUS'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_DUE_DATE'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in invoices" :key="item.id">
							<td>{{ item.total }}</td>
							<td>{{ item.customer_name }}</td>
							<td>{{ item.number }}</td>
							<td>{{ item.status }}</td>
							<td>{{ item.due_date }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn
									x-small
									@click="getInvoiceDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-if="ending_before" v-on:click="getInvoices(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-if="starting_after" v-on:click="getInvoices(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>