<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Http\Requests\AppSettingRequest; use App\Models\AppSetting;
class AppSettingController extends Controller { public function index(){return response()->json(AppSetting::query()->pluck('value','key'));} public function update(AppSettingRequest $r){foreach($r->validated()['settings'] as $key=>$value){AppSetting::updateOrCreate(['key'=>$key],['value'=>$value]);} return response()->json(AppSetting::query()->pluck('value','key'));}}
