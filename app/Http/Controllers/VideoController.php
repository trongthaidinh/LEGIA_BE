<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Exception;

class VideoController extends Controller
{

    public function index()
    {
        try {
            $videos = Video::all();
            return responseJson($videos, 200, 'Videos retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $video = Video::find($id);

            if (!$video) {
                return responseJson(null, 404, 'Video not found');
            }

            return responseJson($video, 200, 'Video found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'required|url|max:255',
            ]);

            $newVideo = Video::create($validated);

            return responseJson($newVideo, 201, 'Video created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $video = Video::find($id);

            if (!$video) {
                return responseJson(null, 404, 'Video not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'url' => 'sometimes|required|url|max:255',
            ]);

            $video->update($validated);

            return responseJson($video, 200, 'Video updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            $video = Video::find($id);

            if (!$video) {
                return responseJson(null, 404, 'Video not found');
            }

            $video->delete();

            return responseJson(null, 200, 'Video deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
