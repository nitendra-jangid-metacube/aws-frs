<!DOCTYPE html>
<html lang="en">

<head>
    <title>FRS</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="{{asset('assets/css/images/fav.ico')}}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{asset('assets/css/jquery.toast.css')}}" rel="stylesheet">
    <link href="{{asset('assets/css/custom.css')}}" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{asset('assets/scripts/jquery.toast.js')}}"></script>
</head>

<body>
<div class="container">
    <div class="row text-center">
        <?php
        if (!empty(session('current_user'))) {
            $user = session('current_user');
        ?>
            <div class="col-12">
                <img src="<?php echo $user['photo']; ?>" class="img-thumbnail w-25">
            </div>
            <div class="col-12">
                <h1 class="text-light">Welcome <?php echo $user['first_name'] . (empty($user['last_name']) ? '' : ' ' . $user['last_name']); ?></h1>
            </div>
            <div class="col-12">
                <a href="?logout=1" class="btn btn-primary">Logout</a>
            </div>
        <?php
        } else {
        ?>
            <div class="col-12">
                <h3 class="text-light">You are not logged in</h3>
            </div>
            <div class="col-12">
                <a href="{{route('login')}}" class="btn btn-primary">Log-In</a>
            </div>
        <?php
        }
        ?>

    </div>
</div>
</body>

</html>