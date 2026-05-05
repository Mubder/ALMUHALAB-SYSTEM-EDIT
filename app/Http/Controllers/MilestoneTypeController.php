<?php

namespace App\Http\Controllers;

use App\Models\MilestoneType;
use Illuminate\Http\Request;

class MilestoneTypeController extends Controller
{
    public function index()
    {
        $milestones = MilestoneType::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.milestone_types.index', compact('milestones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'        => 'required|string|max:60|unique:milestone_types,key|regex:/^[a-z0-9_]+$/',
            'label'      => 'required|string|max:100',
            'icon'       => 'required|string|max:80',
            'color'      => 'required|in:' . implode(',', array_keys(MilestoneType::COLORS)),
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? MilestoneType::max('sort_order') + 1;
        $data['is_active']  = true;

        MilestoneType::create($data);

        return back()->with('success', 'Milestone "' . $data['label'] . '" created.');
    }

    public function update(Request $request, MilestoneType $milestoneType)
    {
        $data = $request->validate([
            'label'      => 'required|string|max:100',
            'icon'       => 'required|string|max:80',
            'color'      => 'required|in:' . implode(',', array_keys(MilestoneType::COLORS)),
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? $milestoneType->sort_order;

        $milestoneType->update($data);

        return back()->with('success', 'Milestone updated.');
    }

    public function destroy(MilestoneType $milestoneType)
    {
        $milestoneType->delete();
        return back()->with('success', 'Milestone deleted.');
    }
}
