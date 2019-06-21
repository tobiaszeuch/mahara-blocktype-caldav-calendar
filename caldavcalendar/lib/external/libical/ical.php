<?php
########################################################################
#
# Project: libical
# URL: http://www.nabber.org/projects/
# E-mail: webmaster@nabber.org
#
# Copyright: (C) 2003-2007, Neil McNab
# License: GNU General Public License Version 2
#   (http://www.gnu.org/copyleft/gpl.html)
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#
# Filename: $URL: https://libical.svn.sourceforge.net/svnroot/libical/trunk/libical/ical.php $
# Last Updated: $Date: 2007-08-14 18:41:07 -0700 (Tue, 14 Aug 2007) $
# Author(s): Neil McNab
#
# Description:
#   This file is a PHP implementation of RFC 2445.
#
# Last Updated: $Date: 2016-11-21 22:00:00 +-+0200 (Mon, 21 Nov 2016) $
# Added refined classes for RRULE and Date_Property (and blind subclasses)
# Author(s): Tobias Zeuch
#
#
########################################################################

/**
 * This file is a PHP implementation of RFC 2445 (ical).
 * @author Neil McNab <webmaster@nabber.org>
 * @version 0.4
 * @package libical
 */

# Constants
/**
 * Max length of a line in the iCal file
 * @global integer $LINE_FOLD_LENGTH
 */
$LINE_FOLD_LENGTH = 75;
/**
 * Date format string
 * @global string $DATE_FORMAT
 */
$DATE_FORMAT = "Ydm\THis";

/**
 * This is the class that everyone should start with to open an existing file or create a new one.
 * @package libical
 */
class ical_File extends ical_ComponentManager {
  /**
   * Subelements allowed according to spec
   * @access private
   */
  var $allowed_subelements = array('VCALENDAR');

  /**
   * Pass a filename (existing or not) when an instance is created.
   * @param string $filename
   */
  function ical_File($filename="") {
    $this->filename = $filename;

    # if file exists, open it for reading and read it in
    if (file_exists($filename)) {
      $filearray = file($filename);
      if ($filearray !== FALSE) {
        $this->load_ical($filearray);
        //return 0;
      }
    }

    # else create new empty calendar object
    //return 1;
  }

  /**
   * Dump ical data to text string
   * Slightly modified from parent class because this isn't really an RFC defined object
   * @param string $text
   * @return string
   */
  function ical_dump($text = "") {
    foreach ($this->components as $key => $value) {
      for ($i = 0 ; $i < sizeof($this->components[$key]); $i++) {
        # check if this method exists before calling
        //print_r($this->components[$key][$i]);
        if (method_exists($this->components[$key][$i], 'ical_dump')) {
          $text .= $this->components[$key][$i]->ical_dump();
        }
        else {
          //print "<p>ERROR: Could not dump object data to iCal file.</p>\n";
	  trigger_error("Could not dump object data to iCal file.");
        }
      }
    }

    return $text;
  }

  /**
   * Write out calendar(s) in this object to a file
   * Returns 0 on sucess, -1 on failure
   * @param string $filename
   * @return integer
   */
  function write($filename = "") {

    if ($filename != "") {
      $this->filename = $filename;
    }

    $handle = fopen($this->filename, "w");
    if ($handle !== FALSE) {
      fwrite($handle, $this->ical_dump());
      fclose($handle);
      return 0;
    }
    return -1;
  }

  /**
   * download method -- outputs the appropriate headers
   * then outputs the results of ical_dump
   *
   * this method  added by C. R. Dick http://wazzuplocal.com
   *
   * @param string $filename
   */
  function download($filename='myical.ics')
  {
        header("Content-Type: text/x-vCalendar");
        header("Content-Disposition: inline; filename=$filename");
        echo $this->ical_dump();
  }

}

/**
 * Base class for any values that we need to handle.
 */
class ical_Property {
  /**
   * Property parameters
   * @access private
   */
  var $params = array();
  var $value = null;

  /**
   * Init function, set known variables
   * @param string $value
   * @param string $params
   */
  function __construct($value, $params="") {
      // multiple key-value pairs=> add all as parameter
    if (substr_count($value, ";") > 0) {
        $this->set_params($value);
    }
    else {
        $this->set_value($value);
        $this->set_params($params);
    }
  }

