<?php

namespace mahara\blocktype\CaldavCalendarPlugin;

use mahara\blocktype\CaldavCalendarPlugin\IcalEventEditableWrapperImpl;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstanceWrapperImpl;
use mahara\blocktype\CaldavCalendarPlugin\IcalFrequencies;
use mahara\blocktype\CaldavCalendarPlugin\IcalWeekdays;

/**
 * Holds methods to calculate the intances for an event based on the rules
 *
 * @author Tobias Zeuch
 */
class IcalEventInstanceUtil {

    public static function get_instances_from_events(IcalEvent $originalevent, \DateTime $startDateTime, \DateTime $endDateTime) {
        $instances = array();

        $event = new IcalEventEditableWrapperImpl($originalevent);
        if ($event->get_start_date() > $endDateTime) {
            return $instances;
        }
        if ($event->get_start_date() >= $startDateTime && $event->get_start_date() <= $endDateTime) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
            $event = clone($event);
        }

        // read max number of repititions. If this is 0 or if no frequency or
        //  intervar is set, return directly
        $recurrence = $event->get_recurrence_rule();
        if (null == $recurrence) {
            return $instances;
        }

        $maxcount = $recurrence->get_count();
        if ((null != $maxcount && $maxcount == 0) ||
                (null == $recurrence->get_frequency())) {
            return $instances;
        }
        $count = 0;
        // second possible stop-condition: the event recurrence rules end date.
        // if this is before end of our timeframe-of-interest, use that as
        // stop-condition for the iteration
        $lasteventstartdate = $recurrence->get_until();
        $enditerationstartdate = $endDateTime;
        if (null != $lasteventstartdate && $lasteventstartdate < $endDateTime) {
            $enditerationstartdate = $lasteventstartdate;
        }

        self::increase_event_by_interval($event);

        // stop, if $nextevent is already beyond end of our interval
        if ($event->get_start_date() > $enditerationstartdate) {
            return $instances;
        }

        // nextDate is our first step ahead, so increment count
        $count ++;

        // in case we are more than a complete interval*frequency before
        // the start of our time window, calculate the next event because
        // even with replacement of weekdays or monthdays, we won't get close
        // in the same iteration
        $nexteventstart = clone($event->get_start_date());
        $nexteventstart->add(new \DateInterval("P1D"));
        while ($nexteventstart <= $startDateTime &&
                (empty($maxcount) || $count <= $maxcount)) {
            self::increase_event_by_interval($event);
            $nexteventstart = clone($event->get_start_date());
            $nexteventstart->add(new \DateInterval("P1D"));
            // keep counting the steps
            $count ++;
        }

        // now nextevent points to some place before start but not too far in
        // the past => start exploding the events until we exceed endDateTime
        while ($event->get_start_date() <= $enditerationstartdate &&
                (empty($maxcount) || $count <= $maxcount)) {
            $instancesforevent = self::generateInstancesForEvent($event, $startDateTime, $enditerationstartdate);
            $instances = array_merge($instances, $instancesforevent);
            $event = clone($event);
            self::increase_event_by_interval($event);
            $count ++;
        }
        // exclude  exceptions
        $exceptionDates = $event->get_exception_dates();
        $notexcludedinstances = array();
        foreach ($instances as $instance) {
            if (!in_array($instance->get_start_date(), $exceptionDates)) {
                $notexcludedinstances []= $instance;
            }
        }

