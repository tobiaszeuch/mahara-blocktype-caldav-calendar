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
$failonerror = param_boolean('failonerror');
$start = param_variable('start');
$end = param_variable('end');

if (empty($start) || empty($end)) {
    return;
}

$startDateTime = new DateTime();
$startDateTime->setTimestamp($start);
$endDateTime = new DateTime();
$endDateTime->setTimestamp($end);

$block = new BlockInstance($blockid);

$calendar = CaldavCalendar::fromRemoteCalendarBlockInst($block);
$events = "";
if (null !== $calendar) {
    $events = $calendar->get_events_for_start_end_as_json($startDateTime, $endDateTime);
}

$errors = $calendar->get_and_clear_errors();
$response = '{';
if ($failonerror && !empty($errors)) {
    $response .= '"error":"' . join('","', $errors) . '",';
}
$response .= '"events":' . $events . '}';



echo $response;