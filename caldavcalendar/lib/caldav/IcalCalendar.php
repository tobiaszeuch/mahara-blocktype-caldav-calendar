<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace mahara\blocktype\CaldavCalendarPlugin;

/**
 *
 * @author Tobias Zeuch
 */
interface IcalCalendar {
    /**
     * returns all events for that calendar.
     * Each event is of type mahara\blocktype\CaldavCalendarPlugin\CaldavEvent
     * @return array
     */
    public function get_events();
}
