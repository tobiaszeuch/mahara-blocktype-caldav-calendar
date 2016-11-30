<?php

namespace mahara\blocktype\CaldavCalendarPlugin\test;

define('INTERNAL', 1);

require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalCalendar.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalEventBase.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalEvent.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalEventInstance.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalEventEditableWrapperImpl.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalEventInstanceImpl.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalRecur.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalUserAddress.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalWeekdays.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalFrequencies.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalEventInstanceUtil.php');
require_once(dirname(dirname(__FILE__)).'/lib/caldav/IcalNumberedWeekday.php');
require_once(dirname(dirname(__FILE__)).'/lib/RemoteCalendarUtil.php');
require_once(dirname(dirname(__FILE__)).'/lib/CaldavCalendar.php');


require_once (dirname(__FILE__).'/IcalEventTestImpl.php');
require_once (dirname(__FILE__).'/IcalRecurTestImpl.php');

use mahara\blocktype\CaldavCalendarPlugin\IcalEvent;
use mahara\blocktype\CaldavCalendarPlugin\libical\LibIcalUtil;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstanceWrapperImpl;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventEditableWrapperImpl;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstance;
use mahara\blocktype\CaldavCalendarPlugin\IcalFrequencies;
use mahara\blocktype\CaldavCalendarPlugin\IcalWeekdays;
use mahara\blocktype\CaldavCalendarPlugin\IcalEventInstanceUtil;
use mahara\blocktype\CaldavCalendarPlugin\IcalNumberedWeekday;



/**
 * Description of testCaldavCalendarUtil
 *
 * @author Tobias Zeuch
 */

class TestCaldavCalendar {

    public static function test_set_month_for_date_ical_event_wrapper() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_month(2);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-01-01 12:00"))->withEnddate(new \DateTime("2016-01-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_month($eventWrapper, array());

