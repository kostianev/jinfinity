<?php
// Code marked with #Jinfinity author/copyright
/**
 * @version     $Id: form.php 020 2013-05-31 17:26:00Z Anton Wintergerst $
 * @package     JiCustomFields 2.0 Framework for Joomla 3.0
 * @copyright   Copyright (C) 2013 Jinfinity. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @website     www.jinfinity.com
 * @email       support@jinfinity.com
 */
// Other code original author/copright
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Base this model on the backend version.
//require_once JPATH_ADMINISTRATOR.'/components/com_content/models/article.php';
require_once JPATH_ADMINISTRATOR.'/components/com_jicustomfields/models/article.php';

/**
 * Content Component Article Model
 *
 * @package     Joomla.Site
 * @subpackage  com_content
 * @since       1.5
 */
class JiCustomFieldsModelForm extends JiCustomFieldsModelArticle
{
    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.6
     */
    protected function populateState()
    {
        $app = JFactory::getApplication();

        // Load state from the request.
        $pk = $app->input->getInt('a_id');
        $this->setState('article.id', $pk);

        $this->setState('article.catid', $app->input->getInt('catid'));

        $return = $app->input->get('return', null, 'base64');
        $this->setState('return_page', base64_decode($return));

        // Load the parameters.
        //$params = $app->getParams();
        // #Jinfinity
        $params = $app->getParams('com_content');
        $this->setState('params', $params);

        $this->setState('layout', $app->input->get('layout'));
    }
    /**
     * Method to get article data.
     *
     * @param   integer The id of the article.
     *
     * @return  mixed   Content item data object on success, false on failure.
     */
    public function getItem($itemId = null)
    {
        $itemId = (int) (!empty($itemId)) ? $itemId : $this->getState('article.id');

        // Get a row instance.
        //$table = $this->getTable();
        // #Jinfinity
        $table = $this->getTable('content');

        // Attempt to load the row.
        $return = $table->load($itemId);

        // Check for a table object error.
        if ($return === false && $table->getError()) {
            $this->setError($table->getError());
            return false;
        }

        $properties = $table->getProperties(1);
        $value = JArrayHelper::toObject($properties, 'JObject');

        // #Jinfinity - Get Fields
        if($itemId!=null) {
            $db = $this->getDbo();
            $query = 'SELECT value AS fields FROM #__jifields_values WHERE cid='.$itemId;
            $db->setQuery($query);
            $fields = $db->loadResult();
            if($fields!=null) $value->fields = json_decode($fields, true);
        }

        // Convert attrib field to Registry.
        $value->params = new JRegistry;
        $value->params->loadString($value->attribs);

        // Compute selected asset permissions.
        $user   = JFactory::getUser();
        $userId = $user->get('id');
        $asset  = 'com_content.article.'.$value->id;

        // Check general edit permission first.
        if ($user->authorise('core.edit', $asset)) {
            $value->params->set('access-edit', true);
        }
        // Now check if edit.own is available.
        elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
            // Check for a valid user and that they are the owner.
            if ($userId == $value->created_by) {
                $value->params->set('access-edit', true);
            }
        }

        // Check edit state permission.
        if ($itemId) {
            // Existing item
            $value->params->set('access-change', $user->authorise('core.edit.state', $asset));
        }
        else {
            // New item.
            $catId = (int) $this->getState('article.catid');

            if ($catId) {
                $value->params->set('access-change', $user->authorise('core.edit.state', 'com_content.category.'.$catId));
                $value->catid = $catId;
            }
            else {
                $value->params->set('access-change', $user->authorise('core.edit.state', 'com_content'));
            }
        }

        $value->articletext = $value->introtext;
        if (!empty($value->fulltext)) {
            $value->articletext .= '<hr id="system-readmore" />'.$value->fulltext;
        }

        return $value;
    }

    /**
     * Get the return URL.
     *
     * @return  string  The return URL.
     * @since   1.6
     */
    public function getReturnPage()
    {
        return base64_encode($this->getState('return_page'));
    }
}
