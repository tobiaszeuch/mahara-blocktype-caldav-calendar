<?php

namespace mahara\blocktype\CaldavCalendarPlugin\test;

use mahara\blocktype\CaldavCalendarPlugin\IcalRecur;
use mahara\blocktype\CaldavCalendarPlugin\WeekDays;
use mahara\blocktype\CaldavCalendarPlugin\IcalNumberedWeekday;

/**
 * Description of IcalRecurTestImpl
 *
 * @author Tobias Zeuch
 */
class IcalRecurTestImpl implements IcalRecur {
    private $bydays = array();
    private $bydaysofmonth = array();
    private $byhours = array();
    private $byminutes = array();
    private $bymonths = array();
    private $byseconds = array();
    private $bysetpos = array();
    private $byweeknos = array();
    private $byyeardays = array();
    private $count = null;
    private $frequency = null;
    private $interval = 1;
    private $until = null;
    private $weekstart = WeekDays::SU;

    /**
     * @param string $day
     * @return IcalRecurTestImpl
     */
    public function with_weekday(IcalNumberedWeekday $day) {
        $this->bydays []= $day;
        return $this;
    }
    /**
     * @param int $day
     * @return IcalRecurTestImpl
     */
    public function with_day_of_month($day) {
        $this->bydaysofmonth []= $day;
        return $this;
    }
    /**
     * @param string $hour
     * @return IcalRecurTestImpl
     */
    public function with_hour($hour) {
        $this->byhours []= $hour;
        return $this;
    }
    /**
     * @param string $minute
     * @return IcalRecurTestImpl
     */
    public function with_minute($minute) {
        $this->byminutes []= $minute;
        return $this;
    }
    /**
     * @param string $month
     * @return IcalRecurTestImpl
     */
    public function with_month($month) {
        $this->bymonths []= $month;
        return $this;
    }
    /**
     * @param string $sec
     * @return IcalRecurTestImpl
     */
    public function with_second($sec) {
        $this->byseconds []= $sec;
        return $this;
    }
    /**
     * @param string $setpos
     * @return IcalRecurTestImpl
     */
    public function with_setpos($setpos) {
        $this->bysetpos []= $setpos;
        return $this;
    }
    /**
     * @param string $no
     * @return IcalRecurTestImpl
     */
    public function with_week_no($no) {
        $this->byweeknos []= $no;
        return $this;
    }
    /**
     * @param string $day
     * @return IcalRecurTestImpl
     */
    public function with_year_day($day) {
        $this->byyeardays []= $day;
        return $this;
    }
    /**
     * @param string $count
     * @return IcalRecurTestImpl
     */
    public function with_count($count) {
        $this->count = $count;
        return $this;
    }
    /**
     * @param string $freq
     * @return IcalRecurTestImpl
     */
    public function with_frequency($freq) {
        $this->frequency = $freq;
        return $this;
    }
    /**
     * @param string $interval
     * @return IcalRecurTestImpl
     */
    public function with_interval($interval) {
        $this->interval = $interval;
        return $this;
    }
    /**
     * @param string $until
     * @return IcalRecurTestImpl
     */
    public function with_until($until) {
        $this->until = $until;
        return $this;
    }
    /**
     * @param string $weekstart
     * @return IcalRecurTestImpl
     */
    public function with_weekstart($weekstart) {
        $this->weekstart = $weekstart;
        return $this;
    }

    /**
     * @param string $day
     * @return IcalRecurTestImpl
     */
    public function with_by_hour($hour) {
        $this->byhours []= $hour;
        return $this;
    }

    public function get_by_days() {
        return $this->bydays;
    }
    public function get_by_days_of_month() {
        return $this->bydaysofmonth;
    }
    public function get_by_hours() {
        return $this->byhours;
    }
    public function get_by_minutes() {
        return $this->byminutes;
    }
    public function get_by_months() {
        return $this->bymonths;
    }
    public function get_by_seconds() {
        return $this->byseconds;
    }
    public function get_by_set_pos() {
        return $this->bysetpos;
    }
    public function get_by_week_no() {
        return $this->byweeknos;
    }
    public function get_by_year_days() {
        return $this->byyeardays;
    }
    public function get_count() {
        return $this->count;
    }
    public function get_frequency() {
        return $this->frequency;
    }
    public function get_interval() {
        return $this->interval;
    }
    public function get_until() {
        return $this->until;
    }
    public function get_week_start() {
        return $this->weekstart;
    }

}