        return $notexcludedinstances;
    }

    public static function generateInstancesForEvent($event, \DateTime $startlimit, \DateTime $endlimit) {
        $freq = $event->get_recurrence_rule()->get_frequency();
        $instances = array(new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date()));

        switch ($freq) {
            case IcalFrequencies::YEARLY:
                // Expansions
                $monthylinstances = self::expand_by_month($event, $instances);
                $weeklyinstances = self::expand_by_week_no($event, $monthylinstances);
                $yeardayinstances = self::expand_by_yearday($event, $weeklyinstances);
                $monthdayinstances = self::expand_by_month_day($event, $yeardayinstances);
                $weekdayinstances = self::expand_by_day($event, $monthdayinstances);
                $hourlyinstances = self::expand_by_hour($event, $weekdayinstances);
                $minutelyinstances = self::expand_by_minute($event, $hourlyinstances);
                $secundlyinstances = self::expand_by_second($event, $minutelyinstances);
                // Limitations
                $limitedByDates = self::limit_by_start_end($secundlyinstances, $startlimit, $endlimit);
                $filteredinstances = self::limit_by_setpos($event, $limitedByDates);
                break;
            case IcalFrequencies::MONTHLY:
                // Expansions
                $weeklyinstances = self::expand_by_week_no($event, $instances);
                $yeardayinstances = self::expand_by_yearday($event, $weeklyinstances);
                $monthdayinstances = self::expand_by_month_day($event, $yeardayinstances);
                $weekdayinstances = self::expand_by_day($event, $monthdayinstances);
                $hourlyinstances = self::expand_by_hour($event, $weekdayinstances);
                $minutelyinstances = self::expand_by_minute($event, $hourlyinstances);
                $secundlyinstances = self::expand_by_second($event, $minutelyinstances);
                // Limitations
                $limitedByDates = self::limit_by_start_end($secundlyinstances, $startlimit, $endlimit);
                $monthlyinstanecs = self::limit_by_month($event, $limitedByDates);
                $filteredinstances = self::limit_by_setpos($event, $monthlyinstanecs);
                break;
            case IcalFrequencies::WEEKLY:
                // Expansions
                $weekdayinstances = self::expand_by_day($event, $instances);
                $hourlyinstances = self::expand_by_hour($event, $weekdayinstances);
                $minutelyinstances = self::expand_by_minute($event, $hourlyinstances);
                $secundlyinstances = self::expand_by_second($event, $minutelyinstances);
                // Limitations
                $limitedByDates = self::limit_by_start_end($secundlyinstances, $startlimit, $endlimit);
                $monthlyinstanecs = self::limit_by_month($event, $limitedByDates);
                $filteredinstances = self::limit_by_setpos($event, $monthlyinstanecs);
                break;
            case IcalFrequencies::DAILY:
                // Expansions
                $hourlyinstances = self::expand_by_hour($event, $instances);
                $minutelyinstances = self::expand_by_minute($event, $hourlyinstances);
                $secundlyinstances = self::expand_by_second($event, $minutelyinstances);
                // Limitations
                $limitedByDates = self::limit_by_start_end($secundlyinstances, $startlimit, $endlimit);
                $monthdayinstances = self::limit_by_month_day($event, $limitedByDates);
                $weekdayinstances = self::limit_by_day($event, $monthdayinstances);
                $monthlyinstanecs = self::limit_by_month($event, $weekdayinstances);
                $filteredinstances = self::limit_by_setpos($event, $monthlyinstanecs);
                break;
            case IcalFrequencies::HOURLY:
                // Expansions
                $minutelyinstances = self::expand_by_minute($event, $instances);
                $secundlyinstances = self::expand_by_second($event, $minutelyinstances);
                // Limitations
                $limitedByDates = self::limit_by_start_end($secundlyinstances, $startlimit, $endlimit);
                $monthdayinstances = self::limit_by_month_day($event, $limitedByDates);
                $weekdayinstances = self::limit_by_day($event, $monthdayinstances);
                $monthlyinstances = self::limit_by_month($event, $weekdayinstances);
                $yeardayinstances = self::limit_by_year_day($event, $monthlyinstances);
                $hourlyinstances = self::limit_by_hour($event, $yeardayinstances);
                $filteredinstances = self::limit_by_setpos($event, $hourlyinstances);
                break;
            case IcalFrequencies::MINUTELY:
                // Expansions
                $secundlyinstances = self::expand_by_second($event, $instances);
                // Limitations
                $limitedByDates = self::limit_by_start_end($secundlyinstances, $startlimit, $endlimit);
                $monthdayinstances = self::limit_by_month_day($event, $limitedByDates);
                $weekdayinstances = self::limit_by_day($event, $monthdayinstances);
                $monthlyinstances = self::limit_by_month($event, $weekdayinstances);
                $yeardayinstances = self::limit_by_year_day($event, $monthlyinstances);
                $hourlyinstances = self::limit_by_hour($event, $yeardayinstances);
                $minutelyinstances = self::limit_by_minutes($event, $hourlyinstances);
                $filteredinstances = self::limit_by_setpos($event, $minutelyinstances);
                break;
            case IcalFrequencies::SECONDLY:
                // Limitations
                $limitedByDates = self::limit_by_start_end($instances, $startlimit, $endlimit);
                $monthdayinstances = self::limit_by_month_day($event, $limitedByDates);
                $weekdayinstances = self::limit_by_day($event, $monthdayinstances);
                $monthlyinstances = self::limit_by_month($event, $weekdayinstances);
                $yeardayinstances = self::limit_by_year_day($event, $monthlyinstances);
                $hourlyinstances = self::limit_by_hour($event, $yeardayinstances);
                $minutelyinstances = self::limit_by_minutes($event, $hourlyinstances);
                $secondlyinstances = self::limit_by_seconds($event, $minutelyinstances);
                $filteredinstances = self::limit_by_setpos($event, $secondlyinstances);
                break;
        }

        return $filteredinstances;
    }

    /**
     * calculates the next base-element
     * @param IcalEventEditableWrapperImpl $event
     */
    public static function increase_event_by_interval(IcalEventEditableWrapperImpl $event) {
        $freq = $event->get_recurrence_rule()->get_frequency();
        $interval = $event->get_recurrence_rule()->get_interval();
        if (empty($interval)) {
            $interval = 1;
        }

        switch ($freq) {
            case IcalFrequencies::SECONDLY:
                $event->add(new \DateInterval("PT". $interval . "S"));
                break;
            case IcalFrequencies::MINUTELY:
                $event->add(new \DateInterval('PT'. $interval . "M"));
                break;
            case IcalFrequencies::HOURLY:
                $event->add(new \DateInterval('PT'. $interval . 'H'));
                break;
            case IcalFrequencies::DAILY:
                $event->add(new \DateInterval('P'. $interval . 'D'));
                break;
            case IcalFrequencies::WEEKLY:
                $event->add(new \DateInterval("P". $interval . "W"));
                break;
            case IcalFrequencies::MONTHLY:
                $event->add(new \DateInterval("P". $interval . "M"));
                break;
            case IcalFrequencies::YEARLY:
                $event->add(new \DateInterval("P". $interval . "Y"));
                break;
            default :
                throw Exception ("Error, invalid frequency: " . $freq);
        }
    }

    /**
     * ifByHours is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_month(IcalEventEditableWrapperImpl  $event, array $instances) {
        $months = $event->get_recurrence_rule()->get_by_months();
        if (empty($months)) {
            return $instances;
        }

        $endate = $event->get_end_date();
        if (null == $endate) {
            throw new Exception("Error; event has no enddate set: " . var_export($event));
        }
        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($months as $month) {
                $startdate = clone($instance->get_start_date());
                while ($month < 0) {
                    $month = $month + 12;
                }
                $startdate->setDate($startdate->format('Y'), $month, $startdate->format('d'));
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }
        return $newinstances;
    }

    /**
     * ifByYearDay is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_yearday(IcalEventEditableWrapperImpl  $event, array $instances) {
        $days = $event->get_recurrence_rule()->get_by_year_days();
        if (empty($days)) {
            return $instances;
        }

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($days as $day) {
                $startdate = clone($instance->get_start_date());
                if (intval($day) == 0) {
                    continue;
                }
                $deltaDays = intval($day) - 1;
                // cope with negative days
                if ($deltaDays < 0) {
                    $deltaDays = 365 + intval($deltaDays);
                }
                $startdate->setDate($startdate->format('Y'), 1, 1);
                $startdate->add(new \DateInterval('P' . $deltaDays . 'D'));
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }

        return $newinstances;
    }

    /**
     * ifByHours is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     *
     * @see https://tools.ietf.org/html/rfc5545
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_week_no(IcalEventEditableWrapperImpl  $event, array $instances) {
        $weeknumbers = $event->get_recurrence_rule()->get_by_week_no();
        if (empty($weeknumbers)) {
            return $instances;
        }

        // we need the week start to start counting from there
        $weekstart = $event->get_recurrence_rule()->get_week_start();

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();
        // construct date-interval here to only construct once
        $dateinterval1day = new \DateInterval("P1D");

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($weeknumbers as $weekno) {
                /* @var $startdate \DateTime */
                $startdate = clone($instance->get_start_date());

                // first set to weekstart, for negative weeknumbers, advance one year
                while ($weekno < 0) {
                    $weekno += 52;
                }
                $startdate->setDate($startdate->format('Y'), 1, 1);
                while (strtoupper(substr($startdate->format('l'), 0, 2)) != $weekstart) {
                    $startdate->add($dateinterval1day);
                }
                // now add the number of weeks
                $startdate->add(new \DateInterval('P' . $weekno .'W'));
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }
        return $newinstances;
    }

    /**
     * ifByMonthDay is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_month_day(IcalEventEditableWrapperImpl  $event, array $instances) {
        $days = $event->get_recurrence_rule()->get_by_days_of_month();
        if (empty($days)) {
            return $instances;
        }

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($days as $day) {
                $startdate = clone($instance->get_start_date());
                if (intval($day) <= 0) {
                    continue;
                }
                $deltaDays = intval($day) - 1;
                $startdate->setDate($startdate->format('Y'), $startdate->format('m'), 1);
                $startdate->add(new \DateInterval('P' . $deltaDays . 'D'));
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }

        return $newinstances;
    }

    /**
     * ifByMonthDay is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_day(IcalEventEditableWrapperImpl  $event, array $instances) {
        $recurrencerule = $event->get_recurrence_rule();
        $days = $recurrencerule->get_by_days();
        if (empty($days)) {
            return $instances;
        }

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        // construct date-interval here to only construct once
        $dateinterval1week = new \DateInterval("P1W");

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($days as $numberedweekday) {
                /* @var $numberedweekday IcalNumberedWeekday */
                if (!in_array(strtoupper($numberedweekday->weekday), array(IcalWeekdays::SU,
                    IcalWeekdays::MO,
                    IcalWeekdays::TU,
                    IcalWeekdays::WE,
                    IcalWeekdays::TU,
                    IcalWeekdays::FR,
                    IcalWeekdays::SA))) {
                    echo "Weekday not recognized: " . var_export($numberedweekday->weekday, true);
                    continue;
                }
                /* @var $startdate \DateTime  */
                $startdate = clone($instance->get_start_date());

                // now, depending on the frequency and other restrictions
                if (($recurrencerule->get_frequency() == IcalFrequencies::WEEKLY) ||
                        (null != ($recurrencerule->get_by_week_no()))) {
                    self::advance_date_to_weekday($startdate, $numberedweekday->weekday);
                     $enddate = clone($startdate);
                    /* @var $enddate \DateTime */
                    $enddate->add($duration);
                    $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                    $newinstances []= $newinst;
                    continue;
                }
                if ($recurrencerule->get_frequency() == IcalFrequencies::MONTHLY ||
                        (null != ($recurrencerule->get_by_months()))) {
                    $newinstances = self::process_weekday_monthly($startdate, $numberedweekday, $event, $duration, $newinstances);
                    continue;
                }
                else {
                    $newinstances = self::process_weekday_yearly($startdate, $numberedweekday, $event, $duration, $newinstances);
                }
            }
        }

        return $newinstances;
    }

    private static function advance_date_to_weekday(\DateTime $date, $weekday) {
        $dateinterval1day = new \DateInterval("P1D");
        while (strtoupper(substr($date->format("D"), 0, 2)) != $weekday) {
            $date->add($dateinterval1day);
        }
    }

    private static function process_weekday_monthly(\DateTime $startdate, IcalNumberedWeekday $numberedweekday, IcalEvent $event, \DateInterval $duration, array $newinstances) {
        // remember the month for the stop-condition and reset
        // the day to the first of the month
        $month = $startdate->format('m');
        $startdate->setDate($startdate->format('Y'), $month, 1);
        // step to the first correct day of the week
        if (null == $numberedweekday->number) {
            self::advance_date_to_weekday($startdate, $numberedweekday->weekday);
            while ($startdate->format('m') == $month) {
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
                // andavance
                $startdate = clone($startdate);
                $startdate->add(new \DateInterval("P1W"));
            }
        }
        else {
            $startdate->setDate($startdate->format('Y'), $month, 1);
            self::advance_date_to_weekday($startdate, $numberedweekday->weekday);
            $advanceWeeks = abs($numberedweekday->number) - 1;
            $startdate->add(new \DateInterval('P' . $advanceWeeks . 'W'));
            $enddate = clone($startdate);
            /* @var $enddate \DateTime */
            $enddate->add($duration);
            $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
            $newinstances []= $newinst;
        }
        return $newinstances;
    }
    
    private static function process_weekday_yearly(\DateTime $startdate, IcalNumberedWeekday $numberedweekday, IcalEvent $event, \DateInterval $duration, array $newinstances) {
        // reset to the first of the year and add for each week
        $year = $startdate->format("Y");
        $startdate->setDate($year, 1, 1);
        self::advance_date_to_weekday($startdate, $numberedweekday->weekday);
        while ($startdate->format('Y') == $year) {
            $enddate = clone($startdate);
            /* @var $enddate \DateTime */
            $enddate->add($duration);
            $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
            $newinstances []= $newinst;
            // andavance
            $startdate = clone($startdate);
            $startdate->add(new \DateInterval("P1W"));
        }
        return $newinstances;
    }

    /**
     * ifByMonthDay is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_hour(IcalEventEditableWrapperImpl  $event, array $instances) {
        $hours = $event->get_recurrence_rule()->get_by_hours();
        if (empty($hours)) {
            return $instances;
        }

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($hours as $hour) {
                $startdate = clone($instance->get_start_date());
                if (intval($hour) <= 0) {
                    continue;
                }
                $startdate->setTime($hour, $startdate->format('i'), $startdate->format('s'));
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }

        return $newinstances;
    }

    /**
     * ifByMonthDay is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_minute(IcalEventEditableWrapperImpl  $event, array $instances) {
        $minutes = $event->get_recurrence_rule()->get_by_minutes();
        if (empty($minutes)) {
            return $instances;
        }

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($minutes as $minute) {
                $startdate = clone($instance->get_start_date());
                if (intval($minute) <= 0) {
                    continue;
                }
                $startdate->setTime($startdate->format('G'), $minute, $startdate->format('s'));
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }

        return $newinstances;
    }

    /**
     * ifByMonthDay is set in the Recurrence rule, create a object for each positive
     * second-parameter that appears in the rule and return all these events.
     * Otherwise return the original event
     *
     * @param IcalEventEditableWrapperImpl $event
     * @param array $instances
     * @return IcalEventInstanceWrapperImpl|array
     */
    public static function expand_by_second(IcalEventEditableWrapperImpl  $event, array $instances) {
        $seconds = $event->get_recurrence_rule()->get_by_seconds();
        if (empty($seconds)) {
            return $instances;
        }

        $duration = $event->get_end_date()->diff($event->get_start_date(), true);
        $newinstances = array();

        // if no initial-instance is set, start with the event-instance
        if (empty($instances)) {
            $instances []= new IcalEventInstanceWrapperImpl($event, $event->get_start_date(), $event->get_end_date());
        }

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            foreach ($seconds as $second) {
                $startdate = clone($instance->get_start_date());
                if (intval($second) <= 0) {
                    continue;
                }
                $startdate->setTime($startdate->format('G'), $startdate->format('i'), $second);
                $enddate = clone($startdate);
                /* @var $enddate \DateTime */
                $enddate->add($duration);
                $newinst = new IcalEventInstanceWrapperImpl($event, $startdate, $enddate);
                $newinstances []= $newinst;
            }
        }

        return $newinstances;
    }

    /**
     * checks, if \DateTime represents a Day of the year that complies with the recursion rules
     * @param IcalEvent $event
     * @param array $instances
     * @return array
     */
    public static function limit_by_setpos(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $daypos = $recur->get_by_set_pos();

        if(empty($daypos)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualDayOfYear = $instance->get_start_date()->format('z');
            if (in_array($actualDayOfYear, $daypos)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    public static function limit_by_month(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $months = $recur->get_by_set_pos();

        if(empty($months)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualmonth = intval($instance->get_start_date()->format('n'));
            if (in_array($actualmonth, $months)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    /**
     * checks, if \DateTime represents a day of the month that complies with the rrule
     * @param IcalEvent $event
     * @param type $instances
     * @return type
     */
    public static function limit_by_month_day(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $monthdays = $recur->get_by_set_pos();

        if(empty($monthdays)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualmonthday = intval($instance->get_start_date()->format('j'));
            if (in_array($actualmonthday, $monthdays)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    /**
     * checks, if the \DateTime object represents a weekday that complies with
     * @param \mahara\blocktype\CaldavCalendarPlugin\IcalEvent $event
     * @param array $instances
     * @return array
     */
    public static function limit_by_day(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $weekdays = $recur->get_by_set_pos();

        if(empty($weekdays)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualweekday = intval($instance->get_start_date()->format('w'));
            if (in_array($actualweekday, $weekdays)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    public static function limit_by_year_day(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $yeardays = $recur->get_by_set_pos();

        if(empty($yeardays)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualyearday = intval($instance->get_start_date()->format('z'));
            if (in_array($actualyearday, $yeardays)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    public static function limit_by_hour(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $hours = $recur->get_by_set_pos();

        if(empty($hours)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualhour = intval($instance->get_start_date()->format('G'));
            if (in_array($actualhour, $hours)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    /**
     * limit events by the BYMINUTE field
     * @param \mahara\blocktype\CaldavCalendarPlugin\IcalEvent $event
     * @param array $instances
     * @return array
     */
    public static function limit_by_minutes(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $minutes = $recur->get_by_set_pos();

        if(empty($minutes)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualminute = intval($instance->get_start_date()->format('i'));
            if (in_array($actualminute, $minutes)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    /**
     * limit events by the BYSECOND field
     * @param \mahara\blocktype\CaldavCalendarPlugin\IcalEvent $event
     * @param array $instances
     * @return array
     */
    public static function limit_by_seconds(IcalEvent $event, array $instances) {
        $recur = $event->get_recurrence_rule();
        $seconds = $recur->get_by_set_pos();

        if(empty($seconds)) {
            return $instances;
        }

        $filteredinstances = array();

        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $actualsecond = intval($instance->get_start_date()->format('s'));
            if (in_array($actualsecond, $seconds)) {
                $filteredinstances []= $instance;
            }
        }

        return $filteredinstances;
    }

    public static function limit_by_start_end($instances, $startlimit, $endlimit) {
        $limitedinstances = array();
        foreach ($instances as $instance) {
            /* @var $instance IcalEventInstance */
            $startdate = $instance->get_start_date();
            if ($startdate >= $startlimit && $startdate <= $endlimit) {
                $limitedinstances []= $instance;
            }
        }
        return $limitedinstances;
    }
}
