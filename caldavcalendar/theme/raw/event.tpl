<div id="calendar_detail_{$htmlId}" class="panel panel-default calendar-event" title="{$title}" style="z-index: 1; ">
    <h2 class="panel-heading js-heading">{$title}</h2>
    <div class="panel-body">
        <p>
            {$description}
        </p>
        <strong>{str tag=detailslabelwhen section=blocktype.caldavcalendar}</strong>
        <p>
        {if $allday}
            {$startdate}
        {else}
            {if $enddatetime}
                {$startdatetime}&nbsp;<b>-</b>&nbsp;{$enddatetime}
            {else}
                {$startdatetime}
            {/if}
        {/if}
        </p>
        {if $location}
            <strong>{str tag=detailslabellocation section=blocktype.caldavcalendar}</strong>
            <p>
                <a href=https://www.google.de/maps/search/{$locationlink}'>{$location}</a>
            </p>
        {/if}
        {if $attendees}
            <strong>{str tag=detailslabelattendees section=blocktype.caldavcalendar}</strong>
            <ul>
            {foreach from=$attendees key="mailto" item="person"}
                <li><a href='{$mailto}'>{$person}</a></li>
            {/foreach}
            </ul>
        {/if}
        <input onclick="jQuery('#calendar_detail_{$htmlId}').remove()" class="btn-primary submit btn" id="viewlayout_submit" name="submit" value="Ok" tabindex="0" type="submit">
    </div>
</div>