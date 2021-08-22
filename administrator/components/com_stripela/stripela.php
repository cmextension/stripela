<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\HTML\HTMLHelper;

require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/stripela.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/vendor/autoload.php';


if (!Factory::getUser()->authorise('core.manage', 'stripela'))
{
	return StripelaHelper::throwException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

HTMLHelper::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');

$controller = BaseController::getInstance('Stripela');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
