
<link rel="stylesheet" href="{$WWWROOT}{$pluginpath}{$relcalendarcsspath}" />
<link rel="stylesheet" href="{$WWWROOT}js/jquery/jquery-ui/css/smoothness/jquery-ui.min.css" />
<script type="text/javascript">
    jQuery(document).ready(function() {

    // page is now ready, initialize the calendar...

    jQuery('#calendar_{$htmlId}').fullCalendar({
            events: function(start, end, timezone, callback) {
                jQuery.ajax({
                    url: '{$WWWROOT}{$pluginpath}feed.php',
                    datatype: 'json',
                    data: {
                        remotecalendarinstance: {$htmlId},
                        failonerror : {if $failonerror}true{else}false{/if},
                        start : start.unix(),
                        end : end.unix()
                    },
                    success: function(data, textStatus, jqXHR) {
                        data = JSON.parse(data);
                        if (typeof(data.error) !== 'undefined') {
                            jQuery('#calendar_{$htmlId}').html("<div class=caldavPluginError>" + data.error + "</div>");
                        }
                        callback(data.events);
                    }
                });
            },
            eventClick: function(calEvent, jsEvent, view) {
                jQuery("#eventDetails_{$htmlId}");
                jQuery.ajax({
                    method: "GET",
                    url: '{$WWWROOT}{$pluginpath}eventDetails.php',
                    data: { remotecalendarinstance: '{$htmlId}',
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
</script>
<div id="calendar_{$htmlId}" style="width: 100%;">
     {$output}
</div>


