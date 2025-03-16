<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\Setting;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function modules()
    {
        $setting = Setting::first();
        return view('Dashboard.settings.modules', compact('setting'));
    }

    public function updateModules(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'hr_module' => 'required|boolean',
                'manufacturing_module' => 'required|boolean',
            ], [
                'hr_module.required' => trans('admin.HR Module is required'),
                'hr_module.boolean' => trans('admin.HR Module must be a boolean'),
                'manufacturing_module.required' => trans('admin.Manufacturing Module is required'),
                'manufacturing_module.boolean' => trans('admin.Manufacturing Module must be a boolean'),
            ]);

            $setting = Setting::first();
            $wagesAndSalariesCategory = ExpenseCategory::where('name', 'اجور ومرتبات')->first();

            // Handle HR module enable/disable
            if ($validatedData['hr_module']) {
                if (!$wagesAndSalariesCategory) {
                    $wagesAndSalariesCategory = new ExpenseCategory();
                    $wagesAndSalariesCategory->name = 'اجور ومرتبات';
                    $wagesAndSalariesCategory->save();
                }
            } else {
                // Remove the wages and salaries object when HR module is disabled
                if ($wagesAndSalariesCategory) {
                    $wagesAndSalariesCategory->delete();
                }
            }

            // Update settings
            $setting->update([
                'hr_module' => $validatedData['hr_module'],
                'manufacturing_module' => $validatedData['manufacturing_module'],
            ]);

            return redirect()->back()->with('success', trans('admin.module_settings_updated_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', trans('admin.an_error_occurred') . ': ' . $e->getMessage());
        }
    }
}