  /**
   * Returns the value stored in this property
   * @return string
   */
  function get_value() {
    return $this->value;
  }

  /**
   * Sets the value stored in this property
   * @param string $value
   */
  function set_value($value) {
    $this->value = $value;
  }

  /**
   * Returns the params (semicolon separated)stored in this property
   * @return string
   */
  function get_params() {
    $output = "";
    foreach ($this->params as $key => $value) {
      $output .= ";$key=$value";
    }
    return $output;
  }

  /**
   * Sets the params (semicolon separated) stored in this property
   * @param string $paramstring
   */
  function set_params($paramstring) {
    $list = explode(";", $paramstring);
    for($i = 0; $i < sizeof($list); $i++) {
        if (false != strpos($list[$i], '=')) {
            list($key, $value) = explode("=", $list[$i], 2);
            $this->set_param($key, $value);
        }
    }
  }

  /**
   * Add a param stored in this property
   * @param string $name
   * @param string $value
   */
  function set_param($name, $value) {
    if ($value != "") {
      $key = strtoupper(trim($name));
      $this->params[$key] = $value;
    }
  }
  /**
   * Returns this property in ical text format
   * @param string $text
   * @return string
   */
  function text_dump($text = "") {
    $text .= $this->get_params() . ":" . $this->value;
    return $text;
  }

}

/**
 * RFC Sections 4.4 - 4.6
 * This is a base class for just about everything and shouldn't be
 * created directly
 */
class ical_ComponentManager {
  /**
   * This is where we store subelements
   * @var array
   */
  var $components = array();

  /**
   * These values are allowed to appear only once
   * @access private
   * @var array
   */
  var $onlyonce = array();

  /**
   * These values are required, they cannot be removed, only set
   * @access private
   * @var array
   */
  var $required = array();

  /**
   * subelements allowed according to spec
   * @access private
   * @var array
   */
  var $allowed_subelements = array();

  /**
   * subelements allowed according to spec
   * @access private
   * @var array
   */
  var $toggle = array();

  /**
   * This should probably be a private function, it doesn't need to be called directly by anyone.
   * @access private
   * @param string $component
   * @return string
   */
  function cleanup_component($component) {

    # We don't need to mess with objects, only strings
    if (is_string($component)) {
      $component = trim($component);
      $component = strtoupper($component);
    }
    return $component;
  }

  /**
   * Add this value if it can have multiple entries, otherwise overwrite the old one.
   * @param string $component
   * @param ical_Property $value
   * @return integer
   */
  function add($component, $value) {
    $index = "";

    $component = $this->cleanup_component($component);

    if (array_search($component, $this->toggle) !== FALSE) {
      # This value can occur only by itself, remove the old ones
      foreach($this->toggle as $key => $value){
        $this->remove($value);
      }
    }

    if (array_search($component, $this->onlyonce) !== FALSE) {
      # This value can occur only once per RFC, overwrite the old one
      $this->components[$component][0] = $value;
      return 0;
    }
    else {
      # Append to already existing array
      $this->components[$component][] = $value;
      return 1;
    }
    return 2;
  }

  /**
   * This is only used for adding additional items.
   * @param string $component
   * @param ical_Property $value
   */
  function add_new($component, $value, $params) {
    $cleanComponent = $this->cleanup_component($component);
    $cleanParams = $this->cleanup_component($params);

    # Append to already existing array
    $propertyClassname = ucfirst(strtolower("$cleanComponent"));
    if (!class_exists($propertyClassname)) {
        $propertyClassname = "ical_Property";
    }
    $property = new $propertyClassname($value, $cleanParams);
    $this->add($cleanComponent, $property);
  }

  /**
   * Set all values for this component type in this array return 0 on success, 1 otherwise.
   * This is really silly to use for single entries only, it is the same as using add, but probably more cumbersome.
   * @param string $component
   * @param array $arrayofvalues Array of ical_Property objects
   * @return integer
   */
  function set($component, $arrayofvalues) {

    $component = $this->cleanup_component($component);

    if ((array_search($component, $this->onlyonce) !== FALSE) &&
        (sizeof($arrayofvalues) > 1)) {
      # This condition is not allowed per RFC, not changing anything
      return -1;
    }

    $this->components[$component] = $arrayofvalues;
    return 0;
  }

