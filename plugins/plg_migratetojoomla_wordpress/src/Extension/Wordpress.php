<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla.wordpress
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\Wordpress\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Component\Menus\Administrator\Table\MenuTable;
use Joomla\Component\Menus\Administrator\Table\MenuTypeTable;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\LogHelper;
use Joomla\Component\Tags\Administrator\Table\TagTable;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use stdClass;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Wordpress Plugin  for com_migratetojoomla
 *
 * @since  1.0
 */

final class Wordpress extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * @var  DatabaseInterface  DB object connected to the WP DB
     *
     * @since __DEPLOY_VERSION__
     */
    public  $wpDB;

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
            'migratetojoomla_storemaxprimarykey' => 'storeMaxPrimaryKey',
            'migratetojoomla_storeprimarykey' => 'storePrimaryKey',
            'migratetojoomla_createdisplaydata' => 'createDisplayData',
            'migratetojoomla_user' => 'importUser',
            'migratetojoomla_tag' => 'importTag',
            'migratetojoomla_category' => 'importCategory',
            'migratetojoomla_menu' => 'importMenu',
            'migratetojoomla_menuitem' => 'importMenuItem',
            'migratetojoomla_postsandpage' => 'importArticle'
        ];
    }

    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        $app = Factory::getApplication();
        self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
    }

    /**
     * Method to store max primary key of Joomla Table
     * 
     * @since   1.0
     */
    public static function storeMaxPrimaryKey()
    {
        $app = Factory::getApplication();
        $app->getSession()->clear('com_migratetojoomla.maxkey');
        $tables = [
            "users",
            "tags",
            "categories",
            "menu_types",
            "menu",
            "content"
        ];
        $maxKey = [];
        $db     = Factory::getContainer()->get(DatabaseInterface::class);;
        foreach ($tables as $table) {
            $tableName = '#__' . $table;
            $query     = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('id') . ')')
                ->from($db->quoteName($tableName));

            $db->setQuery($query);
            $maxKey[$table] = $db->loadResult();
        }

        // how status session value as if user want again import than max value of key must status to avoid duplicate key
        $app->getSession()->set('com_migratetojoomla.maxkey', $maxKey);
    }

    /**
     * Method to set database $wpDB if it is not set
     * 
     * @param object $instance instance of class
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function createWPDB($instance, $data = [])
    {
        if (\is_resource($instance->wpDB)) {
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
            $wpDB = DatabaseDriver::getInstance($options);
            $wpDB->getVersion();
            $instance->wpDB = $wpDB;
            $instance->setdatabase(Factory::getContainer()->get(DatabaseInterface::class));
            return true;
        } catch (\RuntimeException $th) {
            LogHelper::writeLog(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY'), 'error');
            LogHelper::writeLog($th, 'normal');
            return false;
        }
    }

    /**
     * Method to store primary key of tables into Session
     * 
     * @since 1.0
     */
    public function storePrimarykey()
    {
        $app = Factory::getApplication();
        if (!\is_resource($this->wpDB)) {
            self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
        }
        $app->getSession()->clear('com_migratetojoomla.tablekeys');
        $wpDB = $this->wpDB;

        $importstring = $app->getSession()->get('migratetojoomla.arrayimportstring', []);

        $tableusers = '#__users';
        $tableposts = '#__posts';
        $tablepostmeta = '#__postmeta';
        $tabletermtaxonomy = '#__term_taxonomy';
        $tableterms = '#__terms';
        $tabletermrelationship = '#__term_relationships';

        $tablesmap = [
            'user' => $wpDB->getQuery(true)->select($wpDB->quoteName("ID"))->from($wpDB->quoteName($tableusers)),
            'tag' => $wpDB->getQuery(true)->select($wpDB->quoteName("term_id"))->from($wpDB->quoteName($tabletermtaxonomy))->where($wpDB->quoteName('taxonomy') . '=' . $wpDB->q('post_tag')),
            "category" => $wpDB->getQuery(true)->select($wpDB->quoteName("term_id"))->from($wpDB->quoteName($tabletermtaxonomy))->where($wpDB->quoteName('taxonomy') . '=' . $wpDB->q('category')),
            "menu" => $wpDB->getQuery(true)->select($wpDB->quoteName("term_id"))->from($wpDB->quoteName($tabletermtaxonomy)),
            "menuitem" => $wpDB->getQuery(true)
                ->select('DISTINCT ID')
                ->from($wpDB->quoteName($tableposts, 'a'))
                ->leftjoin($wpDB->quoteName($tablepostmeta, 'b'), $wpDB->quoteName('a.ID') . '=' . $wpDB->quoteName('b.post_id'))
                ->leftjoin($wpDB->quoteName($tabletermrelationship, 'c'), $wpDB->quoteName('a.ID') . '=' . $wpDB->quoteName('c.object_id'))
                ->leftjoin($wpDB->quoteName($tabletermtaxonomy, 'd'), $wpDB->quoteName('c.term_taxonomy_id') . '=' . $wpDB->quoteName('d.term_taxonomy_id'))
                ->leftjoin($wpDB->quoteName($tableterms, 'e'), $wpDB->quoteName('d.term_id') . '=' . $wpDB->quoteName('e.term_id'))
                ->where($wpDB->quoteName('a.post_type') . '=' . $wpDB->q('nav_menu_item') . 'AND' . $wpDB->quoteName('b.meta_value') . '=' . $wpDB->q('category') . 'OR' . $wpDB->quoteName('b.meta_value') . '=' . $wpDB->q('post_tag') . 'OR' . $wpDB->quoteName('b.meta_value') . '=' . $wpDB->q('page') . 'OR' . $wpDB->quoteName('b.meta_value') . '=' . $wpDB->q('custom') . 'OR' . $wpDB->quoteName('b.meta_value') . '=' . $wpDB->q('post')),
            "postsandpage" => $wpDB->getQuery(true)->select('ID')->from($wpDB->quoteName($tableposts, 'a'))->where('a.post_status !="trash" AND a.post_status!="inherit" AND a.post_status!="auto-draft"
            AND (a.post_type = "post" OR a.post_type ="page")')
        ];
        $globalkey = $app->getSession()->get('com_migratetojoomla.tablekeys', []);

        foreach ($tablesmap as $table => $query) {
            if (in_array($table, $importstring)) {

                $wpDB->setQuery($query);
                $result = $wpDB->loadAssocList();

                $tempkeys = [];
                foreach ($result as $key => $value) {
                    $valueArray = array_values($value);
                    array_push($tempkeys, $valueArray[0]);
                }
                $globalkey[$table] = $tempkeys;
            }
        }
        $app->getSession()->set('com_migratetojoomla.tablekeys', $globalkey);
    }

    /**
     * The form event.
     *
     * @param   EventInterface    $event  
     *
     * @return   boolean
     *
     * @since   1.0
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
     * Method to remove unwanted element from importstrings
     *
     * @param   EventInterface    $event  
     *
     * @since   1.0
     */
    public static function createDisplayData(EventInterface $event)
    {
        $importstring = $event->getArgument('data');

        $targetvalues = ["usergroup", "postfeatureimage"];

        foreach ($targetvalues as $value) {
            $key = array_search($value, $importstring);
            // Check if the value exists in the array
            if ($key !== false) {
                // Remove the element with the given key
                unset($importstring[$key]);
            }
        }

        // set data into session
        Factory::getApplication()->getSession()->set('migratetojoomla.displayimportstring', $importstring);
    }

    /** 
     * Method to import user table
     * 
     * @param   EventInterface    $event  
     *
     * @since 1.0
     */
    public function importUser(EventInterface $event)
    {
        $key = $event->getArgument('key');
        $field = $event->getArgument('field');
        $status[] = [];
        $app = Factory::getApplication();
        try {

            if (!\is_resource($this->wpDB)) {
                self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
            }

            $maxKey = $app->getSession()->get('com_migratetojoomla.maxkey', []);

            $wpDB = $this->wpDB;

            $config['dbo'] = $wpDB;

            $query = $wpDB->getQuery(true)
                ->select('*')
                ->from($wpDB->quoteName('#__users'))
                ->where($wpDB->quoteName('ID') . '=' . $key);

            $wpDB->setQuery($query);
            $wpUser = $this->wpDB->loadObject();

            // load user group
            $query = $wpDB->getQuery(true)
                ->select('meta_value')
                ->from($wpDB->quoteName('#__usermeta', 'a'))
                ->where($wpDB->quoteName('a.user_id') . '=' . $wpUser->ID, 'AND')
                ->where($wpDB->quoteName('a.meta_key') . '=' . $this->wpDB->quote('#__capabilities'));
            $this->wpDB->setQuery($query);
            $grouprow = $wpDB->loadResult();

            $user               = new User();
            $user->ID           = $maxKey['users'] + $key;
            $user->name         = $wpUser->display_name;
            $user->username     = $wpUser->user_login;
            $user->email        = $wpUser->user_email;
            $user->registerDate = $wpUser->user_registered;
            $user->activation   = $wpUser->user_activation_key;
            $user->requireReset = 1;
            $user->password     = $wpUser->user_pass;
            $user->params       = new Registry('{"admin_style":"","admin_language":"","language":"","editor":"","timezone":"","a11y_mono":"0","a11y_contrast":"0","a11y_highlight":"0","a11y_font":"0"}');

            $groupId = 1;

            if (preg_match("/administrator/", $grouprow)) {
                $groupId = 7;
            } elseif (preg_match("/author/", $grouprow)) {
                $groupId = 3;
            } elseif (preg_match("/editor/", $grouprow)) {
                $groupId = 4;
            }

            $user->groups = [$groupId];

            $user->save();
            $contentTowrite = 'User Imported Successfully with Wordpress ID = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $status[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('User Imported Unsuccessfully with Wordpress ID = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $status[] = ['status' => "error"];
        }
        $app->getSession()->set('com_migratetojoomla.ajaxresponse', $status);
    }

    /** 
     * Method to import tag table
     * 
     * @param   EventInterface    $event  
     *
     * @since 1.0
     */
    public function importTag(EventInterface $event)
    {
        $key = $event->getArgument('key');
        $field = $event->getArgument('field');
        $status[] = [];
        $app     = Factory::getApplication();
        $user    = $app->getIdentity();
        $date = (string)Factory::getDate();
        try {
            LogHelper::writeLog("Tag start saved", 'error');
            if (!\is_resource($this->wpDB)) {
                self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
            }

            $maxKey = $app->getSession()->get('com_migratetojoomla.maxkey', []);
            $wpDB = $this->wpDB;

            $config['dbo'] = $wpDB;

            // load data from framework table
            $query = $wpDB->getQuery(true)
                ->select('*')
                ->from($wpDB->quoteName('#__term_taxonomy', 'b'))
                ->leftjoin($wpDB->quoteName('#__terms', 'a'), $wpDB->quoteName('a.term_id') . '=' . $wpDB->quoteName('b.term_id'))
                ->where($wpDB->quoteName('b.term_id') . '=' . $key);

            $wpDB->setQuery($query);
            $row = $wpDB->loadObject();

            $tag = new TagTable($this->getDatabase());
            $tag->ID = $row->term_id + $maxKey['tags'];
            $tag->parent_id = 0;
            $tag->lft = 0;
            $tag->rgt = 0;
            $tag->level = 0;
            $tag->path = $row->name;
            $tag->title = $row->name;
            $tag->alias = $row->slug;
            $tag->note = '';
            $tag->description = $row->description;
            $tag->published = 0;
            $tag->checked_out = NULL;
            $tag->checked_out_time = NULL;
            $tag->access = 0;
            $tag->params = '{}';
            $tag->metadesc = '';
            $tag->metakey = '';
            $tag->metadata = '{}';
            $tag->created_user_id = $user->id;
            $tag->created_time = $date;
            $tag->created_by_alias = '';
            $tag->modified_user_id = $user->id;
            $tag->modified_time = $date;
            $tag->images = '{}';
            $tag->urls = '{}';
            $tag->hits = 0;
            $tag->language = '*';
            $tag->version = 1;
            $tag->publish_up = $date;
            $tag->publish_down = NULL;
            $tag->setLocation(1, 'last-child');
            $tag->store();

            $contentTowrite = 'Tag Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $status[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Tag Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $status[] = ['status' => "error"];
        }
        Factory::getSession()->set('com_migratetojoomla.ajaxresponse', $status);
    }

    /** 
     * Method to import category table
     * 
     * @param   EventInterface    $event  
     *
     * @since 1.0
     */
    public function importCategory(EventInterface $event)
    {
        $key = $event->getArgument('key');
        $field = $event->getArgument('field');
        $status[] = [];

        $app = Factory::getApplication();
        $user    = $app->getIdentity();
        $date = (string)Factory::getDate();

        try {

            if (!\is_resource($this->wpDB)) {
                self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
            }

            $maxKey = $app->getSession()->get('com_migratetojoomla.maxkey', []);
            $wpDB = $this->wpDB;

            $config['dbo'] = $wpDB;

            // load data from framework table
            $query = $wpDB->getQuery(true)
                ->select(['a.term_id', 'b.parent', 'a.name', 'a.slug', 'b.description'])
                ->from($wpDB->quoteName("#__term_taxonomy", 'b'))
                ->leftjoin($wpDB->quoteName('#__terms', 'a'), $wpDB->quoteName('a.term_id') . '=' . $wpDB->quoteName('b.term_id'))
                ->where($wpDB->quoteName('b.term_id') . '=' . $key);

            $wpDB->setQuery($query);
            $row = $wpDB->loadObject();
            $totalcategory = count($app->getSession()->get('com_migratetojoomla.tablekeys', [])['category']);

            $patharray = [];
            $levelarray = [];

            // manipulate data to find parentcategory path and level

            if ($row->parent != 0) {
                // It is a child category
                $parrentsarray = [];
                $currentelement = $row;
                $iteration = 0;
                while ($currentelement->parent != 0 && $iteration < $totalcategory) {
                    array_push($parrentsarray, $currentelement->name);
                    $iteration = $iteration; // to avoid infinite loop over

                    // finding parent row and assign it as currentelement for next iteration
                    $query = $wpDB->getQuery(true)
                        ->select(['a.term_id', 'a.name', 'b.parent'])
                        ->from($wpDB->quoteName("#__term_taxonomy", 'b'))
                        ->leftjoin($wpDB->quoteName('#__terms', 'a'), $wpDB->quoteName('a.term_id') . '=' . $wpDB->quoteName('b.term_id'))
                        ->where($wpDB->quoteName('b.term_id') . '=' . $currentelement->parent);

                    $wpDB->setQuery($query);
                    $currentelement = $wpDB->loadObject();
                }

                // pushing currentelement in parent array
                array_push($parrentsarray, $currentelement->name);

                // reverse array element
                $reverseparent = array_reverse($parrentsarray);

                $path = implode('/', $reverseparent);

                array_push($patharray, $path);
                array_push($levelarray, count($parrentsarray));
            } else {
                // It is not a child category
                array_push($patharray, $row->name);
                array_push($levelarray, 1);
            }

            $category = new CategoryTable($this->getDatabase());
            $category->ID = $row->term_id + $maxKey['categories'];
            $category->asset_id = 0;
            $category->parent_id = $row->parent;
            $category->lft = 0;
            $category->rgt = 0;
            $category->level = $levelarray[0];
            $category->path = $patharray[0];
            $category->extension = 'com_content';
            $category->title = $row->name;
            $category->alias = $row->slug;
            $category->note = "";
            $category->description = $row->description;
            $category->published = 0;
            $category->check_out = NULL;
            $category->check_out_time = NULL;
            $category->access = 0;
            $category->params = '{}';
            $category->metadesc = '';
            $category->metakey = '';
            $category->metadata = '{}';
            $category->created_user_id = $user->id;
            $category->created_time = $date;
            $category->modified_user_id = $user->id;
            $category->modified_time = $date;
            $category->hits = 0;
            $category->language = '*';
            $category->version = 1;
            $category->store();

            $contentTowrite = 'Category Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $status[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Category  Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $status[] = ['status' => "error"];
        }
        Factory::getSession()->set('com_migratetojoomla.ajaxresponse', $status);
    }

    /** 
     * Method to import Menu
     * 
     * @param   EventInterface    $event  
     *
     * @since 1.0
     */
    public function importMenu(EventInterface $event)
    {
        $key = $event->getArgument('key');
        $field = $event->getArgument('field');
        $status[] = [];
        $app = Factory::getApplication();
        try {

            if (!\is_resource($this->wpDB)) {
                self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
            }

            $maxKey = $app->getSession()->get('com_migratetojoomla.maxkey', []);
            $wpDB = $this->wpDB;

            $config['dbo'] = $wpDB;

            // load data from framework table
            $query = $wpDB->getQuery(true)
                ->select(['a.term_id', 'a.slug', 'a.name', 'b.description'])
                ->from($wpDB->quoteName('#__term_taxonomy', 'b'))
                ->leftjoin($wpDB->quoteName('#__terms', 'a'), $wpDB->quoteName('a.term_id') . '=' . $wpDB->quoteName('b.term_id'))
                ->where($wpDB->quoteName('b.term_id') . '=' . $key);

            $wpDB->setQuery($query);
            $row = $wpDB->loadObject();

            $menu = new MenuTypeTable($this->getDatabase());
            $menu->ID = $row->term_id + $maxKey['menu_types'];
            $menu->asset_id = 0;
            $menu->menutype = $row->slug;
            $menu->title = $row->name;
            $menu->description = $row->description;
            $menu->client_id = 0;
            $menu->store();

            $contentTowrite = 'Menu Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $status[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Menu Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $status[] = ['status' => "error"];
        }
        Factory::getSession()->set('com_migratetojoomla.ajaxresponse', $status);
    }

    /** 
     * Method to import Menu Items
     * 
     * @param   EventInterface    $event  
     *
     * @since 1.0
     */
    public function importMenuItem(EventInterface $event)
    {
        $key = $event->getArgument('key');
        $field = $event->getArgument('field');
        $status[] = [];
        $app = Factory::getApplication();

        try {

            if (!\is_resource($this->wpDB)) {
                self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
            }

            $maxKey = $app->getSession()->get('com_migratetojoomla.maxkey', []);
            $wpDB = $this->wpDB;

            $config['dbo'] = $wpDB;
            $tableposts = '#__posts';
            $tablepostmeta = '#__postmeta';
            $tabletermtaxonomy = '#__term_taxonomy';
            $tableterms = '#__terms';
            $tabletermrelationship = '#__term_relationships';

            $query = $wpDB->getQuery(true)
                ->select('DISTINCT ID , post_title , post_parent , menu_order , post_date , e.name')
                ->from($wpDB->quoteName($tableposts, 'a'))
                ->leftjoin($wpDB->quoteName($tablepostmeta, 'b'), $wpDB->quoteName('a.ID') . '=' . $wpDB->quoteName('b.post_id'))
                ->leftjoin($wpDB->quoteName($tabletermrelationship, 'c'), $wpDB->quoteName('a.ID') . '=' . $wpDB->quoteName('c.object_id'))
                ->leftjoin($wpDB->quoteName($tabletermtaxonomy, 'd'), $wpDB->quoteName('c.term_taxonomy_id') . '=' . $wpDB->quoteName('d.term_taxonomy_id'))
                ->leftjoin($wpDB->quoteName($tableterms, 'e'), $wpDB->quoteName('d.term_id') . '=' . $wpDB->quoteName('e.term_id'))
                ->where($wpDB->quoteName('ID') . '=' . $key);

            $wpDB->setQuery($query);
            $row = $wpDB->loadObject();

            // load taxonomy id
            $query = $wpDB->getQuery(true)
                ->select('meta_value')
                ->from($wpDB->quoteName($tablepostmeta, 'a'))
                ->where($wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($wpDB->quoteName('a.meta_key') . '=' . $wpDB->q('_menu_item_object_id'));
            $wpDB->setQuery($query);
            $result = $wpDB->loadObject();;

            $taxonomyid = intval($result->meta_value);

            // Is category or tag or page or post or customLink
            $query = $wpDB->getQuery(true)
                ->select($wpDB->quoteName('meta_value'))
                ->from($wpDB->quoteName($tablepostmeta, 'a'))
                ->where($wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($wpDB->quoteName('a.meta_key') . '=' . $wpDB->q('_menu_item_object'));
            $wpDB->setQuery($query);
            $resultload = $wpDB->loadObject();;
            $taxonomytype = $resultload->meta_value;

            // load taxonomy title information
            if ($taxonomytype == "category" || $taxonomytype == "post_tag") {
                LogHelper::writeLog('logfilecategory  ' . $taxonomyid . gettype($taxonomyid));

                $query = $wpDB->getQuery(true)
                    ->select($wpDB->quoteName('name'))
                    ->from($wpDB->quoteName($tableterms, 'a'))
                    ->where($wpDB->quoteName('a.term_id') . '=' . $taxonomyid);
                $wpDB->setQuery($query);
                $taxonomyinfo = $wpDB->loadObject();;
                $menuitemtitle = (empty($row->post_title)) ? $taxonomyinfo->name : $row->post_title;
            } else {
                $query = $wpDB->getQuery(true)
                    ->select($wpDB->quoteName('post_title'))
                    ->from($wpDB->quoteName($tableposts, 'a'))
                    ->where($wpDB->quoteName('a.ID') . '=' . $wpDB->q($taxonomyid));
                $wpDB->setQuery($query);
                $taxonomyinfo = $wpDB->loadObject();;
                $menuitemtitle = (empty($row->post_title)) ? $taxonomyinfo->post_title : $row->post_title;
            }

            // set menu item Link
            switch ($taxonomytype) {
                case "post_tag":
                    $menuitemlink = 'index.php?option=com_tags&view=tag&id[0]={' . $taxonomyid . '}';
                    break;
                case "category":
                    $menuitemlink = 'index.php?option=com_content&view=category&id={' . $taxonomyid . '}';
                    break;
                case "page":
                    $menuitemlink =  'index.php?option=com_content&view=article&id={' . $taxonomyid . '}';
                    break;
                case "post":
                    $menuitemlink =  'index.php?option=com_content&view=article&id={' . $taxonomyid . '}';
                    break;
                case "custom":
                    $query = $wpDB->getQuery(true)
                        ->select($wpDB->quoteName('meta_value'))
                        ->from($wpDB->quoteName($tablepostmeta, 'a'))
                        ->where($wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                        ->where($wpDB->quoteName('a.meta_key') . '=' . $wpDB->q('_menu_item_url'));
                    $wpDB->setQuery($query);
                    $menuitemlink = $wpDB->loadObject()->meta_value;
                    break;
                default:
                    $menuitemlink = " ";
                    break;
            }

            $menuitem = new MenuTable($this->getDatabase());
            $menuitem->ID = $row->ID + $maxKey['menu'];
            $menuitem->menutype = $row->name;
            $menuitem->title = $menuitemtitle;
            $menuitem->alias = strtolower($menuitemtitle);
            $menuitem->note = '';
            $menuitem->path = strtolower($menuitemtitle);
            $menuitem->link = $menuitemlink;
            $menuitem->type = 'component';
            $menuitem->published = 1;
            $menuitem->parent_id = $row->post_parent;
            $menuitem->level = $row->menu_order;
            $menuitem->component_id = 19;
            $menuitem->checked_out = NULL;
            $menuitem->checked_out_time = NULL;
            $menuitem->browserNav = 0;
            $menuitem->access = 0;
            $menuitem->img = '';
            $menuitem->template_style_id = 0;
            $menuitem->params = '{}';
            $menuitem->lft = 0;
            $menuitem->rgt = 0;
            $menuitem->home = 0;
            $menuitem->language = '*';
            $menuitem->client_id = 0;
            $menuitem->publish_up = $row->post_date;
            $menuitem->publish_down = NULL;
            $menuitem->store();

            $contentTowrite = 'MenuItem Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $status[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('MenuItem Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $status[] = ['status' => "error"];
        }
        Factory::getSession()->set('com_migratetojoomla.ajaxresponse', $status);
    }

    /** 
     * Method to import post and pages
     * 
     * @param   EventInterface    $event  
     *
     * @since 1.0
     */
    public function importArticle(EventInterface $event)
    {
        $key = $event->getArgument('key');
        $field = $event->getArgument('field');
        $status[] = [];
        $app = Factory::getApplication();
        $articletype = "";

        try {
            if (!\is_resource($this->wpDB)) {
                self::createWPDB($this, $app->getUserState('com_migratetojoomla.information', []));
            }

            $dataparameter = $app->getUserState('com_migratetojoomla.parameter', []);
            // $imagemigrateway = 1;
            $imagemigrateway = $dataparameter['frameworkparams']['postfeatureimage'];
            // // datetime
            $maxKey = $app->getSession()->get('com_migratetojoomla.maxkey', []);

            $date = (string)Factory::getDate();
            $wpDB = $this->wpDB;
            // current login user
            $user = $app->getIdentity();
            $userid = $user->id;

            // Specify the table name
            $tableposts = '#__posts';
            $tablepostmeta = '#__postmeta';
            $tabletermtaxonomy = '#__term_taxonomy';
            $tableterms = '#__terms';
            $tabletermrelationship = '#__term_relationships';

            $config['dbo'] = $wpDB;

            $query = $wpDB->getQuery(true)
                ->select('*')
                ->from($wpDB->quoteName($tableposts, 'a'))
                ->where($wpDB->quoteName('a.ID') . '=' . $key);

            $wpDB->setQuery($query);
            $row = $wpDB->loadObject();
            $articletype = $row->post_type;

            // getting all categories associate with item
            $query  = $wpDB->getQuery(true)
                ->select('*')
                ->from($wpDB->quoteName($tabletermrelationship, 'a'))
                ->leftjoin($wpDB->quoteName($tabletermtaxonomy, 'b'), $wpDB->quoteName('a.term_taxonomy_id') . '=' . $wpDB->quoteName('b.term_taxonomy_id'))
                ->leftjoin($wpDB->quoteName($tableterms, 'c'), $wpDB->quoteName('b.term_id') . '=' . $wpDB->quoteName('c.term_id'))
                ->where($wpDB->quoteName('a.object_id') . '=' . $key, 'AND')
                ->where($wpDB->quoteName('b.taxonomy') . '=' . $wpDB->q('category'));
            $wpDB->setQuery($query);
            $allcategories =  $wpDB->loadAssocList();

            // getting all tags associate with item
            $query  = $wpDB->getQuery(true)
                ->select('*')
                ->from($wpDB->quoteName($tabletermrelationship, 'a'))
                ->leftjoin($wpDB->quoteName($tabletermtaxonomy, 'b'), $wpDB->quoteName('a.term_taxonomy_id') . '=' . $wpDB->quoteName('b.term_taxonomy_id'))
                ->leftjoin($wpDB->quoteName($tableterms, 'c'), $wpDB->quoteName('b.term_id') . '=' . $wpDB->quoteName('c.term_id'))
                ->where($wpDB->quoteName('a.object_id') . '=' . $key, 'AND')
                ->where($wpDB->quoteName('b.taxonomy') . '=' . $wpDB->q('post_tag'));
            $wpDB->setQuery($query);
            $alltags =  $wpDB->loadAssocList();

            // getting id of featured image
            $query = $wpDB->getQuery(true)
                ->select('meta_value')
                ->from($wpDB->quoteName($tablepostmeta, 'a'))
                ->where($wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($wpDB->quoteName('a.meta_key') . '=' . $wpDB->q('_thumbnail_id'));
            $wpDB->setQuery($query);
            $tempresult =  $wpDB->loadAssocList();

            $imageid = NULL;
            if (count($tempresult) > 0) {
                $imageid = $tempresult[0]['meta_value'];
            }

            // changing media url and images field of article in format of joomla path
            $imageinfo = NULL;
            $imageurl = NULL;
            $articleimage = '{"image_intro":"","image_intro_alt":"","float_intro":"","image_intro_caption":"","image_fulltext":"","image_fulltext_alt":"","float_fulltext":"","image_fulltext_caption":""}';
            if (!is_null($imageid)) {
                $query = $wpDB->getQuery(true)
                    ->select('post_title , post_content, post_excerpt, post_name , guid')
                    ->from($wpDB->quoteName($tableposts, 'a'))
                    ->where($wpDB->quoteName('a.ID') . '=' . $imageid);
                $wpDB->setQuery($query);
                $imageinfo = $wpDB->loadObject();
                $url = $imageinfo['guid'];
                if (!empty($url)) {

                    $position = strpos($url, "uploads");

                    if ($position !== false) {
                        // Remove the characters before the continuous part
                        $result = substr($url, $position + strlen("uploads"));
                        $imageurl = JPATH_ROOT . $result;
                    }
                }

                switch ($imagemigrateway) {
                    case "introonly":
                        $articleimage = '{"image_intro":' . $imageurl . ',"image_intro_alt":' . $imageinfo['post_title'] . ',"float_intro":"","image_intro_caption":' . $imageinfo['post_excerpt'] . '}';
                        break;

                    case "fullonly":
                        $articleimage = '{"image_fulltext":' . $imageurl . ',"image_fulltext_alt":' . $imageinfo['post_title'] . ',"float_fulltext":"","image_fulltext_caption":' . $imageinfo['post_content'] . '}';
                        break;

                    default:
                        $articleimage = '{"image_intro":' . $imageurl . ',"image_intro_alt":' . $imageinfo['post_title'] . ',"float_intro":"","image_intro_caption":' . $imageinfo['post_excerpt'] . ',"image_fulltext":' . $imageurl . ',"image_fulltext_alt":' . $imageinfo['post_title'] . ',"float_fulltext":"","image_fulltext_caption":' . $imageinfo['post_content'] . '}';
                        break;
                }
            }

            $articlecategoryId = 0;

            // default category for article
            $joomladb = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $wpDB->getQuery(true)
                    ->select('id')
                    ->from($wpDB->quoteName("#__categories", 'a'))
                    ->where($wpDB->quoteName('a.extension') . '=' . $joomladb->q("com_content"))
                    ->setLimit(1);
            $joomladb->setQuery($query);
            $articlecategoryId = $joomladb->loadObject()->id;


            if (count($allcategories) == 1) {
                $articlecategoryId = $allcategories[0]['term_id'];
            }

            // state of article
            $articlestate = 1; // for post publish and future
            if ($row->post_status == 'draft' || $row->post_status == 'pending') {
                $articlestate = 0;
            }

            // article import
            $article = new stdClass();
            $article->asset_id = 0;
            $article->title = $row->post_title;
            $article->alias = $row->post_name;
            $article->introtext = empty($row->post_exerpt) ? '' : $row->post_exerpt;
            $article->fulltext = $row->post_content;
            $article->state = $articlestate;
            $article->catid = $articlecategoryId;
            $article->created = $row->post_date;
            $article->created_by = $row->post_author;
            $article->created_by_alias = "";
            $article->modified = $date;
            $article->modified_by = $userid;
            $article->checked_out = NULL;
            $article->checked_out_time = NULL;
            $article->publish_up = $articlestate ? $date : NULL;
            $article->publish_down = NULL;
            $article->images = $articleimage;
            $article->urls = "";
            $article->attribs = "";
            $article->version = 1;
            $article->ordering = 0;
            $article->metakey = NULL;
            $article->metadesc = "";
            $article->access = 0;
            $article->hits = 0;
            $article->metadata = 0;
            $article->featured = 0;
            $article->language = '*';
            $article->note = "";

 
            $content_array = $article;
            $content_array = json_decode(json_encode($article), true);
            $factory       = $app->bootComponent('com_content')->getMVCFactory();
            $content_model = $factory->createModel('Article', 'Administrator', ['ignore_request' => true]);

            // Simple Object to Array conversion.
            $content_model->save($content_array);
            if (!$content_model->save($content_array)) {
                LogHelper::writeLog($articletype . ' Imported Unsuccessfully with id = ' . $key, 'error');
                LogHelper::writeSessionLog("error", $field);
                $status[] = ['status' => "error"];
                LogHelper::writeLog($content_model->getError(), 'normal');
                throw new \Exception($content_model->getError());
            }

            // tag map all item associate tags
            $articleid = $content_model->getState('article.id');

            foreach ($alltags as $tag) {
                $tagmap = new stdClass();
                $tagmap->type_alias = "com_content.article";
                $tagmap->core_content_id = 6;
                $tagmap->content_item_id = $articleid;
                $tagmap->tag_id = $tag['term_id'] + $maxKey['tags'];
                $tagmap->tag_time = $date;
                $tagmap->type_id = 1;

                $jdb = $joomladb->insertObject('#__contentitem_tag_map', $tagmap);
            }

            // more that one category convert into tags
            if (count($allcategories) > 1) {

                foreach ($allcategories as $category) {

                    // one category can associate with multiple item so check whether it's already in tag table before import
                    $tagTable  = Table::getInstance('Tag', 'TagsTable');
                    $th = new TagsHelper();
                    $tagnames = $th->getTagNames(array($category['term_id']));

                    if (!empty($tagnames)) {
                        // skip below process if category always available as tag
                        continue;
                    }

                    // one category can associate with multiple pages and post so to avoid duplicate key error checking whether it already exist or not
                    $query = $joomladb->getQuery(true)
                        ->select('id')
                        ->from($joomladb->quoteName('#__tags'))
                        ->where($joomladb->quoteName('id') . '=' . $category['term_id'] + $maxKey['categories']);
                    $joomladb->setQuery($query);
                    $tempdata =  $joomladb->loadAssocList();

                    if (count($tempdata) == 0) {

                        // $tag = new stdClass();
                        $tag = new TagTable($this->getDatabase());
                        $tag->id = $category['term_id'] + $maxKey['categories'];
                        $tag->parent_id = 0;
                        $tag->lft = 0;
                        $tag->rgt = 0;
                        $tag->level = 0;
                        $tag->path = $category['name'];
                        $tag->title = $category['name'];
                        $tag->alias = $category['slug'];
                        $tag->note = "";
                        $tag->description = $category['description'];
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
                        $tag->setLocation(1, 'last-child');
                        $tag->store();
                    }
                    $tagmap = new stdClass();
                    $tagmap->type_alias = "com_content.article";
                    $tagmap->core_content_id = 6;
                    $tagmap->content_item_id = $articleid;
                    $tagmap->tag_id = $category['term_id'] + $maxKey['categories'];
                    $tagmap->tag_time = $date;
                    $tagmap->type_id = 1;

                    $jdb = $joomladb->insertObject('#__contentitem_tag_map', $tagmap);
                }
            }
            // if item is page then create a menuitem pointing to that article
            if ($articletype == "page") {

                $menuitem =new MenuTable($this->getDatabase());
                $menuitem->menutype = $row->post_name;
                $menuitem->title = $row->post_title;
                $menuitem->alias = strtolower($row->post_title);
                $menuitem->note = '';
                $menuitem->path = strtolower($row->post_title);
                $menuitem->link = 'index.php?option=com_content&view=article&id={' . $articleid . '}';
                $menuitem->type = 'component';
                $menuitem->published = 1;
                $menuitem->parent_id = $row->post_parent;
                $menuitem->level = $row->menu_order;
                $menuitem->component_id = 19;
                $menuitem->checked_out = NULL;
                $menuitem->checked_out_time = NULL;
                $menuitem->browserNav = 0;
                $menuitem->access = 0;
                $menuitem->img = '';
                $menuitem->template_style_id = 0;
                $menuitem->params = '{}';
                $menuitem->lft = 0;
                $menuitem->rgt = 0;
                $menuitem->home = 0;
                $menuitem->language = '*';
                $menuitem->client_id = 0;
                $menuitem->publish_up = $row->post_date;
                $menuitem->publish_down = NULL;
                $menuitem->store();
            }

            $contentTowrite = $articletype . ' Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $status[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog($articletype . ' Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeSessionLog("error", $field);
            $status[] = ['status' => "error"];
            LogHelper::writeLog($th, 'normal');
        }
        Factory::getSession()->set('com_migratetojoomla.ajaxresponse', $status);
    }
}
