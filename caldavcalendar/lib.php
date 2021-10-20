<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-CalDavCalendar
 * @author     Tobias Zeuch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

// load interfaces
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalCalendar.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalEventBase.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalEvent.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalRecur.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalUserAddress.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalEventEditableWrapperImpl.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalEventInstance.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalEventInstanceImpl.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalEventInstanceUtil.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalFrequencies.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalNumberedWeekday.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldav/IcalWeekdays.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/RemoteCalendarUtil.php');

require_once(dirname(__FILE__).'/lib/external/DAViCal/caldav-client.php');
require_once(dirname(__FILE__).'/lib/external/libical/ical.php');

// load external libraries
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldavLibIcalImpl/LibIcalCalendarImpl.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldavLibIcalImpl/LibIcalEventImpl.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldavLibIcalImpl/LibIcalUserAddressImpl.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldavLibIcalImpl/LibIcalUtil.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/caldavLibIcalImpl/LibIcalRecurImpl.php');

require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/CaldavCalendar.php');
require_once(dirname(dirname(dirname(__FILE__))).'/blocktype/caldavcalendar/lib/CalendarSuggestion.php');

class PluginBlocktypeCalDavCalendar extends MaharaCoreBlocktype {

    public static function get_title() {
        return get_string('title', 'blocktype.caldavcalendar');
    }

    public static function get_instance_title() {
        return '';
    }

    public static function get_description() {
        return get_string('description', 'blocktype.caldavcalendar');
    }

    public static function single_only() {
        return false;
    }

    public static function get_categories() {
        return array('external' => 56000);
    }

    /**
    * This function must be implemented in the subclass if it requires
    * javascript. It returns an array of javascript files, either local
    * or remote.
    */
    public static function get_instance_javascript(BlockInstance $instance) {
        return array(
            'lib/external/fullcalendar-3.0.1/lib/moment.min.js',
            'lib/external/fullcalendar-3.0.1/fullcalendar.js',
        );
    }

    public static function get_html_id(BlockInstance $instance) {
        return $instance->get("id");
    }

    public static function render_instance(BlockInstance $instance, $editing = false, $versioning = false) {
        
        $data = self::get_data($instance);

        $dwoo = smarty_core();
        $dwoo->assign('htmlId', PluginBlocktypeCalDavCalendar::get_html_id($instance));
        $dwoo->assign('output', $data);
        $dwoo->assign('pluginpath', 'blocktype/caldavcalendar/');
        $dwoo->assign('relcalendarcsspath', 'lib/external/fullcalendar-3.0.1/fullcalendar.css');
        $dwoo->assign('failonerror', $editing); // on editing, fail when errors are encountered
        return $dwoo->fetch('blocktype:caldavcalendar:calendar.tpl');
    }

    public static function has_instance_config(BlockInstance $instance) {
        return true;
    }

    public static function get_instance_config_javascript(BlockInstance $instance) {
        return array(
            'js/configform.js',
        );
    }

    public static function instance_config_form(BlockInstance $instance) {
        $configdata = $instance->get('configdata');

        return array(
            'username' => array(
                'type'  => 'text',
                'title' => get_string('username','blocktype.caldavcalendar'),
                'description' => get_string('usernamedescr','blocktype.caldavcalendar'),
                'defaultvalue' => (!empty($configdata['username']) ? $configdata['username'] : null),
                'rows' => 5,
                'cols' => 76,
                'rules' => array(
                    'required' => true
                )
            ),
            'password' => array(
                'type'  => 'password',
                'title' => get_string('password','blocktype.caldavcalendar'),
                'description' => get_string('passworddescr','blocktype.caldavcalendar'),
                'defaultvalue' => (!empty($configdata['password']) ? $configdata['password'] : null),
                'rows' => 5,
                'cols' => 76,
                'rules' => array(
                    'required' => true
                )
            ),
            'baseurl' => array(
                'type'  => 'text',
                'title' => get_string('baseurl','blocktype.caldavcalendar'),
                'description' => get_string('baseurldescr','blocktype.caldavcalendar'),
                'defaultvalue' => (!empty($configdata['baseurl']) ? $configdata['baseurl'] : null),
                'rows' => 5,
                'cols' => 76,
                'rules' => array(
                    'required' => true
                ),
                'isformgroup' => false,
            ),
            'autodiscoverbtn' => array(
                'type' => 'html',
                'title' => '',
                'value' => '<button type="button" onclick="caldavAutoDiscover()">autodiscover</button>',
                'description' => 'Click with a valid base url, username and password to start autodiscover. A single calendar will be configured automatically, several calendars will be displayed as buttons to choose from.',
                'isformgroup' => true,
             ),
            'calendar' => array(
                'type'  => 'text',
                'title' => get_string('calendar','blocktype.caldavcalendar'),
                'description' => get_string('calendar','blocktype.caldavcalendar'),
                'defaultvalue' => (!empty($configdata['calendar']) ? $configdata['calendar'] : null),
                'rows' => 5,
                'cols' => 76,
                'rules' => array(
                    'required' => true
                )
            ),
        );
    }


    public static function default_copy_type(BlockInstance $instance, View $view) {
        return 'shallow';
    }

    protected static function get_data($instance) {
        return null;
    }

    /**
     * Shouldn't be linked to any artefacts via the view_artefacts table.
     *
     * @param BlockInstance $instance
     * @return multitype:
     */
    public static function get_artefacts(BlockInstance $instance) {
        return array();
    }
}
