<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

$doc = Factory::getDocument();
$doc->addStyleSheet('https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css');
$doc->addStyleSheet('components/com_stripela/assets/css/stripela.css');
$doc->addScript('https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js');
$doc->addScript('https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js');
$doc->addScript('https://cdn.jsdelivr.net/npm/vue-router@3.5.2/dist/vue-router.min.js');
$doc->addScript('components/com_stripela/assets/js/stripela.js');

$doc->addScriptDeclaration('var token = "' . Session::getFormToken() . '";');

$components = StripelaHelper::getComponents();

foreach ($components as $component)
{
	echo $this->loadTemplate($component['layout']);
}
?>
<div id="stripela" v-cloak>
	<v-main>
		<div class="router-view-container">
			<router-view></router-view>
		</div>
	</v-main>
</div>