        if (!sizeof($instances) == 1) {
            throw new Exception("Expected 1 instance but found " . sizeof($instances));
        }
        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != new \DateTime("2016-02-01 12:00")) {
            throw new Exception("Expected start-date 2016-02-01 12:00 but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != new \DateTime("2016-02-01 13:00")) {
            throw new Exception("Expected end-date 2016-02-01 13:00 but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_expand_by_month_negative() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_month(-2);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-01-01 12:00"))->withEnddate(new \DateTime("2016-01-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_month($eventWrapper, array());

        if (!sizeof($instances) == 1) {
            throw new Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-10-01 12:00");
        $expectedEnddate = new \DateTime("2016-10-01 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    /**
     *
     */
    public static function test_expand_by_yearday() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_year_day(5);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-01-01 12:00"))->withEnddate(new \DateTime("2016-01-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_yearday($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-01-05 12:00");
        $expectedEnddate = new \DateTime("2016-01-05 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    /**
     *
     */
    public static function test_expand_by_yearday_negative() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_year_day(-360);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-01-01 12:00"))->withEnddate(new \DateTime("2016-01-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_yearday($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-01-05 12:00");
        $expectedEnddate = new \DateTime("2016-01-05 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_expand_by_week_no() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_week_no(3)->with_weekstart(IcalWeekdays::SU);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-01-01 12:00"))->withEnddate(new \DateTime("2016-01-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_week_no($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-01-24 12:00");
        $expectedEnddate = new \DateTime("2016-01-24 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_expand_by_week_no_negative() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_week_no(-49)->with_weekstart(IcalWeekdays::SU);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-01-01 12:00"))->withEnddate(new \DateTime("2016-01-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_week_no($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-01-24 12:00");
        $expectedEnddate = new \DateTime("2016-01-24 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }
    
    public static function test_set_month_day_for_date_ical_event_wrapper() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::MONTHLY)->with_day_of_month(15);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_month_day($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-10-15 12:00");
        $expectedEnddate = new \DateTime("2016-10-15 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_set_week_day_for_date_ical_event_wrapper_weekly() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::WEEKLY)->with_weekday(new IcalNumberedWeekday(null, 'WE'));

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-11-01 12:00"))->withEnddate(new \DateTime("2016-11-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_day($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-11-02 12:00");
        $expectedEnddate = new \DateTime("2016-11-02 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_set_week_day_for_date_ical_event_wrapper_yearly_with_weekno() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_week_no(4)->with_weekday(new IcalNumberedWeekday(null, 'WE'));

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-11-01 12:00"))->withEnddate(new \DateTime("2016-11-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instancesPerWeek = IcalEventInstanceUtil::expand_by_week_no($eventWrapper, array());
        $instances = IcalEventInstanceUtil::expand_by_day($eventWrapper, $instancesPerWeek);

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        if (!sizeof($instances) == 1) {
            throw new \Exception("Expected 1 instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-02-03 12:00");
        $expectedEnddate = new \DateTime("2016-02-03 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_set_week_day_for_date_ical_event_wrapper_monthly() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::MONTHLY)->with_weekday(new IcalNumberedWeekday(null, 'WE'));

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-12-01 12:00"))->withEnddate(new \DateTime("2016-12-02 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_day($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 4;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-12-07 12:00");
        $expectedEnddate = new \DateTime("2016-12-08 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $startdates = array();
        $enddates = array();
        foreach ($instances as $instance) {
            $startdates []= $instance->get_start_date();
            $enddates []= $instance->get_end_date();
        }
        if (!in_array($expectedStartdate, $startdates)) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but not found " . var_export($startdates, true));
        }
        if (!in_array($expectedEnddate, $enddates)) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but not found in " . var_export($enddates, true));
        }
    }

    public static function test_set_week_day_for_date_ical_event_wrapper_yearly() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::YEARLY)->with_weekday(new IcalNumberedWeekday(null, 'WE'));

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-11-01 12:00"))->withEnddate(new \DateTime("2016-11-02 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_day($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method set_year_day_for_date_ical_event_wrapper to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 52;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-11-02 12:00");
        $expectedEnddate = new \DateTime("2016-11-03 13:00");

        $startdates = array();
        $enddates = array();
        foreach ($instances as $instance) {
            $startdates []= $instance->get_start_date();
            $enddates []= $instance->get_end_date();
        }
        if (!in_array($expectedStartdate, $startdates)) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but not found " . var_export($startdates, true));
        }
        if (!in_array($expectedEnddate, $enddates)) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but not found in " . var_export($enddates, true));
        }
    }

    public static function test_set_hour_for_date_ical_event_wrapper() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::DAILY)->with_hour(14);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_hour($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 1;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-10-20 14:00");
        $expectedEnddate = new \DateTime("2016-10-20 15:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_set_minute_for_date_ical_event_wrapper() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::DAILY)->with_minute(14);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_minute($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 1;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-10-20 12:14");
        $expectedEnddate = new \DateTime("2016-10-20 13:14");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    public static function test_set_second_for_date_ical_event_wrapper() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::DAILY)->with_second(35);

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_second($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 1;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-10-20 12:00:35");
        $expectedEnddate = new \DateTime("2016-10-20 13:00:35");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

    function test_get_instances_from_events_no_recurrence() {
        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 12:00"));

        $instances = IcalEventInstanceUtil::get_instances_from_events($event, new \DateTime("2016-10-01 00:00"), new \DateTime("2016-11-01 00:00"));

        if (!(is_array($instances))) {
            throw new \Exception("Expected result to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 1;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }
    }

    function test_get_instances_from_events_recurrence_with_count() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::DAILY)->with_count(2)->with_interval(3);
        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 12:00"))->withRecur($recur);

        $instances = IcalEventInstanceUtil::get_instances_from_events($event, new \DateTime("2016-10-01 00:00"), new \DateTime("2016-11-01 00:00"));

        if (!(is_array($instances))) {
            throw new \Exception("Expected result to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 3;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }
    }

    function test_get_instances_from_events_recurrence_with_enddate() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::DAILY)->with_until(new \DateTime("2016-10-26 12:00"))->with_interval(3);
        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-10-20 12:00"))->withEnddate(new \DateTime("2016-10-20 12:00"))->withRecur($recur);

        $instances = IcalEventInstanceUtil::get_instances_from_events($event, new \DateTime("2016-10-01 00:00"), new \DateTime("2016-11-01 00:00"));

        if (!(is_array($instances))) {
            throw new \Exception("Expected result to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 3;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }
    }



    public static function test_expand_by_day_any_second_wednesday() {
        $recur = new IcalRecurTestImpl();
        $recur->with_frequency(IcalFrequencies::MONTHLY)->with_weekday(new IcalNumberedWeekday(2, "WE"));

        $event = new IcalEventTestImpl();
        $event->withStartDate(new \DateTime("2016-07-01 12:00"))->withEnddate(new \DateTime("2016-07-01 13:00"))->withRecur($recur);
        $eventWrapper = new IcalEventEditableWrapperImpl($event);
        $instances = IcalEventInstanceUtil::expand_by_day($eventWrapper, array());

        if (!(is_array($instances))) {
            throw new \Exception("Expected result of method expand_by_day to be array, but found " . get_class($instances));
        }

        $nrOfExpectedInstances = 1;
        if (sizeof($instances) != $nrOfExpectedInstances) {
            throw new \Exception("Expected " . $nrOfExpectedInstances . " instance but found " . sizeof($instances));
        }

        $expectedStartdate = new \DateTime("2016-07-13 12:00");
        $expectedEnddate = new \DateTime("2016-07-13 13:00");

        /*  @var $inst IcalEventInstanceWrapperImpl  */
        $inst = $instances[0];
        if ($inst->get_start_date() != $expectedStartdate) {
            throw new \Exception("Expected start-date " . $expectedStartdate->format("Y-m-d H:i") . " but found " . $inst->get_start_date()->format("Y-m-d H:i"));
        }
        if ($inst->get_end_date() != $expectedEnddate) {
            throw new \Exception("Expected end-date " . $expectedEnddate->format("Y-m-d H:i") . " but found " . $inst->get_end_date()->format("Y-m-d H:i"));
        }
    }

}


// call tests
$todobien = true;
foreach (get_class_methods(new TestCaldavCalendar()) as $method) {
    try {
        TestCaldavCalendar::$method();
    }
    catch (\Exception $ex) {
        $todobien = false;
        var_export("Error in message: " . $method);
        echo "<br/>";
        var_export($ex->getMessage());
        echo "<br/>";
    }
}

if ($todobien) {
    echo "all tests fine";
}

