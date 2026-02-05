<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SiteSetting;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(SiteSetting::all()->pluck('value', 'key'));
    }
}
