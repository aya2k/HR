<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Traits\ApiResponder;

class SettingController extends Controller
{
   public function index()
    {
        $setting = Setting::first();
        return response()->json($setting);
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $setting = Setting::first() ?? new Setting();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $setting->logo = $path;
        }

        $setting->save();

        return response()->json([
            'message' => 'Logo updated successfully ✅',
            'data' => $setting,
        ]);
    }

    
    public function destroy()
    {
        $setting = Setting::first();
        if ($setting && $setting->logo) {
            // \Storage::disk('public')->delete($setting->logo);
            $setting->logo = null;
            $setting->save();
        }

        return response()->json(['message' => 'Logo deleted successfully ✅']);
    }
}
