<?php

include("ical.php");

# How to build a simple calendar
# First create the ical file
$myicalfile = new ical_File();  //filename can optionally be passed in here

# Create event
$myevent = new vEvent();

$myproperty = new ical_Property('20020422', 'VALUE=DATE');
$myevent->add('DTSTART', $myproperty);

$myevent->add('DTEND', new ical_Property('20020423', 'VALUE=DATE'));
$myevent->add('UID',new ical_Property('1'));
$myevent->add('SUMMARY',new ical_Property('Earth Day'));
$myevent->add('SEQUENCE',new ical_Property('4'));
$myevent->add('DTSTAMP',new ical_Property('20050930T233002Z'));
$myevent->add('RRULE',new ical_Property('FREQ=YEARLY;INTERVAL=1'));


# Create the calendar (according to the standard a calendar 
# is required as a container for any objects)
$mycalendar = new vCalendar();

# Add the event to the calendar
$mycalendar->add('VEVENT', $myevent);

# Add the calendar to the ical file (you can put multiple calendars in a file)
$myicalfile->add('VCALENDAR', $mycalendar);

# These are your output options

# This will output the calendar to the screen in ical format
print_r($myicalfile->ical_dump());

# This will output the calendar to a file
# filename is only required if it was not specified upon creation
$myicalfile->write("filename.ics");

# Howto open an existing calendar for manipulation
$myicalfile1 = new ical_File("US32Holidays.ics");

# print the contents of the first calendar, array form
$array_of_contents = $myicalfile1->components[VCALENDAR][0]->get_all();

# loop through calendar objects
print "\n---------------------------\n";
foreach ($array_of_contents as $component => $value) {
  print "Type: $component\n";
  print "Count: " . sizeof($array_of_contents[$component]) . "\n";
}

// Debugging code
//$icalfile1 = new iCalFile("US32Holidays.ics");
//$icalfile2 = new iCalFile("testcal.ics");

//print_r($icalfile1->components[VCALENDAR][0]->get_all());

//print $icalfile1->components[VCALENDAR][0]->ical_dump();
//$icalfile1->write("testfile.ics");

#print str_replace("\r\n","<br />",$icalfile->ical_dump());
//print $icalfile1->ical_dump();

//print_r($icalfile->calendars[0]);

//$outputcal = merge_calendars($icalfile1, $icalfile2);

//$outputcal->write("testfile.ics");
?>
