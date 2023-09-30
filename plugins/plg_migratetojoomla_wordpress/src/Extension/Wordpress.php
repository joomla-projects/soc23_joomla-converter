<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.contact
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\Wordpress\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\CMS\Form\Form;
use ReflectionClass;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use stdClass;
use Joomla\Database\DatabaseDriver;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\LogHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Wordpress Plugin
 *
 * @since  1.0
 */

final class Wordpress extends CMSPlugin implements SubscriberInterface
{

    /**
     * @var object Database object
     * 
     * @since 1.0
     */
    public $db;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareFormmigrate' => 'onContentPrepareForm',
            'migratetojoomla_user' => 'importUsers',
            'migratetojoomla_tags' => 'importTags',
            'migratetojoomla_category' => 'importCategory',
        ];
    }

    /**
     * The form event.
     *
     * @param   Form      $form  The form
     * @param   stdClass  $data  The data
     *
     * @return   boolean
     *
     * @since   4.0.0
     */
    public function onContentPrepareForm(EventInterface $event)
    {
        $form = $event->getArgument('form');
        $formName = $event->getArgument('formname');

        if ($this->_name !== $event->getArgument('framework')) {
            return true;
        }

        $allowedForms = [
            'com_migratetojoomla.parameter'
        ];

        if (!in_array($formName, $allowedForms, true)) {
            return true;
        }

        Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

        $form->loadFile('wordpress', false);

        $data = Factory::getApplication()->getUserState('com_migratetojoomla.parameter', []);

        if (array_key_exists('frameworkparams', $data)) {

            // form data of plugin form
            $dataextend = $data['frameworkparams'];

            foreach ($dataextend as $field => $value) {
                $form->setValue($field, 'frameworkparams', $value);
            }
        }

        return true;
    }

    /**
     * Method to set database $db if it is not set
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function setdatabase($instance, $data = [])
    {
        if (\is_resource($instance->db)) {
            return true;
        }

        $options = [
            'driver'    => $data['dbdriver'],
            'host'      => $data['dbhostname'] . ':' . $data['dbport'],
            'user'      => $data['dbusername'],
            'password'  => $data['dbpassword'],
            'database'  => $data['dbname'],
            'prefix'    => $data['dbtableprefix'],
        ];

        try {
            $db = DatabaseDriver::getInstance($options);
            $db->getVersion();
            $instance->db = $db;
            return true;
        } catch (\RuntimeException $th) {
            LogHelper::writeLog(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY'), 'error');
            LogHelper::writeLog($th, 'normal');
            return false;
        }
    }

    /** 
     * Method to import user table
     * 
     * @since 1.0
     */
    public function importUsers()
    {
        $totalusers = 0;
        $successcount  = 0;
        try {

            if (!\is_resource($this->db)) {
                self::setdatabase($this, Factory::getApplication()->getUserState('com_migratetojoomla.information', []));
            }
            $data = Factory::getApplication()->getUserState('com_migratetojoomla.information', []);
            $db = $this->db;

            // Specify the table name
            $tableName = rtrim($data['dbtableprefix'], '_') . '_users';
            $config['dbo'] = $db;
            $tablePrefix = Factory::getConfig()->get('dbprefix');

            // load data from framework table
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tableName));

            $db->setQuery($query);
            $results = $db->loadAssocList();

            $totalusers = count($results);
            foreach ($results as $row) {

                $user = new stdClass();
                $user->id = $row['ID'];
                $user->name = $row['display_name'];
                $user->username = $row['user_login'];
                $user->email = $row['user_email'];
                $user->registerDate = $row['user_registered'];
                $user->activation = $row['user_activation_key'];
                $user->requireReset = 1;
                $user->params = '{"admin_style":"","admin_language":"","language":"","editor":"","timezone":"","a11y_mono":"0","a11y_contrast":"0","a11y_highlight":"0","a11y_font":"0"}';

                $jdb = Factory::getDbo()->insertObject($tablePrefix . 'users', $user);

                $successcount = $successcount + 1;
            }

            $contentTowrite = 'User Imported Successfully = ' . $successcount;
            LogHelper::writeLog($contentTowrite, 'success');
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('User Imported Successfully = ' . $successcount, 'success');
            LogHelper::writeLog('User Imported Unsuccessfully = ' . $totalusers - $successcount, 'error');
            LogHelper::writeLog($th, 'normal');
        }
    }

    /** 
     * Method to import tag table
     * 
     * @since 1.0
     */
    public function importTags()
    {
        $totalusers = 0;
        $successcount  = 0;

        // current login user
        $user = Factory::getApplication()->getIdentity();
        $userid = $user->id;

        // datetime

        $date = (string)Factory::getDate();

        try {

            if (!\is_resource($this->db)) {
                self::setdatabase($this, Factory::getApplication()->getUserState('com_migratetojoomla.information', []));
            }
            $data = Factory::getApplication()->getUserState('com_migratetojoomla.information', []);
            $db = $this->db;

            // Specify the table name
            $tabletermtaxonomy = rtrim($data['dbtableprefix'], '_') . '_term_taxonomy';
            $tableterms = rtrim($data['dbtableprefix'], '_') . '_terms';
            $config['dbo'] = $db;
            $tablePrefix = Factory::getConfig()->get('dbprefix');

            // load data from framework table
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tabletermtaxonomy, 'b'))
                ->join('LEFT', $db->quoteName($tableterms, 'a'), $db->quoteName('a.term_id') . '=' . $db->quoteName('b.term_id'))
                ->where($db->quoteName('b.taxonomy') . '=' . $db->q('post_tag'));

            $db->setQuery($query);
            $results = $db->loadAssocList();

            $totalusers = count($results);
            foreach ($results as $row) {

                $tag = new stdClass();
                $tag->id = $row['term_id'];
                $tag->parent_id = 0;
                $tag->lft = 0;
                $tag->rgt = 0;
                $tag->level = 0;
                $tag->path = $row['name'];
                $tag->title = $row['name'];
                $tag->alias = $row['slug'];
                $tag->note = "";
                $tag->description = $row['description'];
                $tag->published = 0;
                $tag->check_out = NULL;
                $tag->check_out_time = NULL;
                $tag->access = 0;
                $tag->params = '{}';
                $tag->metadesc = '';
                $tag->metakey = '';
                $tag->metadata = '{}';
                $tag->created_user_id = $userid;
                $tag->created_time = $date;
                $tag->created_by_alias = '';
                $tag->modified_user_id = $userid;
                $tag->modified_time = $date;
                $tag->images = '{}';
                $tag->urls = '{}';
                $tag->hits = 0;
                $tag->language = '*';
                $tag->version = 1;
                $tag->publish_up = $date;
                $tag->publish_down = NULL;

                $jdb = Factory::getDbo()->insertObject($tablePrefix . 'tags', $tag);

                $successcount = $successcount + 1;
            }

            $contentTowrite = 'Tags Imported Successfully = ' . $successcount;
            LogHelper::writeLog($contentTowrite, 'success');
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Tags Imported Successfully = ' . $successcount, 'success');
            LogHelper::writeLog('Tags Imported Unsuccessfully = ' . $totalusers - $successcount, 'error');
            LogHelper::writeLog($th, 'normal');
        }
    }

    /** 
     * Method to import category table
     * 
     * @since 1.0
     */
    public function importCategory()
    {
        $totalusers = 0;
        $successcount  = 0;

        // current login user
        $user = Factory::getApplication()->getIdentity();
        $userid = $user->id;

        // datetime
        $date = (string)Factory::getDate();

        try {

            if (!\is_resource($this->db)) {
                self::setdatabase($this, Factory::getApplication()->getUserState('com_migratetojoomla.information', []));
            }
            $data = Factory::getApplication()->getUserState('com_migratetojoomla.information', []);
            $db = $this->db;

            // Specify the table name
            $tabletermtaxonomy = rtrim($data['dbtableprefix'], '_') . '_term_taxonomy';
            $tableterms = rtrim($data['dbtableprefix'], '_') . '_terms';
            $config['dbo'] = $db;
            $tablePrefix = Factory::getConfig()->get('dbprefix');

            // load data from framework table
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tabletermtaxonomy, 'b'))
                ->join('LEFT', $db->quoteName($tableterms, 'a'), $db->quoteName('a.term_id') . '=' . $db->quoteName('b.term_id'))
                ->where($db->quoteName('b.taxonomy') . '=' . $db->q('category'));

            $db->setQuery($query);
            $results = $db->loadAssocList();

            $patharray = [];
            $levelarray = [];

            LogHelper::writeLog('No of results' . '  ' . count($results), 'success');
            // manupulate data to find parentcategory path and level

            foreach ($results as $row) {

                if ($row['parent'] != 0) {
                    // It is a child category

                    $parrentsarray = [];
                    $currentelement = $row;
                    $iteration = 0;
                    while ($currentelement['parent'] != 0 && $iteration < count($results)) {
                        array_push($parrentsarray, $currentelement['name']);
                        $iteration = $iteration + 1;

                        // finding parent row and assign it as currentelement for next iteration

                        foreach ($results as $rowtemp) {
                            if ($rowtemp['term_id'] == $currentelement['parent']) {
                                $currentelement = $rowtemp;
                                break;
                            }
                        }
                    }

                    // pushing currentelement in parent array
                    array_push($parrentsarray, $currentelement['name']);
                    // reverse array element

                    $reverseparent = array_reverse($parrentsarray);

                    $path = implode('/', $reverseparent);

                    array_push($patharray, $path);
                    array_push($levelarray, count($parrentsarray));
                } else {
                    // It is not a child category
                    array_push($patharray, $row['name']);
                    array_push($levelarray, 1);
                }
            }

            $totalusers = count($results);
            $count  = 0;
            foreach ($results as $row) {

                $category = new stdClass();
                $category->id = $row['term_id'];
                $category->asset_id = 0;
                $category->parent_id = $row['parent'];
                $category->lft = 0;
                $category->rgt = 0;
                $category->level = $levelarray[$count];
                $category->path = $patharray[$count];
                $category->extension = 'com_content';
                $category->title = $row['name'];
                $category->alias = $row['slug'];
                $category->note = "";
                $category->description = $row['description'];
                $category->published = 0;
                $category->check_out = NULL;
                $category->check_out_time = NULL;
                $category->access = 0;
                $category->params = '{}';
                $category->metadesc = '';
                $category->metakey = '';
                $category->metadata = '{}';
                $category->created_user_id = $userid;
                $category->created_time = $date;
                $category->modified_user_id = $userid;
                $category->modified_time = $date;
                $category->hits = 0;
                $category->language = '*';
                $category->version = 1;

                $jdb = Factory::getDbo()->insertObject($tablePrefix . 'categories', $category);

                $count = $count + 1;
                $successcount = $successcount + 1;
            }

            $contentTowrite = 'Category Imported Successfully = ' . $successcount;
            LogHelper::writeLog($contentTowrite, 'success');
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Category Imported Successfully = ' . $successcount, 'success');
            LogHelper::writeLog('Category Imported Unsuccessfully = ' . $totalusers - $successcount, 'error');
            LogHelper::writeLog($th, 'normal');
        }
    }
}
