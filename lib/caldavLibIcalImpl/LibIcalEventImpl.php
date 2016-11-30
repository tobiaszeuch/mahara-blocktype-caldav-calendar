<?php

namespace mahara\blocktype\CaldavCalendarPlugin\libical;

use mahara\blocktype\CaldavCalendarPlugin\IcalEvent;
use mahara\blocktype\CalDavCalendarPlugin\libical\LibIcalUserAddressImpl;

/**
 * Wrapper for the libical-vEvent file
 *
 * @author Tobias Zeuch
 */
class LibIcalEventImpl implements IcalEvent{
    /** @var \vEvent Description */
    private $vEvent;

    const ATTENDEE = 'ATTENDEE';
    const DESCRIPTION = 'DESCRIPTION';
    const DURATION = 'DURATION';
    const DTEND = 'DTEND';
    const EXDATE =  'EXDATE';
    const LOCATION = 'LOCATION';
    const DTSTART = 'DTSTART';
    const SUMMARY = 'SUMMARY';
    const UID = 'UID';
    const RRULE = 'RRULE';

    /**
     * @var DateTime
     */
    private $startDateTime = null;
    /**
     * @var DateTime
     */
    private $endDateTime = null;
    /**
     *
     * @var IcalRecur;
     */
    private $recurrule = null;


    public function __construct(\vEvent $vEvent) {
        $this->vEvent = $vEvent;
    }
    
    /**
    * return the first component of a type, if it exists, and null otherwise.
    * Usefull especially for components that are allowed only once
    * @param type $componentName
    * @return ical_ComponentManager
    */
    static function getSingleComponent(\ical_ComponentManager $ical, $componentName) {
        $components = $ical->get($componentName);
        if (sizeof($components) > 0) {
            return $components[0];
        }
        return null;
    }

    public function get_attendees() {
        $attendees = array();
        $attributes = $this->vEvent->get(self::ATTENDEE);
        foreach ($attributes as $attribute) {
            $attendees []= new LibIcalUserAddressImpl($attribute);
        }
        return $attendees;
    }

    public function get_description() {
        $descriptionProp = self::getSingleComponent($this->vEvent, self::DESCRIPTION);
        if (null != $descriptionProp) {
            return $descriptionProp->get_value();
        }
        return null;
    }

    public function get_duration() {
        $descriptionProp = self::getSingleComponent($this->vEvent, self::DURATION);
        if (null != $descriptionProp) {
            return $descriptionProp->get_value();
        }
        return null;
    }

    public function get_end_date() {
        if (null != $this->endDateTime) {
            return $this->endDateTime;
        }
        $dtEndProp = self::getSingleComponent($this->vEvent, self::DTEND);
        if (null != $dtEndProp) {
            $this->endDateTime = \RemoteCalendarUtil::dtDate_date_to_DateTime($dtEndProp);
        }
        else {
            $startDate = $this->get_start_date();
            if ($this->is_all_day()) {
                $this->endDateTime = clone $startDate;
                $this->endDateTime->setTime(23, 59, 00);
            }
            else {
                $duration = $this->get_duration();
                if (null == $duration) {
                    throw new Exception("Error in LibIcalEventImpl: no enddate and no duration defined!", -4, null);
                }
                $this->endDateTime = clone $startDate;
                $this->endDateTime->add($duration);
            }
        }
        return $this->endDateTime;
    }

    public function get_exception_dates() {
        $properties = $this->vEvent->get(self::EXDATE);
        if (null != $properties) {
            $exceptionDates = array();
            foreach ($properties as $prop)
            {
                $exceptionDate = \RemoteCalendarUtil::ical_date_to_DateTime($prop->get_value());
                $exceptionDates []= $exceptionDate;
            }
            return $exceptionDates;
        }
        return array();
    }

    public function get_location() {
        $descriptionProp = self::getSingleComponent($this->vEvent, self::LOCATION);
        if (null != $descriptionProp) {
            return $descriptionProp->get_value();
        }
        return null;
    }

    public function get_recurrence_rule() {
        if (null != $this->recurrule) {
            return $this->recurrule;
        }
        $descriptionProp = self::getSingleComponent($this->vEvent, self::RRULE);
        if (null != $descriptionProp) {
            $this->recurrule = new LibIcalRecurImpl($descriptionProp);
        }
        return $this->recurrule;
    }

    /**
     *
     * @return DateTime
     */
    public function get_start_date() {
        if (null != $this->startDateTime) {
            return $this->startDateTime;
        }
        $descriptionProp = self::getSingleComponent($this->vEvent, self::DTSTART);
        if (null != $descriptionProp) {
            $this->startDateTime = \RemoteCalendarUtil::dtDate_date_to_DateTime($descriptionProp);
        }
        return $this->startDateTime;
    }

    public function get_summary() {
        $descriptionProp = self::getSingleComponent($this->vEvent, self::SUMMARY);
        if (null != $descriptionProp) {
            return $descriptionProp->get_value();
        }
        return null;
    }

    public function get_uid() {
        $descriptionProp = self::getSingleComponent($this->vEvent, self::UID);
        if (null != $descriptionProp) {
            return $descriptionProp->get_value();
        }
        return null;
    }

    public function is_all_day() {
        $startdateProp = self::getSingleComponent($this->vEvent, self::DTSTART);
        $startdatestr = $startdateProp->get_value();
        return (strlen($startdatestr) == 8);
    }


}
