Webcam.set({
    width: 600,
    height: 460,
    image_format: 'jpeg',
    jpeg_quality: 90
});
Webcam.attach('#my_camera');

function show_photo() {
    $('#save_data').val(0);
    return take_snapshot();
}

function login() {
    $('#save_data').val(1);
    return take_snapshot();
}

function take_snapshot() {
    if ($('#mobile').val() == '') {
        alert('Mobile is required');
        return;
    }
    // take snapshot and get image data
    $('#show_photo_btn').attr('disabled', '');
    $('#sub_btn').attr('disabled', '');
    $('#sub_btn').html('Please wait...');
    Webcam.snap(function(data_uri) {
        Webcam.upload(data_uri, 'login.php', function(code, text) {
            console.log(text);

            $('#show_photo_btn').removeAttr('disabled');
            $('#sub_btn').removeAttr('disabled');
            $('#sub_btn').html('Submit');
            textObj = JSON.parse(text);
            if (textObj.status) {
                $.toast({
                    text: textObj.message,
                    icon: 'success',
                    position: 'top-right',
                });
                if ($('#save_data').val() == 1) {
                    location.href = 'welcome.php';
                } else {
                    document.getElementById('results').innerHTML =
                        '<img class="img-fluid" src="' + textObj.data.photo + '?var=' + (new Date().getTime()) + '"/>';
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