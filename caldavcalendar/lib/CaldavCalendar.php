<?php

use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstance;
use mahara\blocktype\CaldavCalendarPlugin\libical\LibIcalUtil;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstanceUtil;

/**
 * Description of CaldavCalendar
 *
 * @author Tobias Zeuch
 */
class CaldavCalendar {
    private $user;
    private $pass;
    private $calendar;
    private $base_url;

    const CONFIG_PARAM_USERNAME = 'username';
    const CONFIG_PARAM_PASSWORD = 'password';
    const CONFIG_PARAM_CALENDAR = 'calendar';
    const CONFIG_PARAM_BASE_URL = 'baseurl';

    /** @val $client CalDAVClient */
    private $client = null;

    /**
     * cache for errors
     * @var array
     */
    private $errors = array();

    public function __construct($user, $pass, $calendar, $base_url) {
        $this->user = $user;
        $this->pass = $pass;
        $this->calendar = $calendar;
        $this->base_url = $base_url;
    }

    public static function fromRemoteCalendarBlockInst(BlockInstance $instance) {
        $configdata = $instance->get('configdata');

        $user = null;
        if (array_key_exists(self::CONFIG_PARAM_USERNAME, $configdata)) {
            $user = $configdata[self::CONFIG_PARAM_USERNAME];
        }
        $pass = null;
        if (array_key_exists(self::CONFIG_PARAM_PASSWORD, $configdata)) {
            $pass = $configdata[self::CONFIG_PARAM_PASSWORD];
        }
        $calendar = null;
        if (array_key_exists(self::CONFIG_PARAM_CALENDAR, $configdata)) {
            $calendar = $configdata[self::CONFIG_PARAM_CALENDAR];
        }
        $base_url = null;
        if (array_key_exists(self::CONFIG_PARAM_BASE_URL, $configdata)) {
            $base_url = $configdata[self::CONFIG_PARAM_BASE_URL];
        }

        if (null === $user ||
                null === $pass ||
                null == $calendar ||
                null == $base_url) {
            return null;
        }

        // complete base_url
        $calendarLength = strlen($calendar);
        if (substr($base_url, -$calendarLength) != $calendar &&
                substr($base_url, -($calendarLength + 1)) != $calendar . "/") {
            if (substr($base_url, -1) != '/') {
                $base_url = $base_url . '/';
            }
            $base_url = $base_url . $calendar;
        }

        return new CaldavCalendar($user, $pass, $calendar, $base_url);
    }

    private function initializeClient($forceReinitialize = false) {
        if ($forceReinitialize || (null == $this->client)) {
            $this->client = new CalDAVClient($this->base_url, $this->user, $this->pass, $this->calendar);
        }
    }
    
    public function getCalendars($serverbaseUrl) {
        $currentUserPrincipal = $this->getCurrentUserPrincipal();
        $calendarHomeSet = $this->getCalendarHomeSet($currentUserPrincipal);
        $calendars = $this->getCalendarsFromCalendarHomeSet($calendarHomeSet);
        
        // add cleanUrl to the path of the calendars
        foreach ($calendars as $calendar) {
            $calendar->path = $serverbaseUrl . $calendar->path;
        }
 
        return $calendars;
    }
    
    private function getCalendarsFromCalendarHomeSet($calendarHomeSet) {
        $client = new CalDAVClient($this->base_url, $this->user, $this->pass, '');
        $client->SetDepth('1');
        $xml = <<<EOXML
<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/" xmlns:c="urn:ietf:params:xml:ns:caldav">
  <d:prop>
     <d:resourcetype />
     <d:displayname />
     <cs:getctag />
     <c:supported-calendar-component-set />
  </d:prop>
</d:propfind>
EOXML;
        // find server-base-url in substr 
        if (strpos($calendarHomeSet, $client->base_url) >= 0) {
            $path = substr($calendarHomeSet, strlen($client->base_url));
        }
        
        $client->DoXmlRequest('PROPFIND', $xml, $path);
        $xmlElem = new SimpleXMLElement($client->xmlResponse);
        $responseNodes = $xmlElem->xpath("//d:response[.//d:resourcetype/cal:calendar]");
        
        $calendars = array();
        foreach ($responseNodes as $responseNode) {
            $href = null;
            $name = null;
            $hrefNodes = $responseNode->xpath(".//d:href");
            if (sizeof($hrefNodes > 0)) {
                $href = $hrefNodes[0].'';
            }
            $nameNodes = $responseNode->xpath(".//d:displayname");
            if (sizeof($nameNodes) > 0) {
                $name = $nameNodes[0].'';
            }
            
            $suggestion = new CalendarSuggestion($href, $name, basename($href));
            $calendars []= $suggestion;
        }
        return $calendars;
    }
    
