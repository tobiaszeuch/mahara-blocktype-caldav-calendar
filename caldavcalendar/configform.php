<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage artefact-calendar
 * @author     Tobias Zeuch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

define('INTERNAL', 1);
define('JSON', 1);
define('NOSESSKEY', 1);

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once(get_config('docroot') . 'blocktype/lib.php');
require_once(dirname(__FILE__).'/lib.php');

$blockid = param_integer('remotecalendarinstance');
$eventUid = param_variable('uid');

$block = new BlockInstance($blockid);

$calendar = CaldavCalendar::fromRemoteCalendarBlockInst($block);

$event = $calendar->getEventForEventId($eventUid);

if (null === $event) {
    return "";
}

$dwoo = smarty_core();
$dwoo->assign('htmlId', $blockid);
$dwoo->assign('pluginpath', 'blocktype/caldavcalendar/');
$dwoo->assign('relcalendarcsspath', 'lib/external/fullcalendar-3.0.1/fullcalendar.css');

// calculate attendee list for mailto-list
$attandeeList = $event->get_attendees();
$attendees = array();
foreach ($attandeeList as $attandee) {
    /* @var $attandee \mahara\blocktype\CalDavCalendarPlugin\IcalUserAddress */
    $mailto = $attandee->get_value();
    if ($attandee->is_mail_address()) {
        $address = $attandee->get_mail_address();
    }
    else {
        $address = $mailto;
    }
    $attendees [$mailto]= $address;
}

// eventData
$dwoo->assign('title', $event->get_summary());
$dwoo->assign('allday', $event->is_all_day());
$dwoo->assign('description', $event->get_description());
$dwoo->assign('startdate', $event->get_start_date()->format(get_string('dateformat', 'blocktype.caldavcalendar')));
$dwoo->assign('startdatetime', $event->get_start_date()->format(get_string('datetimeformat', 'blocktype.caldavcalendar')));
$dwoo->assign('enddatetime', $event->get_end_date()->format(get_string('datetimeformat', 'blocktype.caldavcalendar')));
$dwoo->assign('location', $event->get_location());
$dwoo->assign('locationlink', str_replace(" ", "+", $event->get_location()));
$dwoo->assign('attendees', $attendees);

echo $dwoo->fetch('blocktype:caldavcalendar:event.tpl');