jQuery(document).ready(function() {

    // page is now ready, initialize the calendar...

    jQuery('#calendar_112').fullCalendar({
            events: function(start, end, timezone, callback) {
                jQuery.ajax({
                    url: 'http://localhost/mahara-16.10/htdocs/blocktype/caldavcalendar/feed.php',
                    datatype: 'json',
                    data: {
                        remotecalendarinstance: 112,
                        failonerror : 1,
                        start: start,
                        end:end,
                        timezone:timezone
                    },
                    success: function(data, textStatus, jqXHR) {
                        if (null !== data['error'] && data['error'].length > 0) {
                            alert(data['error']);
                        }
                        callback(data['events']);
                    }
                });
            },
            eventClick: function(calEvent, jsEvent, view) {
                jQuery("#eventDetails_112");
                jQuery.ajax({
                    method: "GET",
                    url: 'http://localhost/mahara-16.10/htdocs/blocktype/caldavcalendar/eventDetails.php',
                    data: { remotecalendarinstance: '112',
                    uid: calEvent.id}
                  })
                    .done(function( msg ) {
                        box = jQuery(msg);
                        jQuery("body").append(box);
                        box.css("display:", "block")
                                .center();
                    });
                }
        });

});

jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", Math.max(0, ((jQuery(window).height() - jQuery(this).outerHeight()) / 2) +
                                                jQuery(window).scrollTop()) + "px");
    this.css("left", Math.max(0, ((jQuery(window).width() - jQuery(this).outerWidth()) / 2) +
                                                jQuery(window).scrollLeft()) + "px");
    return this;
}