  /**
   * Remove all of a given component.
   * Return 0 on success, -1 otherwise (required element).
   * @param string $component
   * @return integer
   */
  function remove($component) {

    $component = $this->cleanup_component($component);

    if (array_search($component, $this->required) !== FALSE) {
      # check required list, if required, do not remove!
      return -1;
    }

    unset($this->components[$component]);
    return 0;
  }

  /**
   * Return an array of ical_Property objects for the component.
   * @param string $component
   * @return array Array of ical_Property objects
   */
  function get($component) {
    # return a given component, array form
    $component = $this->cleanup_component($component);
    if (array_key_exists($component, $this->components)) {
        return $this->components[$component];
    }
    return array();
  }

  /**
   * return the first component of a type, if it exists, and null otherwise.
   * Usefull especially for components that are allowed only once
   * @param type $componentName
   * @return ical_ComponentManager
   */
  function getSingleComponent($componentName) {
      $components = $this->get($componentName);
      if (sizeof($components) > 0) {
          return $components[0];
      }
      return null;
  }

  /**
   * Return all component values in native array form.
   * @return array
   */
  function get_all() {
      return $this->components;
  }

  /**
   * Prepare iCal array of lines for further processing.
   * @param string $plaintext
   * @return array Array of strings
   */
  function cleanup_array($plaintext) {

    for ($i = 0; $i < sizeof($plaintext); $i++) {
      # skip blank lines
      if (trim($plaintext[$i]) != "") {

        $line = ltrim(rtrim($plaintext[$i],"\r\n"));

        # Do unfolding per RFC
        while (($i < sizeof($plaintext) - 1) && preg_match("/^\s.*/", $plaintext[$i + 1])) {
          $i++;
          $line .= rtrim(substr($plaintext[$i], 1),"\r\n");
	}

	$new_plaintext[] = $line;
      }
    }

    return $new_plaintext;
  }
  /**
   * Recursive method to add ical objects from their text.
   * @param string $plaintext Text in iCal format
   */
  function load_ical($plaintext) {

    $plaintext = $this->cleanup_array($plaintext);

    $subelement = "";
    for ($i = 0; $i < sizeof($plaintext); $i++) {
      $line = $plaintext[$i];

      # Find beginning and store type for matching end tag
      if (preg_match("/BEGIN:(.*)/i", $line, $matches)) {
        $match = strtoupper(trim($matches[1]));
        if (array_search($match, $this->allowed_subelements) !== FALSE) {
          $subelement = $match;
        }
      }

      if ($subelement != "") {
        $subelementtext[] = $line;
      }
      else {
        # add value to current object
	// This needs to be modified for the RFC quotations exception
        list($temp, $value) = explode(":", $line, 2);

	# also need to extract semicolon params here from key!
	$temparray = "";
        if (false != strpos($temp, ";")) {
            $temparray = explode(";", $temp, 2);
            $this->add_new($temparray[0], $value, $temparray[1]);
        }
        else {
            $temparray = array($temp);
            $this->add_new($temp, $value, null);
        }
      }

      if (!strcasecmp("END:" . $subelement, $line)) {
        # strip off unneed BEGIN/END pairs for element, we know what to create
        $subelementtext = array_slice($subelementtext, 1 ,-1);

	# create new subelement and add it to this element
        $element = new $subelement();
        $element->load_ical($subelementtext);
        $this->add($subelement, $element);

	# reset for next time through loop
        $subelementtext = "";
        $subelement = "";
      }
    }
    return 0;
  }

  /**
   * Dump ical data to text.
   * @param string $text
   * @return string
   */
  function ical_dump($text = "") {

    # do one last verfication of RFC compliance on the object
    if ($this->rfc_verify() < 0) {
      return "Calendar does not comply with RFC 2445\r\n";
    }

    $classtext = strtoupper(get_class($this));
    $text .= $this->format_line("BEGIN:" . $classtext);

    $text = $this->components_dump($text);

    $text .= $this->format_line("END:" . $classtext);

    return $text;
  }

