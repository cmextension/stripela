<?php
/**
 * @package     Stripela
 * @subpackage  com_stripela
 * @copyright   Copyright (C) 2021 CMExtension
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Input\Input;
use Joomla\CMS\Session\Session;

/**
 * Base controller.
 *
 * @package     Stripela
 * @subpackage  com_stripela
 * @since       1.0.0
 */
class StripelaControllerBase extends BaseController
{
	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_task', 'model_path', and
	 * 'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The JApplication for the dispatcher
	 * @param   Input                $input    Input
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct();

		if (!Session::checkToken('get'))
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);

			$this->app->close();
		}
	}
}