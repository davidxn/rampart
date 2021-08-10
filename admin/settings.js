var myFormData = new FormData();

$(function() {

    // Save settings
    $("#save_settings").click(function(){
        myFormData = new FormData();
        $("input").each(function(index, me) {
            me = $(me);
            var type = me.attr('type');
            var val = me.val();
            var id = me.attr('id');
            if (type == 'checkbox') {
                val = (me[0].checked);
            }
            if (type == 'radio') {
                //Don't send empty radios
                if (!me[0].checked) {
                    return;
                }
                id = me.attr("name");
            }
            myFormData.set(id, val);
            console.log(id + " = " + val);
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
                window.location.href = 'index.php';
            }
        }
    });
}
