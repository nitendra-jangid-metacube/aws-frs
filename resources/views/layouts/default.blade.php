<!DOCTYPE html>
<html lang="en">
@include('includes.head')

<body>
    <div class="login-container">
        @include('includes.nav')
        @yield('content')
    </div>
</body>
@include('includes.footer_scritps')

</html>