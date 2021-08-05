var myFormData = new FormData();

$(function() {

    // Save settings
    $("#save_settings").click(function(){
        myFormData = new FormData();
        $("input").each(function(index, me) {
            me = $(me);
            var type = me.attr('type');
            var val = me.val();
            if (type == 'checkbox') {
                val = (me[0].checked);
            }
            myFormData.set(me.attr('id'), val);
            console.log(me.attr('id') + " = " + val);
        });
        submitSettings(myFormData);
    });
});

function submitSettings(formdata, td){
    $.ajax({
        url: './handle_settings_edit.php',
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
