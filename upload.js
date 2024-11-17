var uploadFormData = new FormData();
var downloadStatus = null;

var loadingSvg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; background: #747; display: block;" width="40px" height="40px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="translate(50 50)"><g><animateTransform attributeName="transform" type="rotate" values="0;45" keyTimes="0;1" dur="0.4081632653061225s" repeatCount="indefinite"></animateTransform><path d="M29.491524206117255 -5.5 L37.491524206117255 -5.5 L37.491524206117255 5.5 L29.491524206117255 5.5 A30 30 0 0 1 24.742744050198738 16.964569457146712 L24.742744050198738 16.964569457146712 L30.399598299691117 22.621423706639092 L22.621423706639096 30.399598299691114 L16.964569457146716 24.742744050198734 A30 30 0 0 1 5.5 29.491524206117255 L5.5 29.491524206117255 L5.5 37.491524206117255 L-5.499999999999997 37.491524206117255 L-5.499999999999997 29.491524206117255 A30 30 0 0 1 -16.964569457146705 24.742744050198738 L-16.964569457146705 24.742744050198738 L-22.621423706639085 30.399598299691117 L-30.399598299691117 22.621423706639092 L-24.742744050198738 16.964569457146712 A30 30 0 0 1 -29.491524206117255 5.500000000000009 L-29.491524206117255 5.500000000000009 L-37.491524206117255 5.50000000000001 L-37.491524206117255 -5.500000000000001 L-29.491524206117255 -5.500000000000002 A30 30 0 0 1 -24.742744050198738 -16.964569457146705 L-24.742744050198738 -16.964569457146705 L-30.399598299691117 -22.621423706639085 L-22.621423706639092 -30.399598299691117 L-16.964569457146712 -24.742744050198738 A30 30 0 0 1 -5.500000000000011 -29.491524206117255 L-5.500000000000011 -29.491524206117255 L-5.500000000000012 -37.491524206117255 L5.499999999999998 -37.491524206117255 L5.5 -29.491524206117255 A30 30 0 0 1 16.964569457146702 -24.74274405019874 L16.964569457146702 -24.74274405019874 L22.62142370663908 -30.39959829969112 L30.399598299691117 -22.6214237066391 L24.742744050198738 -16.964569457146716 A30 30 0 0 1 29.491524206117255 -5.500000000000013 M0 -19A19 19 0 1 0 0 19 A19 19 0 1 0 0 -19" fill="#d5cc1b"></path></g></g></svg>'

$(function() {
    
    $("#vidmask").on("mouseover", function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    $("#vidmask").on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

    // Upload form buttons
    $("#uploadtype_first").click(function(){
        $("#upload-question-type").hide();
        $("#upload-question-details").show();
    });
    
    // Upload form buttons
    $("#uploadtype_update").click(function(){
        $("#upload-question-type").hide();
        $("#upload-question-pin").show();
    });
    
    // preventing page from redirecting
    $("html").on("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $("html").on("drop", function(e) { e.preventDefault(); e.stopPropagation(); });

    // Drag enter
    $('.upload-area').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $("h1").text("All right, drop it");
    });

    // Drag over
    $('.upload-area').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $("h1").text("All right, drop it");
    });

    // Drop
    $('.upload-area').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

        var file = e.originalEvent.dataTransfer.files[0];
        $("h1").text("Ready to upload " + file.name + "!");
        uploadFormData = new FormData();
        uploadFormData.set('file', file);
    });

    // Open file selector on div click
    $("#uploadfile").click(function(){
        $("#file").click();
    });

    // file selected
    $("#file").change(function(){
        uploadFormData = new FormData();
        var file = $('#file')[0].files[0];
        $("h1").text("Ready to upload " + file.name + "!");
        uploadFormData.set('file',file);
    });
    
    $("#upload_wad").click(function(){
        uploadFormData.set('mapname', $('#input_map_name').val());
        uploadFormData.set('authorname', $('#input_author_name').val());
        uploadFormData.set('musiccredit', $('#input_music_credit').val());
        uploadFormData.set('pin', $('#input_pin_to_reupload').val());
        uploadFormData.set('jumpcrouch', ($('#input_map_jumpcrouch')[0].checked ? 1 : 0));
        uploadFormData.set('wip', ($('#input_map_wip')[0].checked ? 1 : 0));
        uploadFormData.set('category', $('input[name="input_map_category"]:checked').val());
        uploadFormData.set('difficulty', $('input[name="input_map_difficulty"]:checked').val());
        uploadFormData.set('length', $('input[name="input_map_length"]:checked').val());
        uploadFormData.set('monsters', $('#input_monster_count').val());
        uploadData(uploadFormData);
    });

    $("#confirm_pin").click(function(){
        uploadFormData = new FormData();
        uploadFormData.set('pin', $('#input_pin_to_reupload').val());
        submitPin(uploadFormData);
    });
    
    $("#download_button").click(function(){
        $('#download_button').attr("disabled", true);
        $('#download_button').addClass("waitingdisabled");
        $('#download_button').html("Please wait, generating the project...<br/>" + loadingSvg);
        downloadProject();
    });

});

