<?php 
/**
 * @version     $Id: fields.php 055 2014-07-18 14:45:00Z Anton Wintergerst $
 * @package     JiCustomFields 2.1 Framework for Joomla
 * @copyright   Copyright (C) 2014 Jinfinity. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @website     www.jinfinity.com
 * @email       support@jinfinity.com
 */

// No direct access 
defined( '_JEXEC' ) or die( 'Restricted access' );

class JiCustomFieldsTableFields extends JTable
{
    var $id = null;

    var $title = null;

    function __construct(&$db)
    {
        parent::__construct('#__jifields', 'id', $db);
    }

    public function publish($pks = null, $state = 1, $userId = 0)
    {
        $k = $this->_tbl_key;

        // Sanitize input.
        JArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state = (int) $state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks))
        {
            if ($this->$k)
            {
                $pks = array($this->$k);
            }
            // Nothing to set publishing state on, return false.
            else
            {
                $this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
                return false;
            }
        }

        // Build the WHERE clause for the primary keys.
        $where = $k . '=' . implode(' OR ' . $k . '=', $pks);

        // Determine if there is checkin support for the table.
        if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time'))
        {
            $checkin = '';
        }
        else
        {
            $checkin = ' AND (checked_out = 0 OR checked_out = ' . (int) $userId . ')';
        }

        // Get the JDatabaseQuery object
        $query = $this->_db->getQuery(true);

        // Update the publishing state for rows with the given primary keys.
        $query->update($this->_db->quoteName($this->_tbl));
        $query->set($this->_db->quoteName('state') . ' = ' . (int) $state);
        $query->where('(' . $where . ')' . $checkin);
        $this->_db->setQuery($query);

        try
        {
            $this->_db->execute();
        }
        catch (RuntimeException $e)
        {
            $this->setError($e->getMessage());
            return false;
        }

        // If checkin is supported and all rows were adjusted, check them in.
        if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
        {
            // Checkin the rows.
            foreach ($pks as $pk)
            {
                $this->checkin($pk);
            }
        }

        // If the JTable instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks))
        {
            $this->state = $state;
        }

        $this->setError('');

        return true;
    }

    public function check()
    {
        if (trim($this->title) == '')
        {
            $this->setError(JText::_('COM_CONTENT_WARNING_PROVIDE_VALID_NAME'));
            return false;
        }

        if (trim($this->alias) == '')
        {
            $this->alias = $this->title;
        }

        $this->alias = JApplication::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) == '')
        {
            $this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
        }

        // Set ordering
        if ($this->state < 0)
        {
            // Set ordering to 0 if state is archived or trashed
            $this->ordering = 0;
        } elseif (empty($this->ordering))
        {
            // Set ordering to last if ordering was 0
            $this->ordering = self::getNextOrder('state>=0');
        }

        return true;
    }

    public function store($updateNulls = false)
    {
        if (empty($this->id))
        {
            // Store the row
            parent::store($updateNulls);
        }
        else
        {
            // Get the old row
            $oldrow = JTable::getInstance('Fields', 'JiCustomFieldsTable');
            if (!$oldrow->load($this->id) && $oldrow->getError())
            {
                $this->setError($oldrow->getError());
            }

            // Verify that the alias is unique
            $table = JTable::getInstance('Fields', 'JiCustomFieldsTable');
            if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
            {
                $this->setError(JText::_('JICUSTOMFIELDS_ERROR_UNIQUE_ALIAS'));
                return false;
            }

            // Store the new row
            parent::store($updateNulls);

            // Need to reorder ?
            if ($oldrow->state >= 0 && ($this->state < 0))
            {
                // Reorder the oldrow
                $this->reorder('state>=0');
            }
        }
        return count($this->getErrors()) == 0;

        // Verify that the alias is unique
        $table = JTable::getInstance('Fields', 'JiCustomFieldsTable');
        if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
        {
            $this->setError(JText::_('JLIB_DATABASE_ERROR_ARTICLE_UNIQUE_ALIAS'));

            return false;
        }
        $result = parent::store($updateNulls);

        return $result;
    }
}