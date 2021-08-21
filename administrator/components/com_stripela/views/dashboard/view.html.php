<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * Stripe dashboard.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaViewDashboard extends HtmlView
{
	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('COM_STRIPELA_STRIPE'), 'dashboard');

		$components = StripelaHelper::getComponents();
		$bar = Toolbar::getInstance('toolbar');

		$html = '<joomla-toolbar-button>';
		$html .= '<div class="dropdown">';
		$html .= '<button class="btn btn-primary" type="button" id="stripelaDropdown" data-bs-toggle="dropdown" aria-expanded="false">';
		$html .= '<span class="icon-list icon-fw"></span> ' . Text::_('COM_STRIPELA_MENU');
		$html .= '</button>';
		$html .= '<ul class="dropdown-menu stripela-dropdown-menu" aria-labelledby="stripelaDropdown">';

		foreach ($components as $component)
		{
			$html .= '<li>';
			$html .= '<a class="dropdown-item" href="' . $component['route'] . '">';
			$html .= '<span class="fas ' . $component['icon'] . ' fa-fw"></span> ' . $component['name'] . '</a>';
			$html .= '</li>';
		}

		$html .= '</ul>';
		$html .= '</div>';

		$html .= '</joomla-toolbar-button>';

		$bar->appendButton('Custom', $html, Text::_('COM_STRIPELA_MENU'));

		if (Factory::getUser()->authorise('core.admin', 'com_stripela'))
		{
			ToolbarHelper::preferences('com_stripela');
		}
	}
}
