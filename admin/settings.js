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
            if (type === 'checkbox') {
                val = (me[0].checked);
            }
            if (type === 'radio') {
                //Don't send empty radios
                if (!me[0].checked) {
                    return;
                }
                id = me.attr("name");
            }
            myFormData.set(id, val);
            console.log(id + " = " + val);
        });
        $("textarea").each(function(index, me) {
            me = $(me);
            var val = me.val();
            var id = me.attr('id');
            myFormData.set(id, val);
        });
        submitSettings(myFormData);
        console.log(id + " = " + val);
    });
});

function submitSettings(formdata, td){
    $.ajax({
        url: './commands/handle_settings_edit.php',
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
