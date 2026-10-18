<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Color;
use Toastr;

class ColorController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:color-list|color-create|color-edit|color-delete', ['only' => ['index','show']]);
         $this->middleware('permission:color-create', ['only' => ['create','store']]);
         $this->middleware('permission:color-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:color-delete', ['only' => ['destroy']]);
         $this->middleware('permission:color-create|color-edit', ['only' => ['quickSave']]);
    }

    /**
     * প্রোডাক্ট ফর্মের ভ্যারিয়েন্ট সেকশন থেকে AJAX এ কালার তৈরি / এডিট
     */
    public function quickSave(Request $request)
    {
        $data = $request->validate([
            'id'        => 'nullable|integer|exists:colors,id',
            'colorName' => 'required|string|max:100',
            'color'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $permission = !empty($data['id']) ? 'color-edit' : 'color-create';
        if (!auth()->user()->can($permission)) {
            return response()->json(['success' => false, 'message' => 'এই কাজের অনুমতি নেই'], 403);
        }

        $color = !empty($data['id']) ? Color::findOrFail($data['id']) : new Color(['status' => 1]);
        $color->fill(['colorName' => $data['colorName'], 'color' => $data['color']])->save();

        return response()->json([
            'success' => true,
            'color'   => ['id' => $color->id, 'name' => $color->colorName, 'hex' => $color->color],
        ]);
    }
    public function index(Request $request)
    {
        $show_data = Color::orderBy('colorName','ASC')->get();
        return view('backEnd.color.index',compact('show_data'));
    }
    public function create()
    {
        return view('backEnd.color.create');
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'status' => 'required',
        ]);
        
        $input = $request->all();
        
        Color::create($input);        
        
        Toastr::success('Success','Data insert successfully');
        return redirect()->route('colors.index');
    }
    
    public function edit($id)
    {
        $edit_data = Color::find($id);
        return view('backEnd.color.edit',compact('edit_data'));
    }
    
    public function update(Request $request)
    { 
        $this->validate($request, [
            'status' => 'required',
        ]);
        // image one
        $update_data = Color::find($request->id);
        $input = $request->all();
        $update_data->update($input);

        Toastr::success('Success','Data update successfully');
        return redirect()->route('colors.index');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Color::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Color::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {       
        $delete_data = Color::find($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
}