  /**
   * Dump component values to text format.
   * @param string $text
   * @return string
   */
  function components_dump($text = "") {

    foreach ($this->components as $key => $value) {
      for ($i = 0 ; $i < sizeof($this->components[$key]); $i++) {
	# check if this method exists before calling
        if (method_exists($this->components[$key][$i], 'text_dump')) {
          $text .= $this->format_line($key . $this->components[$key][$i]->text_dump());
	}
	elseif (method_exists($this->components[$key][$i], 'ical_dump')) {
          $text = $this->components[$key][$i]->ical_dump($text);
        }
        else {
          //print "<p>ERROR: Could not dump object data to iCal file, $key, $i.</p>";
	  //print_r($this->components[$key][$i]);
	  trigger_error("Could not dump object data to iCal file.");
        }
      }
    }
    return $text;
  }

  /**
   * Do line folding as defined in the RFC.
   * @global integer
   * @param string $line
   * @return string
   */
  function format_line($line) {
    global $LINE_FOLD_LENGTH;
    return rtrim(chunk_split($line, $LINE_FOLD_LENGTH, "\r\n "))  . "\r\n";
  }

  /**
   * Return 0 when everything checks out, negative code otherwise.
   * @param boolean $recursive
   * @return integer
   */
  function rfc_verify($recursive = FALSE) {

    # check once, required, toggle, subelements list
    foreach ($this->onlyonce as $key) {
      if (sizeof($this->components[$key]) > 1) {
        return -1;
      }
    }

    foreach ($this->required as $key) {
      if (!array_key_exists($key, $this->components)) {
        return -2;
      }
    }

    # toggle check here
    $total = 0;
    for($i = 0; $i < sizeof($this->toggle); $i++) {
      for($j = 0; $j < sizeof($this->toggle); $j++) {
        if (array_search($this->toggle[$i][$j], array_keys($this->components)) !== FALSE) {
          $total += 1;
        }
      }
    }
    if ($total > 1) {
      return -3;
    }

    # find invalid properties
    # need list of valid properties first!
    #$searcharray = array_merge($onlyonce, $required, $allowed_subelements);
    //need to add toggle stuff
    #foreach($this->components as $key => $value) {
    #  if (array_search($key, $searcharray) === FALSE && substr($key, 0, 2) != "X-") {
    #    return -4;
    #  }
    #}

    # check sub components
    if ($recursive) {
      foreach($this->components as $key => $value) {
        if (!is_array($value)) {
          $code = $this->components[$key]->rfc_verify();
	  if ($code < 0) {
            return $code;
	  }
	}
      }
    }

    return 0;
  }
}

/**
 * Calendar object class
 */
class vCalendar extends ical_ComponentManager {
  var $onlyonce = array('PRODID','VERSION','CALSCALE','METHOD');
  var $required = array('PRODID','VERSION');
  var $allowed_subelements = array('VTODO','VEVENT');

  /**
   * Set defaults
   */
  function vCalendar() {
    $prodid = new ical_Property("libical; http://www.nabber.org/projects/ical/");
    $version = new ical_Property("2.0");
    $this->components = array("PRODID" => array($prodid), "VERSION" => array($version));
  }

  /**
   * Get all events during a given period of time
   * @param integer $starttime
   * @param integer $endtime
   * @return array
   */
  function get_events($starttime = 0, $endtime = 10000000000) {
    $temp = array();

    if (!array_key_exists('VEVENT', $this->components)) {
        return $temp;
    }

    /* @var $vEvent vEvent */
    foreach ($this->components['VEVENT'] as $vEvent) {

        if (0 < $starttime) {
            /* @var $dtStart ical_Property */
            $dtStart = $vEvent->getDtStart();
            if ($dtStart->get_value() < $starttime) {
                continue;
            }
        }
        if ($endtime < 10000000000) {
            /* @var $dtEnd ical_Property */
            $dtEnd = $vEvent->getDtEnd();
            if ($dtEnd->get_value() > $endtime) {
                continue;
            }
        }
        $temp []= $vEvent;
    }

    return $temp;
  }
}

class Dtstart extends Date_Property {
}

class Dtend extends Date_Property {
}

class Exdate extends Date_Property {
}

class Date_Property extends ical_Property {
    const TZID = 'TZID';

