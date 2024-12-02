@extends('layouts.default')
@section('content')
<div class="login-form row">
    <form action="" id="frs_form" onsubmit="return false;">
        <div class="col-12">
            <!-- <h5 class="text-left">Login</h5> -->
            <div class="row">
                <div class="col-6 text-left">
                    <div class="form-group">
                        <label for="mobile">Enter mobile</label>
                        <input type="text" class="input-field" placeholder="Mobile.." name="mobile"
                            id="mobile">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <div id="my_camera" class="border"></div>
                </div>
                <div class="col-12 col-md-6">
                    <div id="results" class="border">
                        <img src="images/defaul-img.jpg" class="w-100 default-img">
                    </div>
                </div>
                <div class="col-12 text-center mt-3">
                    <input type="hidden" name="save_data" id="save_data" value="1">
                    <button type="button" id="login_show_photo_btn" class="btn btn-secondary" onClick="login_show_photo()">Show Photo</button>
                    <button type="button" id="sub_btn" class="btn btn-success" onClick="login_submit_form()">Submit</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection