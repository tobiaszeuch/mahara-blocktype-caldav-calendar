<?php


/**
 * Description of RemoteCalendarUtil
 *
 * @author Tobias Zeuch
 */
class RemoteCalendarUtil {
    const DATE_FORMAT_ISO8601 = "Y-m-d";
    const DATE_TIME_FORMAT_ISO8601 = "Y-m-d\TH:i:s\Z";
    const DATE_ICAL_FORMAT = "Ymd";
    const DATE_TIME_ICAL_FORMAT = "Ymd\THis\Z";
    const DATE_TIME_ICAL_FORMAT_NO_TZ = "Ymd\THis";

    public static function dateTime_to_iso_8601_date(DateTime $dateTime) {
        return $dateTime->format(RemoteCalendarUtil::DATE_TIME_FORMAT_ISO8601);
    }

    /**
     *
     * @param string $dateInIso8601
     * @return string
     */
    public static function iso_8601_date_to_ical($dateInIso8601) {
        $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_FORMAT_ISO8601, $dateInIso8601);
        return $date->format(RemoteCalendarUtil::DATE_ICAL_FORMAT);
    }

    /**
     *
     * @param string $dateInIso8601
     * @return string
     */
    public static function iso_8601_date_to_ical_date_time($dateInIso8601) {
        $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_FORMAT_ISO8601, $dateInIso8601);

        return $date->format(RemoteCalendarUtil::DATE_TIME_ICAL_FORMAT);
    }

    /**
     *
     * @param string $dateInIso8601
     * @return DateTime
     */
    public static function iso_8601_date_to_DateTime($dateInIso8601) {
        $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_FORMAT_ISO8601, $dateInIso8601);
        return $date;
    }

    public function dtDate_date_to_isodate(Date_Property $dtDateProperty) {
        $dateTime = RemoteCalendarUtil::dtDate_date_to_DateTime($dtDateProperty);
        return $dateTime->format(RemoteCalendarUtil::DATE_FORMAT_ISO8601);
    }

    public function dtDate_date_to_isodatetime(Date_Property $dtDateProperty) {
        $dateTime = RemoteCalendarUtil::dtDate_date_to_DateTime($dtDateProperty);
        return $dateTime->format(RemoteCalendarUtil::DATE_TIME_FORMAT_ISO8601);
    }

    /**
     * check, if the event is an all day event: the Date-Property does not have
     * TimeZone-Data/time-info
     * @param Date_Property $dtDateProperty
     * @return boolean
     */
    public static function isAllDay(Date_Property $dtDateProperty) {
       return  strlen($dtDateProperty->get_value()) == 8;
    }


    /**
     *
     * @param Date_Property $dtDateProperty
     * @return DateTime
     */
    public static function dtDate_date_to_DateTime(Date_Property $dtDateProperty) {
        $dateICal = $dtDateProperty->get_value();
        $tz = $dtDateProperty->getTZID();
        $date = null;
        try {
            if ((null != $tz) && (strlen($dateICal) == 15)) {
                $timezone = new DateTimeZone($tz);
                $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_TIME_ICAL_FORMAT_NO_TZ, $dateICal, $timezone);
            }
            if (strlen($dateICal) == 8) {
                $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_ICAL_FORMAT, $dateICal);
            }
            elseif (strlen($dateICal) == 16) {
                $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_TIME_ICAL_FORMAT, $dateICal, new DateTimeZone('UTC'));
            }
        }
        catch (Exception $e) {
            var_export($e->getMessage());
            print_r(DateTime::getLastErrors());
            die;
        }

        if (!is_object($date)) {
            var_export($dtDateProperty->get_params());
            var_export($tz);
            var_export($date);
            var_export($dtDateProperty);
            print_r(DateTime::getLastErrors());
            die;
        }

        return $date;
    }

    /**
     *
     * @param string $dateICal
     * @return DateTime
     */
    public static function ical_date_to_DateTime($dateICal) {
        try {
            if (strlen($dateICal) == 8) {
                $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_ICAL_FORMAT, $dateICal);
            }
            elseif (strlen($dateICal) == 16) {
                $date = DateTime::createFromFormat(RemoteCalendarUtil::DATE_TIME_ICAL_FORMAT, $dateICal, new DateTimeZone('UTC'));
            }
        }
        catch (Exception $e) {
            var_export($e->getMessage());
            print_r(DateTime::getLastErrors());
            die;
        }
        return $date;
    }

    /**
     * calculates a list of all events of all vCalendar objects inside $vCalendars
     * @param array $vCalendars an array of vCalendars
     * @return array all events contained in all $vCalendars
     */
    public static function calendar_component_array_to_fullcalendarevent(array $vCalendars) {
        $vEvents = array();

        /* @var $vCalendar vCalendar */
        foreach ($vCalendars as $vCalendar) {
            $eventsForCalendar = $vCalendar->get_events();
            $vEvents = array_merge($vEvents, $eventsForCalendar);
        }

        return $vEvents;
    }

    /**
     *
     * @param vEvent $vEvent
     * @return array
     */
    public static function vEventToJsonEvents(vEvent $vEvent) {
        $jsonInstances = array();

        $json = '{
            "title": "' . $vEvent->getSummary()->get_value() . '",
            "start": "' . $vEvent->getDtStart()->get_value() . '",
            "end":"' . $vEvent->getDtEnd()->get_value() . '"
        }';

        $jsonInstances []= $json;
        return $jsonInstances;
    }

    /**
     *
     * @param EventInstanceDTO $vEvent
     * @return string
     */
    public static function vEventInstanceDTOToJsonEvents(EventInstanceDTO $vEvent) {
        $dateStart = RemoteCalendarUtil::dateTime_to_iso_8601_date($vEvent->getDtStart());
        $dateEnd = RemoteCalendarUtil::dateTime_to_iso_8601_date($vEvent->getDtEnd());
        $allDay = $vEvent->getAllDay() ? 'true' : 'false';
        $uid = $vEvent->getUid();

        $json = '{
            "id": "'. $uid .'",
            "title": "' . $vEvent->getSummary()->get_value() . '",
            "start": "' . $dateStart . '",
            "end":"' . $dateEnd . '",
            "allDay":'. $allDay .'
        }';

        return $json;
    }

}