    /**
     * returns the timezone-ID or null, if not defined
     * @return string
     */
    public function getTZID() {
        if (array_key_exists(Date_Property::TZID, $this->params)) {
            return $this->params[Date_Property::TZID];
        }
        return null;
    }
}

/**
 * see @link https://tools.ietf.org/html/rfc2445#section-4.3.10 Definition of Recurrence Rule
 */
class Rrule extends ical_Property {
    /** = "SECONDLY" / "MINUTELY" / "HOURLY" / "DAILY" / "WEEKLY" / "MONTHLY" / "YEARLY"*/
    const FREQ = 'FREQ';
    /** 1*DIGIT */
    const INTERVAL = 'INTERVAL';
    /** 1*DIGIT */
    const COUNT = 'COUNT';
    /**
     * date
     * date-time            ;An UTC value
     */
    const UNTIL = 'UNTIL';
    /**
     * seconds / ( seconds *("," seconds) )
     * seconds    = 1DIGIT / 2DIGIT       ;0 to 59
     */
    const BYSECOND = 'BYSECOND';
    /**
     * minutes / ( minutes *("," minutes) )
     * minutes    = 1DIGIT / 2DIGIT       ;0 to 59
     */
    const BYMINUTE = 'BYMINUTE';
    /**
     * hour / ( hour *("," hour) )
     * hour       = 1DIGIT / 2DIGIT       ;0 to 23
     */
    const BYHOUR = 'BYHOUR';
    /**
     * weekdaynum / ( weekdaynum *("," weekdaynum) )
     * weekdaynum = [([plus] ordwk / minus ordwk)] weekday
     * plus       = "+"
     * minus      = "-"
     * ordwk      = 1DIGIT / 2DIGIT       ;1 to 53
     * weekday    = "SU" / "MO" / "TU" / "WE" / "TH" / "FR" / "SA"
     * ;Corresponding to SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY,
     * ;FRIDAY, SATURDAY and SUNDAY days of the week.
     */
    const BYDAY = 'BYDAY';
    /**
     * monthdaynum / ( monthdaynum *("," monthdaynum) )
     * monthdaynum = ([plus] ordmoday) / (minus ordmoday)
     * ordmoday   = 1DIGIT / 2DIGIT       ;1 to 31
     */
    const BYMONTHDAY = 'BYMONTHDAY';
    /**
     * yeardaynum / ( yeardaynum *("," yeardaynum) )
     * yeardaynum = ([plus] ordyrday) / (minus ordyrday)
     * ordyrday   = 1DIGIT / 2DIGIT / 3DIGIT      ;1 to 366
     */
    const BYYEARDAY = 'BYYEARDAY';
    /**
     * weeknum / ( weeknum *("," weeknum) )
     * weeknum = ([plus] ordwk) / (minus ordwk)
     * ordwk      = 1DIGIT / 2DIGIT       ;1 to 53
     */
    const BYWEEKNO = 'BYWEEKNO';
    /**
     * monthnum / ( monthnum *("," monthnum) )
     * monthnum   = 1DIGIT / 2DIGIT       ;1 to 12
     */
    const BYMONTH = 'BYMONTH';
    /**
     * setposday / ( setposday *("," setposday) )
     * setposday  = yeardaynum
     */
    const BYSETPOS = 'BYSETPOS';
    /**
     * "SU" / "MO" / "TU" / "WE" / "TH" / "FR" / "SA"
     * ;Corresponding to SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY,
     * ;FRIDAY, SATURDAY and SUNDAY days of the week.
     */
    const WKST = 'WKST';
    const SECONDLY = 'SECONDLY';
    const MINUTELY = 'MINUTELY';
    const HOURLY = 'HOURLY';
    const DAILY = 'DAILY';
    const WEEKLY = 'WEEKLY';
    const MONTHLY = 'MONTHLY';
    const YEARLY = 'YEARLY';
    const SU = 'SU';
    const MO = 'MO';
    const TU = 'TU';
    const WE = 'WE';
    const TH = 'TH';
    const FR = 'FR';
    const SA = 'SA';

    // lets assume for the moment, that there is only one frequency and multiple frequencies would result in multiple Rrules
//    var $onlyonce = array(Rrule::INTERVAL, Rrule::BYSECOND, Rrule::BYMINUTE, Rrule::BYHOUR, );
    var $required = array('FREQ');
    var $toggle = array(array(Rrule::UNTIL, Rrule::COUNT));

