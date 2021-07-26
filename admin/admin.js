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

    // Confirm property edit buttons
    $(".property-ok").click(function(){
        var td = $(this).closest("td.editable-property");
        var pin = td.attr("name");
        var field = td.children("div").children("input").attr("name");
        var value = td.children("div").children("input").val();
        myFormData = new FormData();
        myFormData.set('pin', pin);
        myFormData.set('field', field);
        myFormData.set('value', value);
        submitEdit(myFormData, td);
    });

});

function submitEdit(formdata, td){
    var newValue = formdata.get("value");

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
                td.children('span.property-edit').text(newValue);
                td.children('input').val(newValue);
                td.children('span.property-edit').show();
            }
        }
    });
}

