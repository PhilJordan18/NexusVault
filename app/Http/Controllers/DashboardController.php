<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

final class DashboardController extends Controller
{
   public function index(): View { return view('dashboard.index'); }
}
