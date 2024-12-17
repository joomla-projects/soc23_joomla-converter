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
use Joomla\CMS\Table\MenuType;
use Joomla\CMS\User\User;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Component\Menus\Administrator\Table\MenuTable;
use Joomla\Component\MigrateToJoomla\Administrator\Event\MigrationStatusEvent;
use Joomla\Component\Tags\Administrator\Table\TagTable;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
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

final class Wordpress extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

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

        $app = Factory::getApplication();
        $this->createWPDB($app->getUserState('com_migratetojoomla.information', []));
    }

    /**
     * Method to store max primary key of Joomla Table
     *
     * @since   1.0
     */
    public function storeMaxPrimaryKey()
    {
        $app = Factory::getApplication();
        $this->getApplication()->getSession()->clear('migratetojoomla.maxkey');
        $tables = [
            "users",
            "tags",
            "categories",
            "menu_types",
            "menu",
            "content",
        ];
        $maxKey = [];
        $db     = $this->getDatabase();
        foreach ($tables as $table) {
            $tableName = '#__' . $table;
            $query     = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('id') . ')')
                ->from($db->quoteName($tableName));

            $db->setQuery($query);
            $maxKey[$table] = $db->loadResult() + 1;
        }

        // how update session value as if user want again import than max value of key must update to avoid duplicate key
        $app->getSession()->set('migratetojoomla.maxkey', $maxKey);
    }

    /**
     * Method to set database $wpDB if it is not set
     *
     * @param   array   $data      form data
     * @return  boolean True on success
     *
     * @since 1.0
     */
    protected function createWPDB($data = [])
    {
        if (count($data) < 7) {
            return;
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
            $this->wpDB = DatabaseDriver::getInstance($options);
            $this->wpDB->getVersion();
            return true;
        } catch (\RuntimeException $e) {
            Log::add(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY') . $e->getMessage(), Log::ERROR);
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

        $app->getSession()->clear('migratetojoomla.tablekeys');

        $importstring = $app->getSession()->get('migratetojoomla.arrayimportstring', []);

        $tablesmap = [
            'user'     => $this->wpDB->getQuery(true)->select($this->wpDB->quoteName("ID"))->from($this->wpDB->quoteName('#__users')),
            'tag'      => $this->wpDB->getQuery(true)->select($this->wpDB->quoteName("term_id"))->from($this->wpDB->quoteName('#__term_taxonomy'))->where($this->wpDB->quoteName('taxonomy') . '=' . $this->wpDB->quote('post_tag')),
            "category" => $this->wpDB->getQuery(true)->select($this->wpDB->quoteName("term_id"))->from($this->wpDB->quoteName('#__term_taxonomy'))->where($this->wpDB->quoteName('taxonomy') . '=' . $this->wpDB->quote('category')),
            "menu"     => $this->wpDB->getQuery(true)->select($this->wpDB->quoteName("term_id"))->from($this->wpDB->quoteName('#__term_taxonomy')),
            "menuitem" => $this->wpDB->getQuery(true)
                ->select('DISTINCT ID')
                ->from($this->wpDB->quoteName('#__posts', 'a'))
                ->leftJoin($this->wpDB->quoteName('#__postmeta', 'b'), $this->wpDB->quoteName('a.ID') . '=' . $this->wpDB->quoteName('b.post_id'))
                ->leftJoin($this->wpDB->quoteName('#__term_relationships', 'c'), $this->wpDB->quoteName('a.ID') . '=' . $this->wpDB->quoteName('c.object_id'))
                ->leftJoin($this->wpDB->quoteName('#__term_taxonomy', 'd'), $this->wpDB->quoteName('c.term_taxonomy_id') . '=' . $this->wpDB->quoteName('d.term_taxonomy_id'))
                ->leftJoin($this->wpDB->quoteName('#__terms', 'e'), $this->wpDB->quoteName('d.term_id') . '=' . $this->wpDB->quoteName('e.term_id'))
                ->where($this->wpDB->quoteName('a.post_type') . '=' . $this->wpDB->quote('nav_menu_item') . 'AND' . $this->wpDB->quoteName('b.meta_value') . '=' . $this->wpDB->quote('category') . 'OR' . $this->wpDB->quoteName('b.meta_value') . '=' . $this->wpDB->quote('post_tag') . 'OR' . $this->wpDB->quoteName('b.meta_value') . '=' . $this->wpDB->quote('page') . 'OR' . $this->wpDB->quoteName('b.meta_value') . '=' . $this->wpDB->quote('custom') . 'OR' . $this->wpDB->quoteName('b.meta_value') . '=' . $this->wpDB->quote('post')),
            "postsandpage" => $this->wpDB->getQuery(true)->select('ID')->from($this->wpDB->quoteName('#__posts', 'a'))->where('a.post_status !="trash" AND a.post_status!="inherit" AND a.post_status!="auto-draft"
            AND (a.post_type = "post" OR a.post_type ="page")'),
        ];
        $globalkey   = $app->getSession()->get('migratetojoomla.tablekeys', []);

        foreach ($tablesmap as $table => $query) {
            if (@\in_array($table, $importstring)) {
                $this->wpDB->setQuery($query);
                $result = $this->wpDB->loadAssocList();

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
     * @param   MigrationStatusEvent    $event
     *
     * @return  void
     *
     * @since 1.0
     */
    public function importUser(MigrationStatusEvent $event)
    {
        $app     = $this->getApplication();
        $map     = $app->getUserState('com_migratetojoomla.wordpress.user_map', []);
        $lastKey = $event->getLastID();

        try {
            // load data from framework table
            $query = $this->wpDB->getQuery(true)
                ->select('*')
                ->from($this->wpDB->quoteName('#__users'))
                ->where($this->wpDB->quoteName('ID') . ' > ' . $lastKey)
                ->order($this->wpDB->quoteName('ID'));

            $this->wpDB->setQuery($query);
            $wpUser = $this->wpDB->loadObject();

            if (!$wpUser) {
                $event->setStatus(-1);
                return;
            }

            // load user group
            $query = $this->wpDB->getQuery(true)
                ->select('meta_value')
                ->from($this->wpDB->quoteName('#__usermeta', 'a'))
                ->where($this->wpDB->quoteName('a.user_id') . '=' . $wpUser->ID, 'AND')
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

            if (!$user->save()) {
                $event->setStatus(0);
                $event->setError($user->getError());
                $event->setLastID($wpUser->ID);
                Log::add('Error: Importing user with ID ' . $wpUser->ID . ' failed. ' . $user->getError(), Log::ERROR);
                return;
            }

            $map[$wpUser->ID] = $user->id;
            $event->setStatus(1);
            $event->setLastID($wpUser->ID);
            Log::add('User with WP ID ' . $wpUser->ID . ' (Joomla: ' . $user->id . ') and group ' . $groupId . ' successfully imported.');
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing user with ID ' . $wpUser->ID . ' failed. ' . $e->getMessage(), Log::ERROR);
            $event->setStatus(0);
            $event->setError($e->getMessage());
        }

        $app->setUserState('com_migratetojoomla.wordpress.user_map', $map);
    }

    /**
     * Method to import tag table
     *
     * @param   MigrationStatusEvent    $event
     *
     * @since 1.0
     */
    public function importTag(MigrationStatusEvent $event)
    {
        $app     = $this->getApplication();
        $map     = $app->getUserState('com_migratetojoomla.wordpress.tag_map', []);
        $lastKey = $event->getLastID();
        $user    = $app->getIdentity();
        $date    = (string) Factory::getDate();

        try {
            // load data from framework table
            $query = $this->wpDB->getQuery(true)
                ->select('*')
                ->from($this->wpDB->quoteName('#__term_taxonomy', 'b'))
                ->leftjoin($this->wpDB->quoteName('#__terms', 'a'), $this->wpDB->quoteName('a.term_id') . '=' . $this->wpDB->quoteName('b.term_id'))
                ->where($this->wpDB->quoteName('b.term_id') . ' > ' . $lastKey)
                ->order($this->wpDB->quoteName('b.term_id'));

            $this->wpDB->setQuery($query);
            $row = $this->wpDB->loadObject();

            if (!$row) {
                $event->setStatus(-1);
                return;
            }

            $tag                   = new TagTable($this->getDatabase());
            $tag->path             = $row->name;
            $tag->title            = $row->name;
            $tag->alias            = $row->slug;
            $tag->note             = "";
            $tag->description      = $row->description;
            $tag->published        = 0;
            $tag->check_out        = null;
            $tag->check_out_time   = null;
            $tag->access           = 0;
            $tag->params           = '{}';
            $tag->metadesc         = '';
            $tag->metakey          = '';
            $tag->metadata         = '{}';
            $tag->created_user_id  = $user->id;
            $tag->created_time     = $date;
            $tag->created_by_alias = '';
            $tag->modified_user_id = $user->id;
            $tag->modified_time    = $date;
            $tag->images           = '{}';
            $tag->urls             = '{}';
            $tag->hits             = 0;
            $tag->language         = '*';
            $tag->version          = 1;
            $tag->publish_up       = $date;
            $tag->publish_down     = null;
            $tag->setLocation(1, 'last-child');
            $tag->store();

            $map[$row->term_id] = $tag->id;
            $event->setStatus(1);
            $event->setLastID($row->term_id);
            Log::add('Tag with WP ID ' . $row->term_id . ' (Joomla: ' . $tag->id . ') successfully imported.');
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing tag with ID ' . $row->term_id . ' failed. ' . $e->getMessage(), Log::ERROR);
            $event->setStatus(0);
            $event->setError($e->getMessage());
        }

        $app->setUserState('com_migratetojoomla.wordpress.tag_map', $map);
    }

    /**
     * Method to import category table
     *
     * @param   MigrationStatusEvent    $event
     *
     * @since 1.0
     */
    public function importCategory(MigrationStatusEvent $event)
    {
        $app      = $this->getApplication();
        $key      = $event->getArgument('key');
        $field    = $event->getArgument('field');
        $map      = $app->getUserState('com_migratetojoomla.wordpress.category_map', []);
        $update[] = [];
        // current login user
        $user   = $app->getIdentity();
        $userid = $user->id;
        // datetime
        $date = (string)Factory::getDate();
        try {
            // load data from framework table
            $query = $this->wpDB->getQuery(true)
                ->select(['a.term_id', 'b.parent', 'a.name', 'a.slug', 'b.description'])
                ->from($this->wpDB->quoteName('#__term_taxonomy', 'b'))
                ->leftJoin($this->wpDB->quoteName('#__terms', 'a'), $this->wpDB->quoteName('a.term_id') . '=' . $this->wpDB->quoteName('b.term_id'))
                ->where($this->wpDB->quoteName('b.term_id') . '=' . $key);

            $this->wpDB->setQuery($query);
            $row       = $this->wpDB->loadObject();
            $totalcategory = \count(@$app->getSession()->get('migratetojoomla.tablekeys', [])['category']);

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
                    $query = $this->wpDB->getQuery(true)
                        ->select(['a.term_id', 'a.name', 'b.parent'])
                        ->from($this->wpDB->quoteName('#__term_taxonomy', 'b'))
                        ->leftJoin($this->wpDB->quoteName('#__terms', 'a'), $this->wpDB->quoteName('a.term_id') . '=' . $this->wpDB->quoteName('b.term_id'))
                        ->where($this->wpDB->quoteName('b.term_id') . '=' . $currentelement['parent']);

                    $this->wpDB->setQuery($query);
                    $currentelement = $this->wpDB->loadAssoc();
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

            $category                   = new CategoryTable($this->getDatabase());
            $category->extension        = 'com_content';
            $category->title            = $row->name;
            $category->alias            = $row->slug;
            $category->note             = "";
            $category->description      = $row->description;
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
            $category->setLocation($map[$row->parent], 'last-child');
            $category->store();

            $map[$key] = $category->id;
            $event->setStatus(1);
            $event->setLastID($row->term_id);
            Log::add('Category with WP ID ' . $row->term_id . ' (Joomla: ' . $category->id . ') successfully imported.');
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing Category with ID ' . $key . ' failed. ' . $e->getMessage(), Log::ERROR);
            $event->setStatus(0);
            $event->setError($e->getMessage());
        }
        $app->setUserState('com_migratetojoomla.wordpress.category_map', $map);
    }

    /**
     * Method to import Menu
     *
     * @param   MigrationStatusEvent    $event
     *
     * @since 1.0
     */
    public function importMenu(MigrationStatusEvent $event)
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
            // load data from framework table
            $query = $this->wpDB->getQuery(true)
                ->select(['a.term_id', 'a.slug', 'a.name', 'b.description'])
                ->from($this->wpDB->quoteName('#__term_taxonomy', 'b'))
                ->leftJoin($this->wpDB->quoteName('#__terms', 'a'), $this->wpDB->quoteName('a.term_id') . '=' . $this->wpDB->quoteName('b.term_id'))
                ->where($this->wpDB->quoteName('b.term_id') . '=' . $key);

            $this->wpDB->setQuery($query);
            $row = $this->wpDB->loadObject();

            $menu              = new MenuType($this->getDatabase());
            $menu->menutype    = $row->slug;
            $menu->title       = $row->name;
            $menu->description = $row->description;
            $menu->client_id   = 0;
            $menu->setLocation($map[$row->parent], 'last-child');
            $menu->store();

            $map[$key] = $menu->id;
            $event->setStatus(1);
            $event->setLastID($row->term_id);
            Log::add('Menu with WP ID ' . $row->term_id . ' (Joomla: ' . $menu->id . ') successfully imported.');
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing Menu with ID ' . $key . ' failed. ' . $e->getMessage(), Log::ERROR);
            $event->setStatus(0);
            $event->setError($e->getMessage());
        }
    }

    /**
     * Method to import Menu Items
     *
     *
     * @param   MigrationStatusEvent    $event
     *
     * @since 1.0
     */
    public function importMenuItem(MigrationStatusEvent $event)
    {
        $app      = $this->getApplication();
        $key      = $event->getArgument('key');
        $field    = $event->getArgument('field');
        $update[] = [];
        try {
            $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);

            $query = $this->wpDB->getQuery(true)
                ->select('DISTINCT ID , post_title , post_parent , menu_order , post_date , e.name')
                ->from($this->wpDB->quoteName('#__posts', 'a'))
                ->leftJoin($this->wpDB->quoteName('#__postmeta', 'b'), $this->wpDB->quoteName('a.ID') . '=' . $this->wpDB->quoteName('b.post_id'))
                ->leftJoin($this->wpDB->quoteName('#__term_relationships', 'c'), $this->wpDB->quoteName('a.ID') . '=' . $this->wpDB->quoteName('c.object_id'))
                ->leftJoin($this->wpDB->quoteName('#__term_taxonomy', 'd'), $this->wpDB->quoteName('c.term_taxonomy_id') . '=' . $this->wpDB->quoteName('d.term_taxonomy_id'))
                ->leftJoin($this->wpDB->quoteName('#__terms', 'e'), $this->wpDB->quoteName('d.term_id') . '=' . $this->wpDB->quoteName('e.term_id'))
                ->where($this->wpDB->quoteName('ID') . '=' . $key);

            $this->wpDB->setQuery($query);
            $row = $this->wpDB->loadObject();

            // load taxonomy id
            $query = $this->wpDB->getQuery(true)
                ->select('meta_value')
                ->from($this->wpDB->quoteName('#__postmeta', 'a'))
                ->where($this->wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($this->wpDB->quoteName('a.meta_key') . '=' . $this->wpDB->quote('_menu_item_object_id'));
            $this->wpDB->setQuery($query);
            $result = $this->wpDB->loadObject();

            $taxonomyid = \intval($result->meta_value);

            // Is category or tag or page or post or customLink
            $query = $this->wpDB->getQuery(true)
                ->select($this->wpDB->quoteName('meta_value'))
                ->from($this->wpDB->quoteName('#__postmeta', 'a'))
                ->where($this->wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($this->wpDB->quoteName('a.meta_key') . '=' . $this->wpDB->quote('_menu_item_object'));
            $this->wpDB->setQuery($query);
            $resultload   = $this->wpDB->loadObject();
            $taxonomytype = $resultload->meta_value;

            // load taxonomy title information

            if ($taxonomytype == "category" || $taxonomytype == "post_tag") {
                Log::add('logfilecategory  ' . $taxonomyid . \gettype($taxonomyid));

                $query = $this->wpDB->getQuery(true)
                    ->select($this->wpDB->quoteName('name'))
                    ->from($this->wpDB->quoteName('#__terms', 'a'))
                    ->where($this->wpDB->quoteName('a.term_id') . '=' . $taxonomyid);
                $this->wpDB->setQuery($query);
                $taxonomyinfo  = $this->wpDB->loadObject();
                $menuitemtitle = (empty($row->post_title)) ? $taxonomyinfo->name : $row->post_title;
            } else {
                $query = $this->wpDB->getQuery(true)
                    ->select($this->wpDB->quoteName('post_title'))
                    ->from($this->wpDB->quoteName('#__posts', 'a'))
                    ->where($this->wpDB->quoteName('a.ID') . '=' . $this->wpDB->quote($taxonomyid));
                $this->wpDB->setQuery($query);
                $taxonomyinfo  = $this->wpDB->loadObject();
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
                    $query = $this->wpDB->getQuery(true)
                        ->select($this->wpDB->quoteName('meta_value'))
                        ->from($this->wpDB->quoteName('#__postmeta', 'a'))
                        ->where($this->wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                        ->where($this->wpDB->quoteName('a.meta_key') . '=' . $this->wpDB->quote('_menu_item_url'));
                    $this->wpDB->setQuery($query);
                    $menuitemlink = ($this->wpDB->loadObject())->meta_value;
                    break;
                default:
                    $menuitemlink = " ";
                    break;
            }

            $menuitem                    = new MenuTable($this->getDatabase());
            $menuitem->menutype          = $row->name;
            $menuitem->title             = $menuitemtitle;
            $menuitem->alias             = strtolower($menuitemtitle);
            $menuitem->note              = '';
            $menuitem->path              = strtolower($menuitemtitle);
            $menuitem->link              = $menuitemlink;
            $menuitem->type              = 'component';
            $menuitem->published         = 1;
            $menuitem->parent_id         = $row->post_parent;
            $menuitem->level             = $row->menu_order;
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
            $menuitem->publish_up        = $row->post_date;
            $menuitem->publish_down      = null;
            $menuitem->store();

            $map[$key] = $menuitem->id;
            $event->setStatus(1);
            $event->setLastID($row->term_id);
            Log::add('Menu with WP ID ' . $row->term_id . ' (Joomla: ' . $menu->id . ') successfully imported.');
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing Menuitem with ID ' . $key . ' failed. ' . $e->getMessage(), Log::ERROR);
            $event->setStatus(0);
            $event->setError($e->getMessage());
        }
    }

    /**
     * Method to import post and pages
     *
     *
     * @param   MigrationStatusEvent    $event
     *
     * @since 1.0
     */
    public function importPostsAndPage(MigrationStatusEvent $event)
    {
        $app         = $this->getApplication();
        $key         = $event->getArgument('key');
        $field       = $event->getArgument('field');
        $map         = $app->getUserState('com_migratetojoomla.wordpress.content_map', []);
        $tagmap      = $app->getUserState('com_migratetojoomla.wordpress.tag_map', []);
        $usermap     = $app->getUserState('com_migratetojoomla.wordpress.user_map', []);
        $categorymap = $app->getUserState('com_migratetojoomla.wordpress.category_map', []);

        $update[]    = [];
        $articletype = "";
        try {
            $dataparameter = $app->getUserState('com_migratetojoomla.parameter', []);
            // $imagemigrateway = 1;
            $imagemigrateway = @$dataparameter['postfeatureimage'];
            // datetime
            $maxKey = $app->getSession()->get('migratetojoomla.maxkey', []);

            $date = (string)Factory::getDate();
            $db   = $this->getDatabase();
            // current login user
            $user   = $app->getIdentity();
            $userid = $user->id;

            $query = $this->wpDB->getQuery(true)
                ->select('*')
                ->from($this->wpDB->quoteName('#__posts', 'a'))
                ->where($this->wpDB->quoteName('a.ID') . '=' . $key);

            $this->wpDB->setQuery($query);
            $results = $this->wpDB->loadAssocList();
            $row     = $results[0];
            // $totalcount = count($results);
            // foreach ($results as $row) {

            $articleid   = $key + $maxKey['content'];
            $articletype = $row['post_type'];

            // getting all categories associate with item
            $query  = $this->wpDB->getQuery(true)
                ->select('*')
                ->from($this->wpDB->quoteName('#__term_relationships', 'a'))
                ->leftJoin($this->wpDB->quoteName('#__term_taxonomy', 'b'), $this->wpDB->quoteName('a.term_taxonomy_id') . '=' . $this->wpDB->quoteName('b.term_taxonomy_id'))
                ->leftJoin($this->wpDB->quoteName('#__terms', 'c'), $this->wpDB->quoteName('b.term_id') . '=' . $this->wpDB->quoteName('c.term_id'))
                ->where($this->wpDB->quoteName('a.object_id') . '=' . $key, 'AND')
                ->where($this->wpDB->quoteName('b.taxonomy') . '=' . $this->wpDB->quote('category'));
            $this->wpDB->setQuery($query);
            $allcategories =  $this->wpDB->loadAssocList();

            // getting all tags associate with item
            $query  = $this->wpDB->getQuery(true)
                ->select('*')
                ->from($this->wpDB->quoteName('#__term_relationships', 'a'))
                ->leftJoin($this->wpDB->quoteName('#__term_taxonomy', 'b'), $this->wpDB->quoteName('a.term_taxonomy_id') . '=' . $this->wpDB->quoteName('b.term_taxonomy_id'))
                ->leftJoin($this->wpDB->quoteName('#__terms', 'c'), $this->wpDB->quoteName('b.term_id') . '=' . $this->wpDB->quoteName('c.term_id'))
                ->where($this->wpDB->quoteName('a.object_id') . '=' . $key, 'AND')
                ->where($this->wpDB->quoteName('b.taxonomy') . '=' . $this->wpDB->quote('post_tag'));
            $this->wpDB->setQuery($query);
            $alltags =  $this->wpDB->loadAssocList();

            // getting id of featured image
            $query = $this->wpDB->getQuery(true)
                ->select('meta_value')
                ->from($this->wpDB->quoteName('#__postmeta', 'a'))
                ->where($this->wpDB->quoteName('a.post_id') . '=' . $key, 'AND')
                ->where($this->wpDB->quoteName('a.meta_key') . '=' . $this->wpDB->quote('_thumbnail_id'));
            $this->wpDB->setQuery($query);
            $tempresult =  $this->wpDB->loadAssocList();

            $imageid = null;
            if (\count($tempresult) > 0) {
                $imageid = $tempresult[0]['meta_value'];
            }

            // changing media url and images field of article in format of joomla path
            $imageinfo    = null;
            $imageurl     = null;
            $articleimage = '{"image_intro":"","image_intro_alt":"","float_intro":"","image_intro_caption":"","image_fulltext":"","image_fulltext_alt":"","float_fulltext":"","image_fulltext_caption":""}';
            if (!\is_null($imageid)) {
                $query = $this->wpDB->getQuery(true)
                    ->select('post_title , post_content, post_excerpt, post_name , guid')
                    ->from($this->wpDB->quoteName('#__posts', 'a'))
                    ->where($this->wpDB->quoteName('a.ID') . '=' . $imageid);
                $this->wpDB->setQuery($query);
                $imageinfo = $this->wpDB->loadAssocList();
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
                        Log::add($imageinfo['post_title'], "success");
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

            $this->getDatabase()->insertObject('#__content', $article);
            // tag map all item associate tags
            foreach ($alltags as $tag) {
                $tagmap                  = new \stdClass();
                $tagmap->type_alias      = "com_content.article";
                $tagmap->core_content_id = 6;
                $tagmap->content_item_id = $articleid;
                $tagmap->tag_id          = $tag['term_id'] + $maxKey['tags'];
                $tagmap->tag_time        = $date;
                $tagmap->type_id         = 1;

                $this->getDatabase()->insertObject('#__contentitem_tag_map', $tagmap);
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
                    $query    = $db->getQuery(true)
                        ->select('id')
                        ->from($db->quoteName('#__tags'))
                        ->where($db->quoteName('id') . '=' . $category['term_id'] + $maxKey['categories']);
                    $db->setQuery($query);
                    $tempdata =  $db->loadAssocList();

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


                        $db->insertObject('#__tags', $tag);
                    }
                    $tagmap                  = new \stdClass();
                    $tagmap->type_alias      = "com_content.article";
                    $tagmap->core_content_id = 6;
                    $tagmap->content_item_id = $articleid;
                    $tagmap->tag_id          = $category['term_id'] + $maxKey['categories'];
                    $tagmap->tag_time        = $date;
                    $tagmap->type_id         = 1;

                    $db->insertObject('#__contentitem_tag_map', $tagmap);
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

                $db->insertObject('#__menu', $menuitem);
            }

            $map[$key] = $menu->id;
            $event->setStatus(1);
            $event->setLastID($row->term_id);
            Log::add('Article with WP ID ' . $row->term_id . ' (Joomla: ' . $menu->id . ') successfully imported.');
        } catch (\RuntimeException $e) {
            Log::add('Error: Importing article with ID ' . $key . ' failed. ' . $e->getMessage(), Log::ERROR);
            $event->setStatus(0);
            $event->setError($e->getMessage());
        }
        $app->setUserState('com_migratetojoomla.wordpress.content_map', $map);
    }
}
