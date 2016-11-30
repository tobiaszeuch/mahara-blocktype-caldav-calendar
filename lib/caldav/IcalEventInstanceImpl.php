<?php

namespace mahara\blocktype\CaldavCalendarPlugin;

/**
 * Decorator that describes an instance of a real event, which can, in itself,
 * has recurrences
 */
class IcalEventInstanceWrapperImpl implements IcalEventInstance {
    /**
     * @var \DateTime
     */
    private $startdatetime;
    /**
     * @var \DateTime
     */
    private $enddatetime;

    /**
     * the referenced icalEvent that will hold the summary and isAllDay
     * @var IcalEvent
     */
    private $referencedevent;

    public function __construct(IcalEvent $event, \DateTime $startdatetime, \DateTime $enddatetime) {
        $this->referencedevent = $event;
        $this->startdatetime = $startdatetime;
        $this->enddatetime = $enddatetime;
    }

    public function get_calculated_end_date() {
        return $this->enddatetime;
    }

    public function get_start_date() {
        return $this->startdatetime;
    }

    public function get_summary() {
        return $this->referencedevent->get_summary();
    }

    public function get_uid() {
        return $this->referencedevent->get_uid();
    }

    public function is_all_day() {
        return $this->referencedevent->is_all_day();
    }

    public function get_attendees() {
        return $this->referencedevent->get_attendees();
    }

    public function get_description() {
        return $this->referencedevent->get_description();
    }

    public function get_location() {
        return $this->referencedevent->get_location();
    }

    public function get_end_date() {
        return $this->enddatetime;
    }

}