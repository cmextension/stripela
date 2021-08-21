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
				loading: true,
			}
		},
		template: '#quotes',
		computed: {},
		methods: {}
	})
</script>
<script type="text/x-template" id="quotes">
	<div>
		<h2><?php echo Text::_('COM_STRIPELA_QUOTES') ; ?></h2>

		<div v-show="loading" class="text-center">
			<v-progress-circular indeterminate></v-progress-circular>
		</div>
	</div>
</script>