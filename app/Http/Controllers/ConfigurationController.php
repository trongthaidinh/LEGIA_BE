<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Exception;

class ConfigurationController extends Controller
{
    public function index()
    {
        try {
            $configuration = Configuration::all();
            return responseJson($configuration, 200);
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $configuration = Configuration::find($id);

            if (!$configuration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            return responseJson($configuration, 200);
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'homepage_slider' => 'nullable|json',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $configuration = Configuration::create($validated);

            return responseJson($configuration, 201, 'Configuration created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $configuration = Configuration::find($id);

            if (!$configuration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'homepage_slider' => 'nullable|json',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $configuration->update($validated);

            return responseJson($configuration, 200, 'Configuration updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $configuration = Configuration::find($id);

            if (!$configuration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            $configuration->delete();

            return responseJson(null, 200, 'Configuration deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
