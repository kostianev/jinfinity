<?php
/**
 * @version     $Id: menufilter.php 097 2014-12-15 10:57:00Z Anton Wintergerst $
 * @package     Jinfinity Migrator for Joomla 1.5+
 * @copyright   Copyright (C) 2014 Jinfinity. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @website     www.jinfinity.com
 * @email       support@jinfinity.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.'/administrator/components/com_jimigrator/helpers/listfilter.php');
class JiMigratorFieldMenuFilter extends JiMigratorField {
    function getFilter() {
        $listfilterhelper = new JiListFilterHelper();
        if(version_compare( JVERSION, '1.6.0', 'ge' )) {
            $includepath = JPATH_SITE.'/administrator/components/com_jimigrator/helpers/j25menufilter.php';
        } else {
            $includepath = JPATH_SITE.'/administrator/components/com_jimigrator/helpers/j15menufilter.php';
        }
        $listfilter = $listfilterhelper->loadType('menu', $includepath);
        return $listfilter;
    }
    function renderInput() {
        if(version_compare(JVERSION, '3.0.0', 'ge')) {
            JHtml::_('jquery.framework');
            JHTML::script('media/jimigrator/js/jquery.jitoggler.js');
            JHTML::script('media/jimigrator/js/jquery.listfilter.js');
        } else {
            JHTML::_('script', 'jquery.min.js', 'media/jimigrator/js/');
            JHTML::_('script', 'jquery.noconflict.js', 'media/jimigrator/js/');
            JHTML::_('script', 'jquery.jitoggler.js', 'media/jimigrator/js/');
            JHTML::_('script', 'jquery.listfilter.js', 'media/jimigrator/js/');
        }
        
        $listfilter = $this->getFilter();
        $listfilter->setScope($this->get('id'));
        $listfilter->setName($this->get('name'));
        $data = $listfilter->open();

        // Class for toggler
        $params = $this->get('params');
        $class = $params->get('class');
        $class = ($class!=null)? ' class="'.$class.'"' : '';
        ob_start(); ?>
        <script type="text/javascript">
            if(typeof jQuery!='undefined') {
                jQuery(document).ready(function() {
                    jQuery({
                        container:jQuery('#<?php echo $this->get('inputid'); ?>'),
                        url:'<?php echo JURI::root().'administrator/index.php?option=com_jimigrator&api=json&view=listfilter&filtertype=menu&scope='.$this->get('id').'&name='.$this->get('name'); ?>',
                        data: '<?php echo str_replace('\'', '\\\'', json_encode($data)); ?>'
                    }).listfilter();
                    jQuery('.jitogglerbtn').jitoggler({tab:'.jitogglertab'});
                });
            }
        </script>
        <div id="<?php echo $this->get('inputid'); ?>"<?php echo $class; ?>></div>
        <?php
        return ob_get_clean();
    }
    function getValue($decode=false) {
        $paramsdir = JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_jimigrator'.DS.'tmp'.DS.'params';
        // Get values
        $paramspath = $paramsdir.'/'.$this->get('id').'.json';
        if(file_exists($paramspath)) {
            $values = file_get_contents($paramspath);
        } else {
            $values = '';
        }
        $values = json_decode($values, true);
        if($values!=null) {
            if(isset($values[$this->get('name')])) return $values[$this->get('name')];
        }
        return;
    }
    function renderInputLabel() {
        return;
    }
}