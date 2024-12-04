@extends('layouts.default')
@section('content')
<div class="login-form">
    <form action="{{route('registerUser')}}" id="frs_form" onsubmit="return false;">
        @csrf
        <div class="col-12">
            <div id="p-info">
                <h5 class="text-left">Enter your details</h5>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <!-- <label for="first_name">First Name</label> -->
                            <input type="text" class="input-field" placeholder="First Name" name="first_name"
                                id="first_name">
                        </div>
                        <div class="form-group">
                            <!-- <label for="last_name">Last Name</label> -->
                            <input type="text" class="input-field" placeholder="Last Name" name="last_name"
                                id="last_name">
                        </div>
                        <div class="form-group">
                            <!-- <label for="mobile">Mobile</label> -->
                            <input type="text" class="input-field" placeholder="Mobile" name="mobile"
                                id="mobile">
                        </div>
                        <div class="form-group">
                            <!-- <label for="email">Email</label> -->
                            <input type="text" class="input-field" placeholder="Email" name="email"
                                id="email">
                        </div>
                    </div>
                    <div class="col-12 text-center">
                        <button class="btn btn-primary mt-3" onclick="return next_step()">Next</button>
                    </div>
                </div>
            </div>
            <div id="photo-info" class="d-none">
                <h5 class="text-left">Face Recognition</h5>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div id="my_camera" class="border"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div id="results" class="border">
                            <img src="images/defaul-img.jpg" class="w-100 default-img">
                        </div>
                    </div>
                    <div class="col-12" id="user-snaps-div">
                        @if(!empty(session('user_photo')))
                            @foreach(session('user_photo') as $photoPath)
                                <img src="{{url($photoPath)}}" class="img-thumbnail register-snaps" />
                            @endforeach
                        @endif
                    </div>
                    <div class="col-12 text-center mt-3">
                        <input type="hidden" name="save_data" id="save_data" value="1">
                        <button type="button" id="reg_show_photo_btn" class="btn btn-secondary" onClick="reg_show_photo()">Show Photo</button>
                        <button type="button" id="prev_btn" class="btn btn-primary" onclick="return previous_step()">Previous</button>
                        <button type="button" id="sub_btn" class="btn btn-success" onClick="reg_submit_form()">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection