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
	var Products = Vue.extend({
		data: function() {
			return {
				loadingList: true,
				products: [],
				starting_after: '',
				ending_before: '',
				filter_active: 1,
				dialog: false,
				loadingDetail: true,
				productDetailError: '',
				product: null,
				statuses: [
					{ text: '<?php echo Text::_('COM_STRIPELA_ACTIVE'); ?>', value: 1 },
					{ text: '<?php echo Text::_('COM_STRIPELA_ARCHIVED'); ?>', value: 0 }
				]
			}
		},
		template: '#products',
		watch: {
			dialog: function(val) {
				if (!val)
					this.product = null
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
				this.getProducts()
			},
			clearFilter: function() {
				this.starting_after = ''
				this.ending_before = ''
				this.filter_active = 1
				this.getProducts()
			},
			getProducts: function(directionKey) {
				let _this = this
				let url = componentRoute + '&task=product.getProducts&' + token + '=1'

				if (directionKey == -1)
					url +=  '&ending_before=' + _this.ending_before

				if (directionKey == 1)
					url +=  '&starting_after=' + _this.starting_after

				url += '&active=' + _this.filter_active

				_this.loadingList = true

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						_this.loadingList = false

						if (!r.success)
							return

						_this.products = r.data.items
						_this.starting_after = r.data.starting_after
						_this.ending_before = r.data.ending_before
					}
				})
			},
			getProductDetail: function(id) {
				let url = componentRoute + '&task=product.getProduct&' + token + '=1&id=' + id
				this.loadingDetail = true
				this.productDetailError = ''
				this.dialog = true

				let _this = this

				$.ajax({
					url: url,
					method: 'GET',
					dataType: 'json',
					success: function(r) {
						if (r.message)
							_this.productDetailError = r.message

						if (r.success && r.data)
							_this.product = r.data

						_this.loadingDetail = false
					}
				})
			}
		},
		mounted: function() {
			this.getProducts()
		}
	})
</script>
<script type="text/x-template" id="products">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_PRODUCTS') ; ?></h2>

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
						<span class="text-h5" v-if="product && product.name">{{ product.name }}</span>
					</v-card-title>
					<v-card-text>
						<v-alert
							v-show="productDetailError"
							type="error"
							dense
							outlined
						>
							{{ productDetailError }}
						</v-alert>
						<div v-show="loadingDetail" class="text-center">
							<v-progress-circular indeterminate></v-progress-circular>
						</div>

						<v-simple-table v-if="product !== null">
							<template v-slot:default>
							<tbody>
								<tr>
									<th><?php echo Text::_('COM_STRIPELA_ID'); ?></th>
									<td>{{ product.id }}</td>
								</tr>
								<tr v-show="product.name">
									<th><?php echo Text::_('COM_STRIPELA_NAME'); ?></th>
									<td>{{ product.name }}</td>
								</tr>
								<tr v-show="product.description">
									<th><?php echo Text::_('COM_STRIPELA_DESCRIPTION'); ?></th>
									<td>{{ product.description }}</td>
								</tr>
								<tr v-show="product.images.length > 0">
									<th><?php echo Text::_('COM_STRIPELA_IMAGES'); ?></th>
									<td>
										<div v-for="i in product.images">
											<v-img v-bind:src="i" :width="150"></v-img>
										</div>
									</td>
								</tr>
								<tr v-show="product.metadata.length > 0">
									<th><?php echo Text::_('COM_STRIPELA_METADATA'); ?></th>
									<td>
										<div v-for="(value, key) in product.metadata">
											{{ key }}: {{ value }}
										</div>
									</td>
								</tr>
								<tr v-show="product.pricing.length > 0">
									<th><?php echo Text::_('COM_STRIPELA_PRICING'); ?></th>
									<td>
										<div v-for="(p, index) in product.pricing">
											{{ p }}
										</div>
									</td>
								</tr>
								<tr>
									<th><?php echo Text::_('COM_STRIPELA_ACTIVE'); ?></th>
									<td>
										<v-icon v-if="product.active">fas fa-check</v-icon>
										<v-icon v-else>fas fa-times</v-icon>
									</td>
								</tr>
								<tr>
									<th><?php echo Text::_('COM_STRIPELA_SHIPPABLE'); ?></th>
									<td>
										<v-icon v-if="product.shippable">fas fa-check</v-icon>
										<v-icon v-else>fas fa-times</v-icon>
									</td>
								</tr>
								<tr>
									<th><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
									<td>{{ product.created }}</td>
								</tr>
								<tr>
									<th><?php echo Text::_('COM_STRIPELA_UPDATED'); ?></th>
									<td>{{ product.updated }}</td>
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
					<v-select
						v-model="filter_active"
						label="<?php echo Text::_('COM_STRIPELA_STATUS'); ?>"
						:items="statuses"
					>
					</v-select>
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
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_PRICING'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_CREATED'); ?></th>
							<th class="text-left"><?php echo Text::_('COM_STRIPELA_UPDATED'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in products" :key="item.id">
							<td>{{ item.name }}</td>
							<td>
								<div v-for="(p, index) in item.pricing">
									{{ p }}
								</div>
							</td>
							<td>{{ item.created }}</td>
							<td>{{ item.updated }}</td>
							<td>
								<v-btn
									x-small
									@click="getProductDetail(item.id)"
								>
									<v-icon x-small>fas fa-eye</v-icon>
								</v-btn>
							</td>
						</tr>
					</tbody>
				</template>
			</v-simple-table>

			<div class="stripela-pagination float-right">
				<v-btn v-if="ending_before" v-on:click="getProducts(-1)">
					<v-icon>fas fa-chevron-left</v-icon>
				</v-btn>

				<v-btn v-if="starting_after" v-on:click="getProducts(1)">
					<v-icon>fas fa-chevron-right</v-icon>
				</v-btn>
			</div>
		</div>
	</div>
</script>