    private static $allowedValues = array(
        Rrule::FREQ => array(
            Rrule::SECONDLY,
            Rrule::MINUTELY,
            Rrule::HOURLY,
            Rrule::DAILY,
            Rrule::WEEKLY,
            Rrule::MONTHLY,
            Rrule::YEARLY,
        ),
        Rrule::BYDAY => array(
          Rrule::SU,
          Rrule::MO,
          Rrule::TU,
          Rrule::WE,
          Rrule::TH,
          Rrule::FR,
          Rrule::SA,
        ),
    );


/**
     * Add a param stored in this property
     * @param string $name
     * @param string $value
     */
    function set_param($name, $value) {
        if ($value != "") {
            $key = strtoupper(trim($name));
            if (array_key_exists($key, Rrule::$allowedValues)) {
                $allowedValues = Rrule::$allowedValues;
                $allowedValuesForKey = $allowedValues[$key];
                $valuesToCheck = explode(',', $value);
                foreach ($valuesToCheck as $valueToCheck) {
                    if (in_array(substr($valueToCheck, 0, 1), array('+', '-'))) {
                        $valueToCheck = substr($valueToCheck, 1);
                    }
                    $valueToCheck = ltrim($valueToCheck, "0123456789");
                    if (!in_array($valueToCheck, $allowedValuesForKey)) {
                        throw new Exception("Error when setting parameter on Rrule: Illegal value " . $valueToCheck . " for param " . $key, -1, null);
                    }
                }
            }
            $this->params[$key] = $value;
        }
    }

    public function getFREQ() {
        if (array_key_exists(Rrule::FREQ, $this->params)) {
            return $this->params[Rrule::FREQ];
        }
        return null;
    }

    public function getINTERVAL() {
        if (array_key_exists(Rrule::INTERVAL, $this->params)) {
            return $this->params[Rrule::INTERVAL];
        }
        return null;
    }

    public function getCOUNT() {
        if (array_key_exists(Rrule::COUNT, $this->params)) {
            return $this->params[Rrule::COUNT];
        }
        return null;
    }

    public function getUNTIL() {
        if (array_key_exists(Rrule::UNTIL, $this->params)) {
            return $this->params[Rrule::UNTIL];
        }
        return null;
    }

    public function getBYSECOND() {
        if (array_key_exists(Rrule::BYSECOND, $this->params)) {
            return $this->params[Rrule::BYSECOND];
        }
        return null;
    }

    /**
     * minutes / ( minutes *("," minutes) )
     * minutes    = 1DIGIT / 2DIGIT       ;0 to 59
     * return string
     */
    public function getBYMINUTE() {
        if (array_key_exists(Rrule::BYMINUTE, $this->params)) {
            return $this->params[Rrule::BYMINUTE];
        }
        return null;
    }

    public function getBYHOUR() {
        if (array_key_exists(Rrule::BYHOUR, $this->params)) {
            return $this->params[Rrule::BYHOUR];
        }
        return null;
    }

    /**
     * weekdaynum / ( weekdaynum *("," weekdaynum) )
     * weekdaynum = [([plus] ordwk / minus ordwk)] weekday
     * plus       = "+"
     * minus      = "-"
     * ordwk      = 1DIGIT / 2DIGIT       ;1 to 53
     * weekday    = "SU" / "MO" / "TU" / "WE" / "TH" / "FR" / "SA"
     * ;Corresponding to SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY,
     * ;FRIDAY, SATURDAY and SUNDAY days of the week.
     * return string
     */
    public function getBYDAY() {
        if (array_key_exists(Rrule::BYDAY, $this->params)) {
            return $this->params[Rrule::BYDAY];
        }
        return null;
    }

    public function getBYMONTHDAY() {
        if (array_key_exists(Rrule::BYMONTHDAY, $this->params)) {
            return $this->params[Rrule::BYMONTHDAY];
        }
        return null;
    }

    public function getBYYEARDAY() {
        if (array_key_exists(Rrule::BYYEARDAY, $this->params)) {
            return $this->params[Rrule::BYYEARDAY];
        }
        return null;
    }

