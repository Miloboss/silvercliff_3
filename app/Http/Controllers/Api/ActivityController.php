<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Activity;

class ActivityController extends Controller
{
    public function index()
    {
        return response()->json(Activity::where('is_active', true)->get());
    }
}