function readStatus() {
    $.ajax({
        url: './status.log?xcache=' + Math.random(),
        type: 'get',
        dataType: 'text',
        success: function(response){
            $('#download_status').text(response);
        }
    });
}

function downloadProject() {
    downloadStatus = setInterval(readStatus, 800);
    $.ajax({
        url: './handle_pk3_update.php?xcache=' + Math.random(),
        type: 'get',
        dataType: 'json',
        success: function(response){
            $('#download_status').text("");
            clearInterval(downloadStatus);
            //Don't really re-enable the button!
            $('#download_button').removeClass("waitingdisabled");
            if (response.error) {
                $('#download_button').text(response.error);
                return;
            }
            $('#download_button').text("Project generated!");
            window.location.href = './handle_download.php?xcache=' + Math.random();
        }
    });
    
}

var lastlogfiletime = null;
function getBuildLog() {
    $.ajax({
        url: './get_build_log.php',
        type: 'get',
        success: function(response){
            $('#download_log').text(response.log);
            if (response.filetime != lastlogfiletime) {
                setTimeout(getBuildLog, 500);
            }
            else {
                $('#download_button').text("Project generated!");
            }
            lastlogfiletime = response.filetime;
        }
    });
}

// Sending AJAX request and upload file
function uploadData(formdata){
    $('#upload_wad').attr("disabled", true);
    $('#upload_wad').addClass("waitingdisabled");
    $('#upload_wad').text("Working...");

    $.ajax({
        url: './handle_upload.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            updateUploadArea(response);
        }
    });
}

function submitPin(formdata){
    $('#confirm_pin').attr("disabled", true);
    $('#confirm_pin').addClass("waitingdisabled");
    $('#confirm_pin').text("Working...");

    $.ajax({
        url: './handle_pin.php',
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            populateMapInfo(response);
        }
    });
}


function updateUploadArea(response) {
    if (response.error) {
        $('#upload_wad').attr("disabled", false);
        $('#upload_wad').removeClass("waitingdisabled");
        $('#upload_wad').text("Upload WAD!");
        $("h1").text("Drag and drop here again");
        $('#upload_status').removeClass('success');
        $('#upload_status').addClass('fail');
        $('#upload_status').text(response.error);
    } else {
        $("h1").text("OK!");
        $('#upload_status').removeClass('fail');
        $('#upload_status').addClass('success');
        $("#upload_status").html(response.success);
        $('#upload_form').remove();
    }
}

function populateMapInfo(response) {
    if (response.error) {
        $('#confirm_pin').attr("disabled", false);
        $('#confirm_pin').removeClass("waitingdisabled");
        $('#confirm_pin').text("That's my PIN");
        $('#pin_status').removeClass('success');
        $('#pin_status').addClass('fail');
        $('#pin_status').text(response.error);
        $('#pin_status').show();
    } else {
        $("#upload-question-pin").hide();
        $("#upload-question-details").show();
        $("#input_map_name").val(response.mapname);
        $("#input_author_name").val(response.author);
        $("#input_music_credit").val(response.musiccredit);
        $('#input_map_jumpcrouch')[0].checked = response.jumpcrouch == 0 ? false : true;
        $('#input_map_wip')[0].checked = response.wip == 0 ? false : true;

        $('[name="input_map_category"]').removeAttr('checked');
        $('input[name="input_map_category"][value="' + response.category + '"]').prop('checked', true);

        $('[name="input_map_difficulty"]').removeAttr('checked');
        $('input[name="input_map_difficulty"][value="' + response.difficulty + '"]').prop('checked', true);

        $('[name="input_map_length"]').removeAttr('checked');
        $('input[name="input_map_length"][value="' + response.length + '"]').prop('checked', true);
        
        $("#input_monster_count").val(response.monsters);

        $("#upload_prompt").html('OK - alter your details and attach a new map here if you need to.');
        $("#upload_wad").text('Submit changes');
    }
}
