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

    // Confirm property edit buttons
    $(".property-ok").click(function(){
        var td = $(this).closest("td.editable-property");
        var pin = td.attr("name");
        var field = td.children("div").children("input").attr("name");
        var value = td.children("div").children("input").val();
        if (field == null) {
            field = td.children("div").children("textarea").attr("name");
            value = td.children("div").children("textarea").val();
        }
        myFormData = new FormData();
        myFormData.set('pin', pin);
        myFormData.set('field', field);
        myFormData.set('value', value);
        submitEdit(myFormData, td);
    });
    
    // Lock and unlock
    $(".maps_table").on("click", ".property-locked", function() {handleLock(this); });
    $(".maps_table").on("click", ".property-unlocked", function() {handleLock(this); });

    var handleLock = function(me) {
        var td = $(me).closest("td.editable-property");
        var pin = td.attr("name");
        var field = 'lock';
        var value = $(me).hasClass("property-unlocked") ? 1 : -1;
        myFormData = new FormData();
        myFormData.set('pin', pin);
        myFormData.set('field', field);
        myFormData.set('value', value);
        submitLock(myFormData, td);
    };
});

function submitEdit(formdata, td){
    var newValue = formdata.get("value");
    var field = formdata.get("field");

    $.ajax({
        url: './handle_data_edit.php',
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
                if (texta) {
                    $(texta).text(newValue);
                } else {
                    td.children('span.property-edit').text(newValue);
                }
                td.children('input').val(newValue);
                td.children('span.property-edit').show();
                //Just reload if we've changed PIN, too much to hack around
                if (field == 'pin') {
                    window.location.reload();
                }
            }
        }
    });
}

function submitLock(formdata, td){
    var newValue = formdata.get("value");

    $.ajax({
        url: './handle_data_edit.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                if (newValue > 0) {
                    td.children('button').addClass('property-locked').removeClass('property-unlocked');
                } else {
                    td.children('button').addClass('property-unlocked').removeClass('property-locked');
                }
            }
        }
    });
}

function submitNewSlots(formdata){
    
    $.ajax({
        url: './handle_new_slots.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            if (!response.error) {
                location.reload(true);
            }
        }
    });
}