    private function getCalendarHomeSet($currentUserPrincipal) {
        $client = new CalDAVClient($this->base_url, $this->user, $this->pass, '');
        $client->SetDepth('1');
        $xml = <<<EOXML
<d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
  <d:prop>
     <c:calendar-home-set />
  </d:prop>
</d:propfind>
EOXML;
        
        // find server-base-url in substr 
        if (strpos($currentUserPrincipal, $client->base_url) >= 0) {
            $path = substr($currentUserPrincipal, strlen($client->base_url));
        }
        
        $client->DoXmlRequest('PROPFIND', $xml, $path);
        $xmlElem = new SimpleXMLElement($client->xmlResponse);
        $nodes = $xmlElem->xpath("//cal:calendar-home-set/d:href");
        
        if (sizeof($nodes > 0)) {
            $calendarHomeSet = $nodes[0];
            return $calendarHomeSet.'';
        }
    }

    private function getCurrentUserPrincipal() {
        $client = new CalDAVClient($this->base_url, $this->user, $this->pass, '');
        $client->SetDepth('0');
        $xml = <<<EOXML
<d:propfind xmlns:d="DAV:">
  <d:prop>
     <d:current-user-principal />
  </d:prop>
</d:propfind>
EOXML;

        $client->DoXMLRequest('PROPFIND', $xml, '');
        
        $xmlElem = new SimpleXMLElement($client->xmlResponse);
        $nodes = $xmlElem->xpath("//d:current-user-principal/d:href");
        
        if (sizeof($nodes) > 0) {
            $currentUserPrincipal = $nodes[0];
            return($currentUserPrincipal."");
        }
        
        return null;
    }

    /**
     * @return IcalEvent
     */
    public function getEventForEventId($uid) {
        $this->initializeClient();
        $this->client->SetDepth("1");
        $icalEvents = $this->client->GetEntryByUid($uid);

        $icals = array();
        foreach ( $icalEvents AS $icalEvent ) {
            $array = $icalEvent['data'];
            $icals []= $array;
            // we won't return more than one anyways
            break;
        }
        $calendar = LibIcalUtil::createCaldavCalendarForIcalFiles($icals);

        $events = $calendar->get_events();
        if (empty($events)) {
            return 0;
        }
        return $events[0];
    }

    /**
     *
     * @param DateTime $startDateTime
     * @param DateTime $endDateTime
     * @return array
     */
    public function getEventsForStartEnd(DateTime $startDateTime, DateTime $endDateTime) {
        $this->initializeClient();
        $this->client->SetDepth("1");
        $startIcal = $startDateTime->format(RemoteCalendarUtil::DATE_TIME_ICAL_FORMAT);
        $endIcal = $endDateTime->format(RemoteCalendarUtil::DATE_TIME_ICAL_FORMAT);
        $icalEvents = $this->client->GetEvents($startIcal, $endIcal);

        $icals = array();
        $eventinstances = array();

        foreach ( $icalEvents AS $icalEvent ) {
            if (!array_key_exists('data', $icalEvent)) {
                if (array_key_exists('message', $icalEvent)) {
                    $this->errors []= "Error when fetching data from the caldav server: " . $icalEvent['message'];
                }
                continue;
            }
            $array = $icalEvent['data'];
            $icals []= $array;
        }

        if (empty($icals)) {
            return $eventinstances;
        }

        $calendar = LibIcalUtil::createCaldavCalendarForIcalFiles($icals);
        $events = $calendar->get_events();

        foreach ($events as $event) {
            $instancerForEvent = IcalEventInstanceUtil::get_instances_from_events($event, $startDateTime, $endDateTime);
            $eventinstances = array_merge($eventinstances, $instancerForEvent);
        }

        return $eventinstances;
    }

    public function get_events_for_start_end_as_json(DateTime $startDateTime, DateTime $endDateTime) {
        $events = $this->getEventsForStartEnd($startDateTime, $endDateTime);
        $jsonObjects = array();
        foreach ($events as $event) {
            /* @var $event IcalEventInstance */
            $startdatejson = $event->get_start_date()->format(RemoteCalendarUtil::DATE_TIME_FORMAT_ISO8601);
            $enddatejson = $event->get_end_date()->format(RemoteCalendarUtil::DATE_TIME_FORMAT_ISO8601);
            $jsonObj = '{
            "title": "' . $event->get_summary() . '",
            "start": "' . $startdatejson . '",
            "end":"'    . $enddatejson . '",
            "id":"'    . $event->get_uid() . '"
            }';
            $jsonObjects []= $jsonObj;
        }

        $resp = '[' . join(',', $jsonObjects) . ']';
        return print_r($resp, true);
    }

    /**
     * return the errors, if there were any
     * @return array
     */
    public function get_and_clear_errors() {
        $errors = $this->errors;
        $this->errors = array();
        return $errors;
    }
}