<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class VillageController extends Controller
{
    //
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Village::query();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="btn-group"><button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';

                    if (auth('user')->user()->has_permission('update-villages'))
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.villages.edit', $row->id) . '">' . trans('admin.Edit') . '</a>';

                    if (auth('user')->user()->has_permission('delete-villages'))
                        $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('dashboard.villages.destroy', $row->id) . '">' . trans('admin.Delete') . '</a>';

                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('name', function ($row) {
                    return $row->name_ar;
                })
                ->addColumn('city', function ($row) {
                    return $row->city->city_name_ar;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('Dashboard.villages.index');
    }

    public function create()
    {
        $cities = City::all();
        return view('Dashboard.villages.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'city_id' => 'required|exists:cities,id',
        ]);
        $village = new Village();
        $village->name_ar = $request->name_ar;
        $village->name_en = $request->name_en;
        $village->city_id = $request->city_id;
        $village->save();
        return redirect()->route('dashboard.villages.index')->with('success', 'تم اضافة القرية بنجاح');
    }

    public function edit($id)
    {
        $village = Village::findOrFail($id);
        $cities = City::all();
        return view('Dashboard.villages.edit', compact('village', 'cities'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'city_id' => 'required|exists:cities,id',
        ]);
        $village = Village::findOrFail($id);
        $village->name_ar = $request->name_ar;
        $village->name_en = $request->name_en;
        $village->city_id = $request->city_id;
        $village->save();
        return redirect()->route('dashboard.villages.index')->with('success', 'تم تعديل القرية بنجاح');
    }

    public function destroy($id)
    {
        $village = Village::findOrFail($id);
        $village->delete();
        return redirect()->route('dashboard.villages.index')->with('success', 'تم حذف القرية بنجاح');
    }

    public function getVillagesBasedOnCity(Request $request)
    {
        $city_id = $request->city_id;
        $villages = Village::where('city_id', $city_id)->get();
        return response()->json($villages);
    }
}
