<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


namespace Joomla\Component\MigrateToJoomla\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Http\HttpFactory;
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
 

class HttpHelper
{	
    /**
     * Method to check Enter http url connection
     * 
     * @param string Http url of live website
     * @return boolean True on success
     * 
     * since
     */
    public static function testhttpconnection($url = NULL)
    {
        
        $app   = Factory::getApplication();
       
        $headers = [];
        try 
        {
           $response = HttpFactory::getHttp()->get($url, $headers);
           $statusCode = $response->code;
        
           if($statusCode==200) {

           $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_SUCCESSFULLY') , 'success');

           }
           else
           {
        
           $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_UNSUCCESSFULLY'), 'warning');

           }
        } 
        catch (\RuntimeException $exception)
        {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_UNSUCCESSFULLY'), 'warning');

        }

    }
    
    /**
    *  Method to get content of File with Http
    * 
    * @param string Source 
    * @return string File content
    */

    public static function getcontent($source)
    {
       $content = false;
       $source = str_replace(" ", "%20", $source); // for filenames with spaces

       $http = HttpFactory::getHttp();

       $response = $http->get($source);


    }    
    
}?>
