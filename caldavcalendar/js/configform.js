/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function caldavAutoDiscover() {
    baseurl = $("#instconf_baseurl")[0].value;
    username = $("#instconf_username")[0].value;
    passwd = $("#instconf_password")[0].value;
    var params = {'serverbaseurl': baseurl, 'username': username, 'passwd': passwd};
    sendjsonrequest(config['wwwroot'] + '/blocktype/caldavcalendar/configform.php', params, 'POST', function(data) {
        if (!data.success) {
            alert("Error: " + data.errors);
        }
        else if (data.error) {
            alert("Error: " + data.error_rendered);
        }
        if (data.success && data.suggestions) {
            if (data.suggestions.length === 1) {
                var suggestion = data.suggestions[0];
                autofillWithSuggestion(suggestion);
            }
            else if(data.suggestions.length > 1) {
                addCalendarsToSelectFrom(data.suggestions);
            }
        }
    });
}

// üut the suggestion data into the form
function autofillWithSuggestion(suggestion) {
    $("#instconf_baseurl")[0].value = suggestion.path;
    $("#instconf_calendar")[0].value = suggestion.calendar;
}

// add a list of calendar-buttons from the auto-suggestions
function addCalendarsToSelectFrom(suggestions) {
    var calendarList = jQuery('<ul/>');
    for (var i = 0; i < suggestions.length; i++) {
        let suggestion = suggestions[i];
        let button = jQuery('<button type="button">' + suggestion.name + '</button>', {
            id: 'instconf_autodiscoverbtn_container_' + suggestion.name
        });
        button.click(function() {
            autofillWithSuggestion(suggestion);
        });
        button.suggestion = suggestion;
        let li = jQuery("<li/>");
        li.append(button);
        calendarList.append(li);
    }
    
    $("#instconf_autodiscoverbtn_container").append(calendarList);
}
