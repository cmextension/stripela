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
	var Customers = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				customers: [],
				starting_after: '',
				ending_before: '',
				filter_email: '',
				dialog: false,
				loadingDetail: true,
				customerDetailError: '',
				customer: null,
			}
		},
		template: '#customers',
		watch: {
			dialog: function(val) {
				if (!val)
					this.customer = null
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
				this.getCustomers()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_email = ''
				this.getCustomers()
			},
			getCustomers: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=customer.getCustomers&' + token + '=1'

				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				if (_this.filter_email)
					url += '&email=' + _this.filter_email

				_this.loadingList = true

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						_this.loadingList = false

						if (!r.success)
							return

						_this.customers = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getCustomerDetail: function(id) {
				let url = componentRoute + '&task=customer.getCustomer&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.customerDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.customerDetailError = r.message

						if (r.success && r.data)
							_this.customer = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getCustomers()
		}
	})
</script>
<script type="text/x-template" id="customers">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_CUSTOMERS') ; ?></h2>

		<v-divider></v-divider>

		<div v-show="loadingList" class="text-center">
			<v-progress-circular indeterminate></v-progress-circular>
		</div>

		<div v-show="!loadingList">
			<v-dialog
				v-model="dialog"
				width="600px"
			>
				<v-card>
					<v-card-title>
						<span class="text-h5">
							<span v-if="customer && customer.name">{{ customer.name }}</span>
							<span v-else-if="customer && customer.email">{{ customer.email }}</span>
							<span v-else-if="customer && customer.id">{{ customer.id }}</span>
						</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="customerDetailError"
							type="error"
							dense
							outlined
						>
							{{ customerDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<v-simple-table v-if="customer">
							<template v-slot:default>
								<tbody>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
										<td>{{ customer.id }}</td>
									</tr>
									<tr v-show="customer.name">
										<th><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
										<td>{{ customer.name }}</td>
									</tr>
									<tr v-show="customer.email">
										<th><?php echo Text::_('COM_STRIPELA_EMAIL'); ?></th>
										<td>{{ customer.email }}</td>
									</tr>
									<tr v-show="customer.description">
										<th><?php echo Text::_('COM_STRIPELA_DESCRIPTION'); ?></th>
										<td>{{ customer.description }}</td>
									</tr>
									<tr v-show="customer.currency">
										<th><?php echo Text::_('COM_STRIPELA_CURRENCY'); ?></th>
										<td>{{ customer.currency }}</td>
									</tr>
									<tr v-show="customer.address">
										<th><?php echo Text::_('COM_STRIPELA_ADDRESS'); ?></th>
										<td>{{ customer.address }}</td>
									</tr>
									<tr v-show="customer.phone">
										<th><?php echo Text::_('COM_STRIPELA_PHONE'); ?></th>
										<td>{{ customer.phone }}</td>
									</tr>
									<tr>
										<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
										<td>{{ customer.created }}</td>
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
					md="6"
				>
					<v-text-field
						v-model="filter_email"
						label="Email"
						prepend-icon="fas fa-user fa-fw"
					></v-text-field>
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_EMAIL'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_DESCRIPTION'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in customers" :key="item.id">
							<td>{{ item.name }}</td>
							<td>{{ item.email }}</td>
							<td>{{ item.description }}</td>
							<td>{{ item.created }}</td>
							<td>
								<v-btn
									x-small
									@click="getCustomerDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-show="ending_before" v-on:click="getCustomers(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-show="starting_after" v-on:click="getCustomers(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>