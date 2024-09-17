<?php

namespace App\Http\Controllers;

use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class TeamController extends Controller
{
    public function index()
    {
        try {
            $teams = Teams::all();
            return responseJson($teams, 200, 'Teams retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $team = Teams::find($id);

            if (!$team) {
                return responseJson(null, 404, 'Team not found');
            }

            return responseJson($team, 200, 'Team found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:5048',
            ]);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                $image->storeAs('public/images', $filename);
                $uploadedImages = config('app.url') . '/storage/images/' . $filename;
                $validated['image'] = $uploadedImages;
            }

            $team = Teams::create($validated);

            return responseJson($team, 201, 'Team created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $team = Teams::find($id);

            if (!$team) {
                return responseJson(null, 404, 'Team not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'position' => 'sometimes|required|string|max:255',
                'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:5048',
            ]);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                $image->storeAs('public/images', $filename);
                $uploadedImages = config('app.url') . '/storage/images/' . $filename;
                $validated['image'] = $uploadedImages;
            }

            $team->update($validated);

            return responseJson($team, 200, 'Team updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $team = Teams::find($id);

            if (!$team) {
                return responseJson(null, 404, 'Team not found');
            }

            $image = $team->image;

            if ($image) {
                $path = str_replace(config('app.url') . '/storage/', '', $image);
                $fullPath = storage_path('app/public/' . $path);

                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $team->delete();

            return responseJson(null, 200, 'Team deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
