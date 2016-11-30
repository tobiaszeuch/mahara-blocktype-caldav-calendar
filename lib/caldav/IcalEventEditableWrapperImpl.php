<?php

namespace mahara\blocktype\CaldavCalendarPlugin;

use mahara\blocktype\CaldavCalendarPlugin\IcalEventEditableWrapperImpl;

/**
 * Description of IcalEventWrapperImpl
 *
 * @author tobias
 */
class IcalEventEditableWrapperImpl implements IcalEvent {
    /**
     * @var type DateTime
     */
    private $startdatetime;
    /**
     * @var type DateTime
     */
    private $enddatetime;
    /**
     * @var IcalEvent
     */
    private $wrappedevent;

    /**
     *
     * @param IcalEventEditableWrapperImpl $event
     */
    public function __construct(IcalEvent $event) {
        $this->wrappedevent = $event;
        $this->startdatetime = $event->get_start_date();
        $this->enddatetime = $event->get_end_date();
    }

    /**
     * when cloning, create clones of the start and end time. The wrapped
     * element should remain the same
     */
    public function __clone() {
        $this->startdatetime = clone($this->startdatetime);
        $this->enddatetime = clone($this->enddatetime);
    }

    /**
     * adds $interval to start and end of this editable event
     * @param \DateInterval $interval
     */
    public function add(\DateInterval $interval) {
        $this->startdatetime->add($interval);
        $this->enddatetime->add($interval);
    }

    public function get_attendees() {
        return $this->wrappedevent->get_attendees();
    }

    public function get_description() {
        return $this->wrappedevent->get_description();
    }

    public function get_duration() {
        return $this->wrappedevent->get_duration();
    }

    public function get_end_date() {
        return $this->enddatetime;
    }

    public function get_exception_dates() {
        return $this->wrappedevent->get_exception_dates();
    }

    public function get_location() {
        return $this->wrappedevent->get_location();
    }

    public function get_recurrence_rule() {
        return $this->wrappedevent->get_recurrence_rule();
    }

    public function get_start_date() {
        return $this->startdatetime;
    }

    public function get_summary() {
        return $this->wrappedevent->get_summary();
    }

    public function get_uid() {
        return $this->wrappedevent->get_uid();
    }

    public function is_all_day() {
        return $this->wrappedevent->is_all_day();
    }

}
