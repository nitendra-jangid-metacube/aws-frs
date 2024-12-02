Webcam.set({
    width: 600,
    height: 460,
    image_format: 'jpeg',
    jpeg_quality: 90
});
Webcam.attach('#my_camera');

function reg_show_photo() {
    $('#save_data').val(0);
    return register();
}

function reg_submit_form() {
    $('#save_data').val(1);
    return register();
}

function register() {
    
    // take snapshot and get image data
    $('#reg_show_photo_btn').attr('disabled', '');
    $('#prev_btn').attr('disabled', '');
    $('#sub_btn').attr('disabled', '');
    $('#sub_btn').html('Please wait...');
    Webcam.snap(function(data_uri) {
        let formUrl = $('#frs_form').attr('action');
        Webcam.upload(data_uri, formUrl, function(code, text) {
            // console.log(text);

            $('#reg_show_photo_btn').removeAttr('disabled');
            $('#prev_btn').removeAttr('disabled');
            $('#sub_btn').removeAttr('disabled');
            $('#sub_btn').html('Submit');
            try{
                textObj = JSON.parse(text);
            } catch(e) {
                $.toast({
                    text: 'Something went wrong!!',
                    icon: 'error',
                    position: 'top-right',
                    hideAfter: 5000
                });
                return;
            }
            if (textObj.status) {
                $.toast({
                    text: textObj.message,
                    icon: 'success',
                    position: 'top-right',
                });
                if ($('#save_data').val() == 1) {
                    window.setTimeout(function() {
                        window.location.href = 'login';
                    }, 2000);
                } else {
                    document.getElementById('results').innerHTML =
                        '<img class="img-fluid" src="' + textObj.data.photo + '?var=' + (new Date().getTime()) + '" />';
                }
            } else {
                $.toast({
                    text: textObj.message,
                    icon: 'error',
                    position: 'top-right',
                    hideAfter: 5000
                });
            }
        }, 'frs_form');
    });
}

function next_step() {
    if ($('#first_name').val() == '') {
        alert('First Name is required');
        return;
    } else if ($('#mobile').val() == '') {
        alert('Mobile is required');
        return;
    } else {
        $('#p-info').addClass('d-none');
        $('#photo-info').removeClass('d-none');
    }
}

function previous_step() {
    $('#photo-info').addClass('d-none');
    $('#p-info').removeClass('d-none');
}