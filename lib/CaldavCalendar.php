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

    /** @val $client CalDAVClient */
    private $client = null;

    public function __construct($user, $pass, $calendar, $base_url) {
        $this->user = $user;
        $this->pass = $pass;
        $this->calendar = $calendar;
        $this->base_url = $base_url;
    }

    public static function fromRemoteCalendarBlockInst(BlockInstance $instance) {
        $configdata = $instance->get('configdata');

        $user = $configdata['username'];
        $pass = $configdata['password'];
        $calendar = $configdata['calendar'];
        $base_url = $configdata['baseurl'];

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

        foreach ( $icalEvents AS $icalEvent ) {
            $array = $icalEvent['data'];
            $icals []= $array;
        }

        $calendar = LibIcalUtil::createCaldavCalendarForIcalFiles($icals);
        $events = $calendar->get_events();

        $eventinstances = array();
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
     * fetches the events for displaying in the FullCalendar for the specified
     * time frame from $start to $end.
     * @param type $start in ISO8601 format, like 2016-11-16
     * @param type $end
     */
//    public function getEventJsonListsForStartEnd($start, $end) {
//        $this->initializeClient();
//        $this->client->SetDepth("1");
//
//        $startIcal = RemoteCalendarUtil::iso_8601_date_to_ical_date_time($start);
//        $endIcal = RemoteCalendarUtil::iso_8601_date_to_ical_date_time($end);
//        $events = $this->client->GetEvents($startIcal, $endIcal);
//
//        $icalFile = new ical_File();
//
//        foreach ( $events AS $event ) {
//            $array = preg_split ('/$\R?^/m', $event['data']);
//            $icalFile->load_ical($array);
//        }
//
//        $vCalendars = array();
//        if (array_key_exists("VCALENDAR", $icalFile->get_all())) {
//            $vCalendars = $icalFile->get("VCALENDAR");
//        }
//        if (null == $vCalendars || sizeof($vCalendars) <= 0) {
//            return null;
//        }
//
//        $vEvents = RemoteCalendarUtil::calendar_component_array_to_fullcalendarevent($vCalendars);
//
//
//        $eventInstanceDtos = array();
//        $dateTimeStart = RemoteCalendarUtil::iso_8601_date_to_DateTime($start);
//        $dateTimeEnd = RemoteCalendarUtil::iso_8601_date_to_DateTime($end);
//        foreach ($vEvents as $vEvent) {
//            $events = EventInstanceDTO::fromEvent($vEvent, $dateTimeStart, $dateTimeEnd);
//            $eventInstanceDtos = array_merge($eventInstanceDtos, $events);
//        }
//
//        $jsonObjets = array();
//        foreach ($eventInstanceDtos as $eventInstanceDto) {
//            $json = RemoteCalendarUtil::vEventInstanceDTOToJsonEvents($eventInstanceDto);
//            $jsonObjets []= $json;
//        }
//
//        $resp = '[' . join(',', $jsonObjets) . ']';
//        echo print_r($resp, true);
//    }
}