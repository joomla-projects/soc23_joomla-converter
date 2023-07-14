<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Versioning\VersionableControllerTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * main controller class.
 *
 * @since  
 */
class HttpController extends FormController
{
    use VersionableControllerTrait;
    /**
     * Method to check Enter http url connection
     * 
     * @return boolean True on success
     * 
     * since
     */
    public function testhttpconnection($url = NULL) {
        
        $http = HttpFactory::getHttp();
        
        $response = $http->get($url);

        $statusCode = $response->code;

        return ($statusCode==200);

    }
    
}
