<?php

class CalendarSuggestion {
    public $path;
    public $name;
    public $calendar;
    
    public function CalendarSuggestion ($path, $name, $calendar) {
        $this->path = $path;                
        $this->name = $name;
        $this->calendar = $calendar;
    }
}