    /**
     * weeknum / ( weeknum *("," weeknum) )
     * weeknum = ([plus] ordwk) / (minus ordwk)
     * ordwk      = 1DIGIT / 2DIGIT       ;1 to 53
     * return string
     */
    public function getBYWEEKNO() {
        if (array_key_exists(Rrule::BYWEEKNO, $this->params)) {
            return $this->params[Rrule::BYWEEKNO];
        }
        return null;
    }

    public function getBYMONTH() {
        if (array_key_exists(Rrule::BYMONTH, $this->params)) {
            return $this->params[Rrule::BYMONTH];
        }
        return null;
    }

    /**
     * setposday / ( setposday *("," setposday) )
     * setposday  = yeardaynum
     * @return string
     */
    public function getBYSETPOS() {
        if (array_key_exists(Rrule::BYSETPOS, $this->params)) {
            return $this->params[Rrule::BYSETPOS];
        }
        return null;
    }

    /**
     * "SU" / "MO" / "TU" / "WE" / "TH" / "FR" / "SA"
     * ;Corresponding to SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY,
     * ;FRIDAY, SATURDAY and SUNDAY days of the week.
     * @return string
     */
    public function getWKST() {
        if (array_key_exists(Rrule::WKST, $this->params)) {
            return $this->params[Rrule::WKST];
        }
        return null;
    }
}

/**
 * Event object class
 */
class vEvent extends ical_ComponentManager {
  var $onlyonce = array('CLASS','CREATED','DESCRIPTION','DTSTART','GEO','LAST-MOD','LOCATION','ORGANIZER','PRIORITY','DTSTAMP','SEQ','STATUS','SUMMARY','TRANSP','UID','URL','RECURID');
  var $required = array();
  var $allowed_subelements = array('VALARM', 'RRULE', 'RDATE');
  var $toggle = array(array('DTEND','DURATION'));

  const EXDATE =  'EXDATE';

  /**
   * returns the class of the event
   * @return ical_Property
   */
  public function getClass() {
     return $this->getSingleComponent('CLASS');
  }

  /**
   * returns the Summary/Title of the event
   * @return ical_Property
   */
  public function getSummary() {
     return $this->getSingleComponent('SUMMARY');
  }

  /**
   * returns the long Description text of the event
   * @return ical_Property
   */
  public function getDESCRIPTION() {
     return $this->getSingleComponent('DESCRIPTION');
  }
  
  /**
   * returns the Start of the event
   * @return Date_Property
   */
  public function getDtStart() {
     return $this->getSingleComponent('DTSTART');
  }

  /**
   * returns the Start of the event
   * @return ical_Property
   */
  public function getRECURID() {
     return $this->getSingleComponent('RECURID');
  }

  /**
   * returns the Start of the event
   * @return Rrule
   */
  public function getRRULE() {
      return $this->getSingleComponent('RRULE');
  }

  /**
   * 
   * @return Date_Property
   */
  public function getDtEnd() {
      return $this->getSingleComponent('DTEND');
  }

  /**
   *
   * @return array
   */
  public function getExDates() {
      $components = $this->get(vEvent::EXDATE);
      return $components;
  }

  /**
   * returns the uid of the event as string
   * @return string
   */
  public function getUid() {
      /* @var $uidProp ical_Property  */
      $uidProp = $this->getSingleComponent('UID');
      if (null != $uidProp) {
          return $uidProp->get_value();
      }
      return null;
  }


  function gen_event_instances($starttime, $endtime) {
    $temp = array();

    // copy current element data to instance
    $tempelement = new vEventInstance();
    foreach ($this->components as $element) {
      $tempelement->components[] = $element;
    }

    $temp[] = $tempelement;

    return $temp;
  }

}

/**
 * Event Instance object class
 */
class vEventInstance extends vEvent {
  function get_start() {
  // convert DTSTART to something meaningful here

  }
  function get_end() {
  //convert DTEND or DURATION to something useful here

  }

}

/**
 * Todo object class
 */
