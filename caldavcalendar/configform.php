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
//define('NOSESSKEY', 1);

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once(get_config('docroot') . 'blocktype/lib.php');
require_once(dirname(__FILE__).'/lib.php');

$responseObj = new Response();

$serverbaseurl = param_variable('serverbaseurl');
$username = param_variable('username');
$passwd = param_variable('passwd');

// strip path of url
$urlComps = parse_url($serverbaseurl);
$cleanUrl = $urlComps['scheme'].'://'.$urlComps['host'];
if (array_key_exists('port', $urlComps)) {
    $cleanUrl .= ":".$urlComps['port'];
}

$serverurl = $cleanUrl . "/.well-known/caldav";
//s$erverurl = "https://yakitobi.de/.well-known/caldav";

$curl = curl_init($serverurl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_POST, 0);
$result = curl_exec($curl);
$responsecode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

// handle curl errors
if (curl_errno($curl) != 0) {
    $responseObj->success = false;
    $responseObj->errors = curl_error($curl);
}
// handle http errors
elseif ($responsecode >= 400 || $responsecode < 200) {
    $responseObj->success = false;
    $responseObj->errors = $result;
}
else {
    // 300-400 means redirect
    if ($responsecode >= 300 && $responsecode < 400) {
        $serverurl = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
    }

    // in case that the well-known call doesn't work, use the exact url entered by the user
    if ($responsecode >= 200 && $responsecode < 300) {
        $serverurl = $serverbaseurl;
    }

    // in case of http-code 200-299: we use the default 
    $caldavCal = new CaldavCalendar($username, $passwd, '', $serverurl);
    $calendarSuggestions = $caldavCal->getCalendars($cleanUrl);
    $responseObj->suggestions = $calendarSuggestions;
    $responseObj->success = true;
}

echo json_encode($responseObj);

class Response {
    public $success;
    public $suggestions;
    public $errors;
}


