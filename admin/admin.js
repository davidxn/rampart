var myFormData = new FormData();

$(function() {

    // Edit property buttons
    $("span.property-edit").click(function(){
        var td = $(this).closest("td.editable-property");
        $(this).hide();
        td.children('div.property-editor').show();
    });
    
    // Cancel property edit buttons
    $(".property-cancel").click(function(){
        var td = $(this).closest("td.editable-property");
        td.children('div.property-editor').hide();
        td.children('span.property-edit').show();
    });

    // New slot button - custom
    $(".new-slots").click(function(){
        var number = $('.number-of-slots').val();
        myFormData = new FormData();
        myFormData.set('slots', number);
        submitNewSlots(myFormData);
    });
    
    // New slot button - fixed
    $(".template-slots").click(function(){
        var template = $(this).attr('name');
        var slots = $(this).data('slots');
        myFormData = new FormData();
        myFormData.set('template', template);
        myFormData.set('slots', slots);
        submitNewSlots(myFormData);
    });
    
    // Renumber slots button
    $(".renumber-slots").click(function(){
        myFormData = new FormData();
        renumberSlots(myFormData);
    });

    // Confirm property edit buttons
    $(".property-ok").click(function(){
        var td = $(this).closest("td.editable-property");
        var tr = $(this).closest("tr");
        var rampid = tr.attr("name");
        var field = td.children("div").children("input").attr("name");
        var value = td.children("div").children("input").val();
        if (field == null) {
            field = td.children("div").children("textarea").attr("name");
            value = td.children("div").children("textarea").val();
        }
        myFormData = new FormData();
        myFormData.set('rampid', rampid);
        myFormData.set('field', field);
        myFormData.set('value', value);
        submitEdit(myFormData, td);
    });

    var maps_table = $(".maps_table");

    // Delete
    maps_table.on("click", ".property-delete", function() {handleDelete(this); });

    // Lock and unlock
    maps_table.on("click", ".property-locked", function() {handleLock(this); });
    maps_table.on("click", ".property-unlocked", function() {handleLock(this); });
    
    // Enable and disable
    maps_table.on("click", ".property-disabled", function() {handleDisable(this); });
    maps_table.on("click", ".property-enabled", function() {handleDisable(this); });

    var handleLock = function(me) {
        var td = $(me).closest("td.editable-property");
        var tr = $(me).closest("tr");
        var ramp_id = tr.attr("name");
        var field = 'lock';
        var value = $(me).hasClass("property-unlocked") ? 1 : -1;
        myFormData = new FormData();
        myFormData.set('rampid', ramp_id);
        myFormData.set('field', field);
        myFormData.set('value', value);
        submitLock(myFormData, td);
    };
    
    var handleDisable = function(me) {
        var td = $(me).closest("td.editable-property");
        var tr = $(me).closest("tr");
        var ramp_id = tr.attr("name");
        var field = 'disabled';
        var value = $(me).hasClass("property-enabled") ? 1 : 0;
        myFormData = new FormData();
        myFormData.set('rampid', ramp_id);
        myFormData.set('field', field);
        myFormData.set('value', value);
        submitDisable(myFormData, td);
    };

    var handleDelete = function(me) {
        var tr = $(me).closest("tr");
        var name = tr.find('input[name="name"]')[0].value
        var lump = tr.find('input[name="lump"]')[0].value
        var ramp_id = tr.attr("name");
        if (!confirm('Are you sure you want to delete RAMP map '
                + ramp_id + ', "' + lump + ": " + name + '"?')) {
            return;
        }
        myFormData = new FormData();
        myFormData.set('rampid', ramp_id);
        submitDelete(myFormData);
    };
});

function submitEdit(formdata, td){
    var newValue = formdata.get("value");

    $.ajax({
        url: './commands/handle_data_edit.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (response.error) {
                td.children('div.property-editor').hide();
                td.children('span.property-edit').show();
            } else {
                td.children('div.property-editor').hide();
                var texta = td.children("span.property-edit").children("pre");
                if (texta.length > 0) {
                    $(texta).text(newValue);
                } else {
                    td.children('span.property-edit').text(newValue);
                }
                td.children('input').val(newValue);
                td.children('span.property-edit').show();
            }
        }
    });
}

function submitLock(formdata, td){
    var newValue = formdata.get("value");

    $.ajax({
        url: './commands/handle_data_edit.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                if (newValue > 0) {
                    td.children('button.property-unlocked').addClass('property-locked').removeClass('property-unlocked');
                } else {
                    td.children('button.property-locked').addClass('property-unlocked').removeClass('property-locked');
                }
            }
        }
    });
}

function submitDisable(formdata, td){
    var newValue = formdata.get("value");

    $.ajax({
        url: './commands/handle_data_edit.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                if (newValue > 0) {
                    td.children('button.property-enabled').addClass('property-disabled').removeClass('property-enabled');
                } else {
                    td.children('button.property-disabled').addClass('property-enabled').removeClass('property-disabled');
                }
            }
        }
    });
}

function submitDelete(formdata){
    $.ajax({
        url: './commands/handle_delete_map.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                location.reload();
            }
        }
    });
}

function submitNewSlots(formdata){
    
    $.ajax({
        url: './commands/handle_new_slots.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                location.reload();
            }
        }
    });
}

$('#forceNewSnapshotLink').on("click", submitSettingsTouch);

function submitSettingsTouch(){

    $.ajax({
        url: './commands/handle_settings_touch.php',
        type: 'get',
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                $('#forceNewSnapshotLink').text("Project will be regenerated on next download");
            }
        }
    });
}

$('#releaseLocksLink').on("click", submitReleaseLocks);

function submitReleaseLocks(){

    $.ajax({
        url: './commands/handle_release_locks.php',
        type: 'get',
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                $('#releaseLocksLink').text("Locks released");
            }
        }
    });
}
