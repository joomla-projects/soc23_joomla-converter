<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Migratetojoomla.wordpress
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\Wordpress\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\LogHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Wordpress Plugin  for com_migratetojoomla
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
     * @var  DatabaseInterface  DB object connected to the WP DB
     *
     * @since __DEPLOY_VERSION__
     */
    public $wpDB;


    public $log;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareFormmigrate'        => 'onContentPrepareForm',
            'migratetojoomla_storemaxprimarykey' => 'storeMaxPrimaryKey',
            'migratetojoomla_storeprimarykey'    => 'storePrimaryKey',
            'migratetojoomla_createdisplaydata'  => 'createDisplayData',
            'migratetojoomla_user'               => 'importUser',
            'migratetojoomla_tag'                => 'importTag',
            'migratetojoomla_category'           => 'importCategory',
            'migratetojoomla_menu'               => 'importMenu',
            'migratetojoomla_menuitem'           => 'importMenuItem',
            'migratetojoomla_postsandpage'       => 'importPostsAndPage',
        ];
    }

    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        self::setdatabase($this, $this->getApplication()->getUserState('com_migratetojoomla.information', []));

        $options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
        $options['text_file'] = 'wordpress-to-joomla.php';
        Log::addLogger($options);
    }

    /**
     * Method to store max primary key of Joomla Table
     *
     * @since   1.0
     */
    public static function storeMaxPrimaryKey()
    {
        $app = Factory::getApplication();
        $app->getSession()->clear('migratetojoomla.maxkey');
        $tables = [
            "users",
            "tags",
            "categories",
            "menu_types",
            "menu",
            "content",
        ];
        $maxKey      = [];
        $tablePrefix = $app->get('dbprefix');
        $jdb         = Factory::getDbo();
        foreach ($tables as $table) {
            $tableName = $tablePrefix . $table;
            $query     = $jdb->getQuery(true)
                ->select('MAX(' . $jdb->quoteName('id') . ')')
                ->from($jdb->quoteName($tableName));

            $jdb->setQuery($query);
            $result = $jdb->loadAssocList();

            foreach ($result[0] as $key => $value) {
                $maxKey[$table] = $value + 1;
            }
        }

        // how update session value as if user want again import than max value of key must update to avoid duplicate key
        $app->getSession()->set('migratetojoomla.maxkey', $maxKey);
    }

    /**
     * Method to set database $db if it is not set
     *
     * @param   object  $instance  instance of class
     * @param   array   $data      form data
     * @return  boolean True on success
     *
     * @since 1.0
     */
    public static function setdatabase($instance, $data = [])
    {
        if (\is_resource($instance->db)) {
            return true;
        }

        $options = [
            'driver'   => $data['dbdriver'],
            'host'     => $data['dbhostname'] . ':' . $data['dbport'],
            'user'     => $data['dbusername'],
            'password' => $data['dbpassword'],
            'database' => $data['dbname'],
            'prefix'   => $data['dbtableprefix'],
        ];

        try {
            $db = DatabaseDriver::getInstance($options);
            $db->getVersion();
            $instance->db   = $db;
            $instance->wpDB = $db;
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
        $app = $this->getApplication();

        if (!\is_resource($this->db)) {
            self::setdatabase($this, $app->getUserState('com_migratetojoomla.information', []));
        }
        $app->getSession()->clear('migratetojoomla.tablekeys');
        $data = $app->getUserState('com_migratetojoomla.information', []);
        $db   = $this->db;

        $importstring = $app->getSession()->get('migratetojoomla.arrayimportstring', []);

        // Specify the table name
        $tablePrefix           = rtrim($data['dbtableprefix'], '_');
        $tableusers            = $tablePrefix . '_users';
        $tableposts            = $tablePrefix . '_posts';
        $tablepostmeta         = $tablePrefix . '_postmeta';
        $tabletermtaxonomy     = $tablePrefix . '_term_taxonomy';
        $tableterms            = $tablePrefix . '_terms';
        $tabletermrelationship = $tablePrefix . '_term_relationships';

        $tablesmap = [
            'user'     => $db->getQuery(true)->select($db->quoteName("ID"))->from($db->quoteName($tableusers)),
            'tag'      => $db->getQuery(true)->select($db->quoteName("term_id"))->from($db->quoteName($tabletermtaxonomy))->where($db->quoteName('taxonomy') . '=' . $db->quote('post_tag')),
            "category" => $db->getQuery(true)->select($db->quoteName("term_id"))->from($db->quoteName($tabletermtaxonomy))->where($db->quoteName('taxonomy') . '=' . $db->quote('category')),
            "menu"     => $db->getQuery(true)->select($db->quoteName("term_id"))->from($db->quoteName($tabletermtaxonomy)),
            "menuitem" => $db->getQuery(true)
                ->select('DISTINCT ID')
                ->from($db->quoteName($tableposts, 'a'))
                ->join('LEFT', $db->quoteName($tablepostmeta, 'b'), $db->quoteName('a.ID') . '=' . $db->quoteName('b.post_id'))
                ->join('LEFT', $db->quoteName($tabletermrelationship, 'c'), $db->quoteName('a.ID') . '=' . $db->quoteName('c.object_id'))
                ->join('LEFT', $db->quoteName($tabletermtaxonomy, 'd'), $db->quoteName('c.term_taxonomy_id') . '=' . $db->quoteName('d.term_taxonomy_id'))
                ->join('LEFT', $db->quoteName($tableterms, 'e'), $db->quoteName('d.term_id') . '=' . $db->quoteName('e.term_id'))
                ->where($db->quoteName('a.post_type') . '=' . $db->quote('nav_menu_item') . 'AND' . $db->quoteName('b.meta_value') . '=' . $db->quote('category') . 'OR' . $db->quoteName('b.meta_value') . '=' . $db->quote('post_tag') . 'OR' . $db->quoteName('b.meta_value') . '=' . $db->quote('page') . 'OR' . $db->quoteName('b.meta_value') . '=' . $db->quote('custom') . 'OR' . $db->quoteName('b.meta_value') . '=' . $db->quote('post')),
            "postsandpage" => $db->getQuery(true)->select('ID')->from($db->quoteName($tableposts, 'a'))->where('a.post_status !="trash" AND a.post_status!="inherit" AND a.post_status!="auto-draft"
            AND (a.post_type = "post" OR a.post_type ="page")'),
        ];
        $globalkey   = $app->getSession()->get('migratetojoomla.tablekeys', []);
        $tablePrefix = rtrim($data['dbtableprefix'], '_');

        foreach ($tablesmap as $table => $query) {
            if (@\in_array($table, $importstring)) {
                $db->setQuery($query);
                $result = $db->loadAssocList();

                $tempkeys = [];
                foreach ($result as $key => $value) {
                    $valueArray = array_values($value);
                    array_push($tempkeys, $valueArray[0]);
                }
                $globalkey[$table] = $tempkeys;
            }
        }
        $app->getSession()->set('migratetojoomla.tablekeys', $globalkey);
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
        $form     = $event->getArgument('form');
        $formName = $event->getArgument('formname');

        if ($this->_name !== $event->getArgument('framework')) {
            return true;
        }

        $allowedForms = [
            'com_migratetojoomla.parameter',
        ];

        if (!\in_array($formName, $allowedForms, true)) {
            return true;
        }

        Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

        $form->loadFile('wordpress', false);

        $data = $this->getApplication()->getUserState('com_migratetojoomla.parameter', []);

        if (\array_key_exists('frameworkparams', $data)) {
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
        Factory::getSession()->set('migratetojoomla.displayimportstring', $importstring);
    }


    /**
     * Method to import user table
     *
     * @param   EventInterface    $event
     *
     * @return  void
     *
     * @since 1.0
     */
    public function importUser(EventInterface $event)
    {
        $app    = $this->getApplication();
        $map    = $app->getUserState('com_migratetojoomla.wordpress.user_map', []);
        $data   = $app->getUserState('com_migratetojoomla.information', []);
        $key    = $event->getArgument('key');
        $field  = $event->getArgument('field');
        $update = [];

        try {
            // Specify the table name
            $tableUsers     = rtrim($data['dbtableprefix'], '_') . '_users';
            $tableUsersMeta = rtrim($data['dbtableprefix'], '_') . '_usermeta';

            // load data from framework table
            $query = $this->wpDB->getQuery(true)
                ->select('*')
                ->from($this->wpDB->quoteName($tableUsers))
                ->where($this->wpDB->quoteName('ID') . '=' . $key);

            $this->wpDB->setQuery($query);
            $wpUser = $this->wpDB->loadObject();

            if (!$wpUser) {
                throw new \Exception('User with ID ' . $key . ' not found');
            }

            // load user group
            $query = $this->wpDB->getQuery(true)
                ->select('meta_value')
                ->from($this->wpDB->quoteName($tableUsersMeta, 'a'))
                ->where($this->wpDB->quoteName('a.user_id') . '=' . $key, 'AND')
                ->where($this->wpDB->quoteName('a.meta_key') . '=' . $this->wpDB->quote('wp_capabilities'));
            $this->wpDB->setQuery($query);
            $grouprow = $this->wpDB->loadResult();

            $user               = new User();
            $user->name         = $wpUser->display_name;
            $user->username     = $wpUser->user_login;
            $user->email        = $wpUser->user_email;
            $user->registerDate = $wpUser->user_registered;
            $user->activation   = $wpUser->user_activation_key;
            $user->requireReset = 1;
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
            $map[$wpUser->ID] = $user->id;
            Log::add('User with WP ID ' . $key . ' (Joomla: ' . $user->id . ') and group ' . $groupId . ' successfully imported.');

            LogHelper::writeSessionLog("success", $field);
            $update[] = ['status' => "success"];
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing user with ID ' . $key . 'failed. ' . $e->getMessage(), Log::ERROR);

            LogHelper::writeSessionLog("error", $field);
            $update[] = ['status' => "error"];
        }
        $app->getSession()->set('migratetojoomla.ajaxresponse', $update);
        $app->setUserState('com_migratetojoomla.wordpress.user_map', $map);
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
        $app      = $this->getApplication();
        $jdb      = Factory::getDbo();
        $key      = $event->getArgument('key');
        $field    = $event->getArgument('field');
        $update[] = [];
        // current login user
        $user   = $app->getIdentity();
        $userid = $user->id;
        $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);
        // datetime
        $date = (string)Factory::getDate();
        try {
            if (!\is_resource($this->db)) {
                self::setdatabase($this, $app->getUserState('com_migratetojoomla.information', []));
            }
            $data = $app->getUserState('com_migratetojoomla.information', []);
            $db   = $this->db;

            // Specify the table name
            $tabletermtaxonomy = rtrim($data['dbtableprefix'], '_') . '_term_taxonomy';
            $tableterms        = rtrim($data['dbtableprefix'], '_') . '_terms';

            $config['dbo'] = $db;
            $tablePrefix   = $app->get('dbprefix');

            // load data from framework table
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tabletermtaxonomy, 'b'))
                ->join('LEFT', $db->quoteName($tableterms, 'a'), $db->quoteName('a.term_id') . '=' . $db->quoteName('b.term_id'))
                ->where($db->quoteName('b.term_id') . '=' . $key);

            $db->setQuery($query);
            $results = $db->loadAssocList();
            $row     = $results[0];

            $tag                   = new \stdClass();
            $tag->id               = $row['term_id'] + $maxKey['tags'];
            $tag->parent_id        = 0;
            $tag->lft              = 0;
            $tag->rgt              = 0;
            $tag->level            = 0;
            $tag->path             = $row['name'];
            $tag->title            = $row['name'];
            $tag->alias            = $row['slug'];
            $tag->note             = "";
            $tag->description      = $row['description'];
            $tag->published        = 0;
            $tag->check_out        = null;
            $tag->check_out_time   = null;
            $tag->access           = 0;
            $tag->params           = '{}';
            $tag->metadesc         = '';
            $tag->metakey          = '';
            $tag->metadata         = '{}';
            $tag->created_user_id  = $userid;
            $tag->created_time     = $date;
            $tag->created_by_alias = '';
            $tag->modified_user_id = $userid;
            $tag->modified_time    = $date;
            $tag->images           = '{}';
            $tag->urls             = '{}';
            $tag->hits             = 0;
            $tag->language         = '*';
            $tag->version          = 1;
            $tag->publish_up       = $date;
            $tag->publish_down     = null;

            $jdb->insertObject($tablePrefix . 'tags', $tag);

            $contentTowrite = 'Tag Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $update[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Tag Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $update[] = ['status' => "error"];
        }
        $app->getSession()->set('migratetojoomla.ajaxresponse', $update);
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
        $app      = $this->getApplication();
        $key      = $event->getArgument('key');
        $field    = $event->getArgument('field');
        $update[] = [];
        // current login user
        $user   = $app->getIdentity();
        $userid = $user->id;
        $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);
        // datetime
        $date = (string)Factory::getDate();
        try {
            if (!\is_resource($this->db)) {
                self::setdatabase($this, $app->getUserState('com_migratetojoomla.information', []));
            }
            $data = $app->getUserState('com_migratetojoomla.information', []);
            $db   = $this->db;

            // Specify the table name
            $tabletermtaxonomy = rtrim($data['dbtableprefix'], '_') . '_term_taxonomy';
            $tableterms        = rtrim($data['dbtableprefix'], '_') . '_terms';

            $config['dbo'] = $db;
            $tablePrefix   = $app->get('dbprefix');

            // load data from framework table
            $query = $db->getQuery(true)
                ->select(['a.term_id', 'b.parent', 'a.name', 'a.slug', 'b.description'])
                ->from($db->quoteName($tabletermtaxonomy, 'b'))
                ->join('LEFT', $db->quoteName($tableterms, 'a'), $db->quoteName('a.term_id') . '=' . $db->quoteName('b.term_id'))
                ->where($db->quoteName('b.term_id') . '=' . $key);

            $db->setQuery($query);
            $results       = $db->loadAssocList();
            $totalcategory = \count(@$app->getSession()->get('migratetojoomla.tablekeys', [])['category']);
            $row           = $results[0];

            $patharray  = [];
            $levelarray = [];
            // manipulate data to find parentcategory path and level

            if ($row['parent'] != 0) {
                // It is a child category

                $parrentsarray  = [];
                $currentelement = $row;
                $iteration      = 0;
                while ($currentelement['parent'] != 0 && $iteration < $totalcategory) {
                    array_push($parrentsarray, $currentelement['name']);
                    $iteration = $iteration + 1; // to avoid infinite loop over

                    // finding parent row and assign it as currentelement for next iteration
                    $query = $db->getQuery(true)
                        ->select(['a.term_id', 'a.name', 'b.parent'])
                        ->from($db->quoteName($tabletermtaxonomy, 'b'))
                        ->join('LEFT', $db->quoteName($tableterms, 'a'), $db->quoteName('a.term_id') . '=' . $db->quoteName('b.term_id'))
                        ->where($db->quoteName('b.term_id') . '=' . $currentelement['parent']);

                    $db->setQuery($query);
                    $results        = $db->loadAssocList();
                    $currentelement = $results[0];
                }
                // pushing currentelement in parent array
                array_push($parrentsarray, $currentelement['name']);

                // reverse array element
                $reverseparent = array_reverse($parrentsarray);

                $path = implode('/', $reverseparent);

                array_push($patharray, $path);
                array_push($levelarray, \count($parrentsarray));
            } else {
                // It is not a child category
                array_push($patharray, $row['name']);
                array_push($levelarray, 1);
            }

            $category                   = new \stdClass();
            $category->id               = $row['term_id'] + $maxKey['categories'];
            $category->asset_id         = 0;
            $category->parent_id        = $row['parent'];
            $category->lft              = 0;
            $category->rgt              = 0;
            $category->level            = $levelarray[0];
            $category->path             = $patharray[0];
            $category->extension        = 'com_content';
            $category->title            = $row['name'];
            $category->alias            = $row['slug'];
            $category->note             = "";
            $category->description      = $row['description'];
            $category->published        = 0;
            $category->check_out        = null;
            $category->check_out_time   = null;
            $category->access           = 0;
            $category->params           = '{}';
            $category->metadesc         = '';
            $category->metakey          = '';
            $category->metadata         = '{}';
            $category->created_user_id  = $userid;
            $category->created_time     = $date;
            $category->modified_user_id = $userid;
            $category->modified_time    = $date;
            $category->hits             = 0;
            $category->language         = '*';
            $category->version          = 1;

            $jdb = Factory::getDbo()->insertObject($tablePrefix . 'categories', $category);

            $contentTowrite = 'Category Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $update[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Category  Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $update[] = ['status' => "error"];
        }
        $app->getSession()->set('migratetojoomla.ajaxresponse', $update);
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
        $app      = $this->getApplication();
        $key      = $event->getArgument('key');
        $field    = $event->getArgument('field');
        $update[] = [];
        // current login user
        $user   = $app->getIdentity();
        $userid = $user->id;
        $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);
        // datetime
        $date = (string)Factory::getDate();
        try {
            if (!\is_resource($this->db)) {
                self::setdatabase($this, $app->getUserState('com_migratetojoomla.information', []));
            }
            $data = $app->getUserState('com_migratetojoomla.information', []);
            $db   = $this->db;

            // Specify the table name
            $tabletermtaxonomy = rtrim($data['dbtableprefix'], '_') . '_term_taxonomy';
            $tableterms        = rtrim($data['dbtableprefix'], '_') . '_terms';

            $config['dbo'] = $db;
            $tablePrefix   = $app->get('dbprefix');

            // load data from framework table
            $query = $db->getQuery(true)
                ->select(['a.term_id', 'a.slug', 'a.name', 'b.description'])
                ->from($db->quoteName($tabletermtaxonomy, 'b'))
                ->join('LEFT', $db->quoteName($tableterms, 'a'), $db->quoteName('a.term_id') . '=' . $db->quoteName('b.term_id'))
                ->where($db->quoteName('b.term_id') . '=' . $key);

            $db->setQuery($query);
            $results = $db->loadAssocList();
            $row     = $results[0];

            $menu              = new \stdClass();
            $menu->id          = $row['term_id'] + $maxKey['menu'];
            $menu->asset_id    = 0;
            $menu->menutype    = $row['slug'];
            $menu->title       = $row['name'];
            $menu->description = $row['description'];
            $menu->client_id   = 0;

            $jdb = Factory::getDbo()->insertObject($tablePrefix . 'menu_types', $menu);

            $contentTowrite = 'Menu Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $update[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('Menu Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $update[] = ['status' => "error"];
        }
        $app->getSession()->set('migratetojoomla.ajaxresponse', $update);
    }

    /**
     * Method to import Menu Items
     *
     *
     * @param   EventInterface    $event
     *
     * @since 1.0
     */
    public function importMenuItem(EventInterface $event)
    {
        $app      = $this->getApplication();
        $key      = $event->getArgument('key');
        $field    = $event->getArgument('field');
        $update[] = [];
        try {
            if (!\is_resource($this->db)) {
                self::setdatabase($this, $app->getUserState('com_migratetojoomla.information', []));
            }
            $data   = $app->getUserState('com_migratetojoomla.information', []);
            $db     = $this->db;
            $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);

            $databaseprefix = rtrim($data['dbtableprefix'], '_');
            // Specify the table name
            $tableposts            = $databaseprefix . '_posts';
            $tablepostmeta         = $databaseprefix . '_postmeta';
            $tabletermtaxonomy     = $databaseprefix . '_term_taxonomy';
            $tableterms            = $databaseprefix . '_terms';
            $tabletermrelationship = $databaseprefix . '_term_relationships';

            $config['dbo'] = $db;
            $tablePrefix   = $app->get('dbprefix');

            $query = $db->getQuery(true)
                ->select('DISTINCT ID , post_title , post_parent , menu_order , post_date , e.name')
                ->from($db->quoteName($tableposts, 'a'))
                ->join('LEFT', $db->quoteName($tablepostmeta, 'b'), $db->quoteName('a.ID') . '=' . $db->quoteName('b.post_id'))
                ->join('LEFT', $db->quoteName($tabletermrelationship, 'c'), $db->quoteName('a.ID') . '=' . $db->quoteName('c.object_id'))
                ->join('LEFT', $db->quoteName($tabletermtaxonomy, 'd'), $db->quoteName('c.term_taxonomy_id') . '=' . $db->quoteName('d.term_taxonomy_id'))
                ->join('LEFT', $db->quoteName($tableterms, 'e'), $db->quoteName('d.term_id') . '=' . $db->quoteName('e.term_id'))
                ->where($db->quoteName('ID') . '=' . $key);

            $db->setQuery($query);
            $results = $db->loadAssocList();
            $row     = $results[0];


            // load taxonomy id
            $query = $db->getQuery(true)
                ->select('meta_value')
                ->from($db->quoteName($tablepostmeta, 'a'))
                ->where($db->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($db->quoteName('a.meta_key') . '=' . $db->quote('_menu_item_object_id'));
            $db->setQuery($query);
            $result = $db->loadAssocList();

            $taxonomyid = \intval($result[0]['meta_value']);

            // Is category or tag or page or post or customLink
            $query = $db->getQuery(true)
                ->select($db->quoteName('meta_value'))
                ->from($db->quoteName($tablepostmeta, 'a'))
                ->where($db->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($db->quoteName('a.meta_key') . '=' . $db->quote('_menu_item_object'));
            $db->setQuery($query);
            $resultload   = $db->loadAssocList();
            $taxonomytype = $resultload[0]['meta_value'];

            // load taxonomy title information

            if ($taxonomytype == "category" || $taxonomytype == "post_tag") {
                LogHelper::writeLog('logfilecategory  ' . $taxonomyid . \gettype($taxonomyid));

                $query = $db->getQuery(true)
                    ->select($db->quoteName('name'))
                    ->from($db->quoteName($tableterms, 'a'))
                    ->where($db->quoteName('a.term_id') . '=' . $taxonomyid);
                $db->setQuery($query);
                $taxonomyinfo  = $db->loadAssocList();
                $menuitemtitle = (empty($row['post_title'])) ? $taxonomyinfo[0]['name'] : $row['post_title'];
            } else {
                $query = $db->getQuery(true)
                    ->select($db->quoteName('post_title'))
                    ->from($db->quoteName($tableposts, 'a'))
                    ->where($db->quoteName('a.ID') . '=' . $db->quote($taxonomyid));
                $db->setQuery($query);
                $taxonomyinfo  = $db->loadAssocList();
                $menuitemtitle = (empty($row['post_title'])) ? $taxonomyinfo['post_title'] : $row['post_title'];
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
                    $query = $db->getQuery(true)
                        ->select($db->quoteName('meta_value'))
                        ->from($db->quoteName($tablepostmeta, 'a'))
                        ->where($db->quoteName('a.post_id') . '=' . $key, 'AND')
                        ->where($db->quoteName('a.meta_key') . '=' . $db->quote('_menu_item_url'));
                    $db->setQuery($query);
                    $menuitemlink = ($db->loadAssocList())[0]['meta_value'];
                    break;
                default:
                    $menuitemlink = " ";
                    break;
            }

            $menuitem                    = new \stdClass();
            $menuitem->id                = $row['ID'] + $maxKey['menu'];
            $menuitem->menutype          = $row['name'];
            $menuitem->title             = $menuitemtitle;
            $menuitem->alias             = strtolower($menuitemtitle);
            $menuitem->note              = '';
            $menuitem->path              = strtolower($menuitemtitle);
            $menuitem->link              = $menuitemlink;
            $menuitem->type              = 'component';
            $menuitem->published         = 1;
            $menuitem->parent_id         = $row['post_parent'];
            $menuitem->level             = $row['menu_order'];
            $menuitem->component_id      = 19;
            $menuitem->checked_out       = null;
            $menuitem->checked_out_time  = null;
            $menuitem->browserNav        = 0;
            $menuitem->access            = 0;
            $menuitem->img               = '';
            $menuitem->template_style_id = 0;
            $menuitem->params            = '{}';
            $menuitem->lft               = 0;
            $menuitem->rgt               = 0;
            $menuitem->home              = 0;
            $menuitem->language          = '*';
            $menuitem->client_id         = 0;
            $menuitem->publish_up        = $row['post_date'];
            $menuitem->publish_down      = null;

            $jdb = Factory::getDbo()->insertObject($tablePrefix . 'menu', $menuitem);

            $contentTowrite = 'MenuItem Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $update[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog('MenuItem Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeLog($th, 'normal');
            LogHelper::writeSessionLog("error", $field);
            $update[] = ['status' => "error"];
        }
        $app->getSession()->set('migratetojoomla.ajaxresponse', $update);
    }

    /**
     * Method to import post and pages
     *
     *
     * @param   EventInterface    $event
     *
     * @since 1.0
     */
    public function importPostsAndPage(EventInterface $event)
    {
        $app         = $this->getApplication();
        $key         = $event->getArgument('key');
        $field       = $event->getArgument('field');
        $joomladb    = Factory::getDbo();
        $update[]    = [];
        $articletype = "";
        try {
            if (!\is_resource($this->db)) {
                self::setdatabase($this, $app->getUserState('com_migratetojoomla.information', []));
            }
            $data          = $app->getUserState('com_migratetojoomla.information', []);
            $dataparameter = $app->getUserState('com_migratetojoomla.parameter', []);
            // $imagemigrateway = 1;
            $imagemigrateway = @$dataparameter['postfeatureimage'];
            // datetime
            $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);

            $date = (string)Factory::getDate();
            $db   = $this->db;
            // current login user
            $user   = $app->getIdentity();
            $userid = $user->id;

            $databaseprefix = rtrim($data['dbtableprefix'], '_');
            // Specify the table name
            $tableposts            = $databaseprefix . '_posts';
            $tablepostmeta         = $databaseprefix . '_postmeta';
            $tabletermtaxonomy     = $databaseprefix . '_term_taxonomy';
            $tableterms            = $databaseprefix . '_terms';
            $tabletermrelationship = $databaseprefix . '_term_relationships';

            $config['dbo'] = $db;
            $tablePrefix   = $app->get('dbprefix');

            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tableposts, 'a'))
                ->where($db->quoteName('a.ID') . '=' . $key);

            $db->setQuery($query);
            $results = $db->loadAssocList();
            $row     = $results[0];
            // $totalcount = count($results);
            // foreach ($results as $row) {

            $articleid   = $key + $maxKey['content'];
            $articletype = $row['post_type'];

            // getting all categories associate with item
            $query  = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tabletermrelationship, 'a'))
                ->join('LEFT', $db->quoteName($tabletermtaxonomy, 'b'), $db->quoteName('a.term_taxonomy_id') . '=' . $db->quoteName('b.term_taxonomy_id'))
                ->join('LEFT', $db->quoteName($tableterms, 'c'), $db->quoteName('b.term_id') . '=' . $db->quoteName('c.term_id'))
                ->where($db->quoteName('a.object_id') . '=' . $key, 'AND')
                ->where($db->quoteName('b.taxonomy') . '=' . $db->quote('category'));
            $db->setQuery($query);
            $allcategories =  $db->loadAssocList();

            // getting all tags associate with item
            $query  = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName($tabletermrelationship, 'a'))
                ->join('LEFT', $db->quoteName($tabletermtaxonomy, 'b'), $db->quoteName('a.term_taxonomy_id') . '=' . $db->quoteName('b.term_taxonomy_id'))
                ->join('LEFT', $db->quoteName($tableterms, 'c'), $db->quoteName('b.term_id') . '=' . $db->quoteName('c.term_id'))
                ->where($db->quoteName('a.object_id') . '=' . $key, 'AND')
                ->where($db->quoteName('b.taxonomy') . '=' . $db->quote('post_tag'));
            $db->setQuery($query);
            $alltags =  $db->loadAssocList();

            // getting id of featured image
            $query = $db->getQuery(true)
                ->select('meta_value')
                ->from($db->quoteName($tablepostmeta, 'a'))
                ->where($db->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($db->quoteName('a.meta_key') . '=' . $db->quote('_thumbnail_id'));
            $db->setQuery($query);
            $tempresult =  $db->loadAssocList();

            $imageid = null;
            if (\count($tempresult) > 0) {
                $imageid = $tempresult[0]['meta_value'];
            }

            // changing media url and images field of article in format of joomla path
            $imageinfo    = null;
            $imageurl     = null;
            $articleimage = '{"image_intro":"","image_intro_alt":"","float_intro":"","image_intro_caption":"","image_fulltext":"","image_fulltext_alt":"","float_fulltext":"","image_fulltext_caption":""}';
            if (!\is_null($imageid)) {
                $query = $db->getQuery(true)
                    ->select('post_title , post_content, post_excerpt, post_name , guid')
                    ->from($db->quoteName($tableposts, 'a'))
                    ->where($db->quoteName('a.ID') . '=' . $imageid);
                $db->setQuery($query);
                $imageinfo =  $db->loadAssocList();
                $imageinfo = $imageinfo[0];
                $url       = $imageinfo['guid'];
                if (!empty($url)) {
                    $position = strpos($url, "uploads");

                    if ($position !== false) {
                        // Remove the characters before the continuous part
                        $result   = substr($url, $position + \strlen("uploads"));
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
                        LogHelper::writeLog($imageinfo['post_title'], "success");
                        $articleimage = '{"image_intro":' . $imageurl . ',"image_intro_alt":' . $imageinfo['post_title'] . ',"float_intro":"","image_intro_caption":' . $imageinfo['post_excerpt'] . ',"image_fulltext":' . $imageurl . ',"image_fulltext_alt":' . $imageinfo['post_title'] . ',"float_fulltext":"","image_fulltext_caption":' . $imageinfo['post_content'] . '}';
                        break;
                }
            }

            $articlecategoryId = 0;

            if (\count($allcategories) == 1) {
                $articlecategoryId = $allcategories[0]['term_id'];
            }

            // state of article
            $articlestate = 1; // for post publish and future
            if ($row['post_status'] == 'draft' || $row['post_status'] == 'pending') {
                $articlestate = 0;
            }

            // article import
            $article                   = new \stdClass();
            $article->id               = $articleid;
            $article->asset_id         = 0;
            $article->title            = $row['post_title'];
            $article->alias            = $row['post_name'];
            $article->introtext        = empty($row['post_exerpt']) ? '' : $row['post_exerpt'];
            $article->fulltext         = $row['post_content'];
            $article->state            = $articlestate;
            $article->catid            = $articlecategoryId;
            $article->created          = $row['post_date'];
            $article->created_by       = $row['post_author'];
            $article->created_by_alias = "";
            $article->modified         = $date;
            $article->modified_by      = $userid;
            $article->checked_out      = null;
            $article->checked_out_time = null;
            $article->publish_up       = $articlestate ? $date : null;
            $article->publish_down     = null;
            $article->images           = $articleimage;
            $article->urls             = "";
            $article->attribs          = "";
            $article->version          = 1;
            $article->ordering         = 0;
            $article->metakey          = null;
            $article->metadesc         = "";
            $article->access           = 0;
            $article->hits             = 0;
            $article->metadata         = 0;
            $article->featured         = 0;
            $article->language         = '*';
            $article->note             = "";

            $joomladb->insertObject('#__content', $article);
            // tag map all item associate tags
            foreach ($alltags as $tag) {
                $tagmap                  = new \stdClass();
                $tagmap->type_alias      = "com_content.article";
                $tagmap->core_content_id = 6;
                $tagmap->content_item_id = $articleid;
                $tagmap->tag_id          = $tag['term_id'] + $maxKey['tags'];
                $tagmap->tag_time        = $date;
                $tagmap->type_id         = 1;

                $joomladb->insertObject('#__contentitem_tag_map', $tagmap);
            }

            // more that one category convert into tags
            if (\count($allcategories) > 1) {
                foreach ($allcategories as $category) {
                    // one category can associate with multiple item so check whether it's already in tag table before import
                    $th        = new TagsHelper();
                    $tagnames  = $th->getTagNames([$category['term_id']]);

                    if (!empty($tagnames)) {
                        // skip below process if category always available as tag
                        continue;
                    }

                    // one category can associate with multiple pages and post so to avoid duplicate key error checking whether it already exist or not
                    $query    = $joomladb->getQuery(true)
                        ->select('id')
                        ->from($joomladb->quoteName($tablePrefix . 'tags'))
                        ->where($joomladb->quoteName('id') . '=' . $category['term_id'] + $maxKey['categories']);
                    $joomladb->setQuery($query);
                    $tempdata =  $joomladb->loadAssocList();

                    if (\count($tempdata) == 0) {
                        $tag                   = new \stdClass();
                        $tag->id               = $category['term_id'] + $maxKey['categories']; // will change in future to avoid duplicate key error (when id map implemented)
                        $tag->parent_id        = 0;
                        $tag->lft              = 0;
                        $tag->rgt              = 0;
                        $tag->level            = 0;
                        $tag->path             = $category['name'];
                        $tag->title            = $category['name'];
                        $tag->alias            = $category['slug'];
                        $tag->note             = "";
                        $tag->description      = $category['description'];
                        $tag->published        = 0;
                        $tag->check_out        = null;
                        $tag->check_out_time   = null;
                        $tag->access           = 0;
                        $tag->params           = '{}';
                        $tag->metadesc         = '';
                        $tag->metakey          = '';
                        $tag->metadata         = '{}';
                        $tag->created_user_id  = $userid;
                        $tag->created_time     = $date;
                        $tag->created_by_alias = '';
                        $tag->modified_user_id = $userid;
                        $tag->modified_time    = $date;
                        $tag->images           = '{}';
                        $tag->urls             = '{}';
                        $tag->hits             = 0;
                        $tag->language         = '*';
                        $tag->version          = 1;
                        $tag->publish_up       = $date;
                        $tag->publish_down     = null;


                        $joomladb->insertObject('#__tags', $tag);
                    }
                    $tagmap                  = new \stdClass();
                    $tagmap->type_alias      = "com_content.article";
                    $tagmap->core_content_id = 6;
                    $tagmap->content_item_id = $articleid;
                    $tagmap->tag_id          = $category['term_id'] + $maxKey['categories'];
                    $tagmap->tag_time        = $date;
                    $tagmap->type_id         = 1;

                    $jdb = $joomladb->insertObject('#__contentitem_tag_map', $tagmap);
                }
            }
            // if item is page then create a menuitem pointing to that article
            if ($articletype == "page") {
                $menuitem           = new \stdClass();
                $menuitem->id       = $key + $maxKey['menu'];
                $menuitem->menutype = $row['post_name'];
                $menuitem->title    = $row['post_title'];
                $menuitem->alias    = strtolower($row['post_title']);
                $menuitem->note     = '';
                $menuitem->path     = strtolower($row['post_title']);
                $menuitem->link     = 'index.php?option=com_content&view=article&id={' . $articleid . '}';
                ;
                $menuitem->type              = 'component';
                $menuitem->published         = 1;
                $menuitem->parent_id         = $row['post_parent'];
                $menuitem->level             = $row['menu_order'];
                $menuitem->component_id      = 19;
                $menuitem->checked_out       = null;
                $menuitem->checked_out_time  = null;
                $menuitem->browserNav        = 0;
                $menuitem->access            = 0;
                $menuitem->img               = '';
                $menuitem->template_style_id = 0;
                $menuitem->params            = '{}';
                $menuitem->lft               = 0;
                $menuitem->rgt               = 0;
                $menuitem->home              = 0;
                $menuitem->language          = '*';
                $menuitem->client_id         = 0;
                $menuitem->publish_up        = $row['post_date'];
                $menuitem->publish_down      = null;

                $joomladb->insertObject('#__menu', $menuitem);
            }

            $contentTowrite = $articletype . ' Imported Successfully with id = ' . $key;
            LogHelper::writeLog($contentTowrite, 'success');
            LogHelper::writeSessionLog("success", $field);
            $update[] = ['status' => "success"];
        } catch (\RuntimeException $th) {
            LogHelper::writeLog($articletype . ' Imported Unsuccessfully with id = ' . $key, 'error');
            LogHelper::writeSessionLog("error", $field);
            $update[] = ['status' => "error"];
            LogHelper::writeLog($th, 'normal');
        }
        $app->getSession()->set('migratetojoomla.ajaxresponse', $update);
    }
}
