<?php

namespace mahara\blocktype\CaldavCalendarPlugin\test;

use mahara\blocktype\CaldavCalendarPlugin\IcalEvent;
use mahara\blocktype\CaldavCalendarPlugin\libical\LibIcalUtil;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstanceWrapperImpl;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventEditableWrapperImpl;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstance;
use mahara\blocktype\CaldavCalendarPlugin\IcalRecur;

/**
 * DummyImplementation of Ical for testing purposes
 *
 * @author tobias
 */
class IcalEventTestImpl implements IcalEvent {
    /**
     * @var DateInterval
     */
    private $duration = null;
    /**
     * @var DateTime
     */
    private $enddate = null;
    /**
     * @var DateTime
     */
    private $startdate = null;
     /**
     * @var array
     */
    private $exceptionDates = array();
    /**
     * @var IcalRecur
     */
    private $recurRule = null;
    /**
     * @var string
     */
    private $summary = null;
    /**
     * @var bool
     */
    private $isAllDay = false;

    /**
     * @return \IcalEventTestImpl
     */
    public function withDuration(DateInterval $duration) {
        $this->duration = $duration;
        return $this;
    }
    /**
     * @return IcalEventTestImpl
     */
    public function withEnddate(\DateTime $dateTime) {
        $this->enddate = $dateTime;
        return $this;
    }
    /**
     * @return IcalEventTestImpl
     */
    public function withStartDate(\DateTime $startDate) {
        $this->startdate = $startDate;
        return $this;
    }
    /**
     * @return IcalEventTestImpl
     */
    public function withExceptionDate(\DateTime $exceptionDate) {
        $this->exceptionDates []= $exceptionDate;
        return $this;
    }
    /**
     * @return IcalEventTestImpl
     */
    public function withExceptionDates(array $exceptionDates) {
        $this->exceptionDates = $exceptionDates;
        return $this;
    }
    /**
     * @param IcalRecur $recur
     * @return IcalEventTestImpl
     */
    public function withRecur(IcalRecur $recur) {
        $this->recurRule = $recur;
        return $this;
    }
    /**
     * @return IcalEventTestImpl
     */
    public function withSummary($summary) {
        $this->summary = $summary;
        return $this;
    }
    /**
     * @return IcalEventTestImpl
     */
    public function withAllDay($isallday) {
        $this->isAllDay = $isallday;
        return $this;
    }

    public function get_attendees() {
        return array();
    }

    public function get_description() {
        return null;
    }

    public function get_duration() {
        return $this->duration;
    }

    public function get_end_date() {
        return $this->enddate;
    }

    public function get_exception_dates() {
        return $this->exceptionDates;
    }

    public function get_location() {
        return null;
    }

    public function get_recurrence_rule() {
        return $this->recurRule;
    }

    public function get_start_date() {
        return $this->startdate;
    }

    public function get_summary() {
        return $this->summary;
    }

    public function get_uid() {
        return null;
    }

    public function is_all_day() {
        return $this->isAllDay;
    }
}