class vTodo extends ical_ComponentManager {
  var $onlyonce = array('CLASS','COMPLETED','CREATED','DESCRIPTION','DTSTAMP','DTSTART','GEO','LAST-MOD','LOCATION','ORGANIZER','PERCENT','PRIORITY','RECURID','SEQ','STATUS','SUMMARY','UID','URL');
  var $required = array();
  var $allowed_subelements = array('VALARM');
  var $toggle = array(array('DUE','DURATION'));

}

/**
 * Journal object class
 */
class vJournal extends ical_ComponentManager {
  var $onlyonce = array('CLASS','CREATED','DESCRIPTION','DTSTART','DTSTAMP','LAST-MOD','ORGANIZER','RECURID','SEQ','STATUS','SUMMARY','UID','URL');
  var $required = array();
  var $allowed_subelements = array();

}

/**
 * Freebusy object class
 */
class vFreebusy extends ical_ComponentManager {
  var $onlyonce = array('CONTACT','DTSTART','DTEND','DURATION','DTSTAMP','ORGANIZER','UID','URL');
  var $required = array();

}

/**
 * Timezone object class
 */
class vTimezone extends ical_ComponentManager {
  var $onlyonce = array('TZID','LAST-MOD','TZURL');
  var $required = array('TZID');


// required standard or daylight
}

/**
 * Tzprop object class
 */
class Tzprop extends ical_ComponentManager {
  var $onlyonce = array('DTSTART','TZOFFSETTO','TZOFFSETFROM');
  var $required = array('DTSTART','TZOFFSETTO','TZOFFSETFROM');

}

/**
 * Standard object class
 */
class Standard extends Tzprop {

}

/**
 * Daylist object class
 */
class Daylight extends Tzprop {

}

/**
 * Alarm object class
 */
class vAlarm extends ical_ComponentManager {
  var $onlyonce = array('ACTION','TRIGGER','DURATION','REPEAT','ATTACH'); // These values are allowed to appear only once
  var $required = array('ACTION','TRIGGER'); // These values are required, they cannot be removed, only set

/*                ; 'duration' and 'repeat' are both optional,
		; and MUST NOT occur more than once each,
		; but if one occurs, so MUST the other
	         duration / repeat /
*/
}

##########################################

/**
 * This really shouldn't ever be needed, since according to the RFC everything needs to be within a calendar to begin with, just use load_ical in the Vcalendar class
 */
function create_from_ical($subelementext) {
  //not complete

  $subelementtext = array_slice($subelementtext, 1 ,-1);
  $element = new $subelement();
  $element->load_ical($subelementtext);

}

/**
 * pass in arbitrary number of icalfile objects
 * returns a new icalfile object with all calendars
 * @return ical_File
 */
function merge_icalobjects() {

  $count = func_num_args();

  $calstring = "";
  for($i = 0; $i < $count; $i++) {
    $tempcal = func_get_arg($i);
    $calstring .= $tempcal->ical_dump();
  }

  $outputcal = new iCalFile();
  $outputcal->load_ical(text_to_array($calstring));

  return $outputcal;
}

/**
 * pass in arbitrary number of vcalendar objects
 * returns a new vcalendar object with all calendar items
 * @param ical_File
 * @param ical_File
 * @return ical_File
 */
function merge_calendars() {

  $count = func_num_args();

  $calstring = "";
  for($i = 0; $i < $count; $i++) {
    $tempcal = func_get_arg($i);
    $calstring .= $tempcal->components_dump();
  }

  //TODO - add items to calendar

  return 0;

}

function icaltime2epochtime($icaltime) {
  //convert ical text time to epochtime
  // if ends in Z, convert
  // otherwise this always represents local time
}

function epochtime2icaltime($icaltime) {
}

/**
 * @global string
 */
function local2utc($local, $offset) {
  global $DATE_FORMAT;
  # offset in hours
  $time = strtotime($local);
  $time = $time - $offset * 3600;
  return date($DATE_FORMAT, $time);
}

/**
 * @global string
 */
function utc2local($utc, $offset) {
  global $DATE_FORMAT;
  $time = strtotime($utc);
  $time = $time + $offset * 3600;
  return date($DATE_FORMAT, $time);
}

/**
 * convert a string into an array based on newlines
 * @param string $text
 * @return array
 */
function text_to_array($text) {
  $text = trim($text);
  $myarray = explode("\r\n", $text);
  return $myarray;
}

?>
