<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;

class AWSController extends Controller
{
    public function index() {
        return View::make('register');
    }

    public function login() {
        return View::make('login');
    }
}
