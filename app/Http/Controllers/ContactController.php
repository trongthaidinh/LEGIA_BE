<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Contacts;
use Illuminate\Http\Request;
use Exception;

class ContactController extends Controller
{
    public function index()
    {
        try {
            $contacts = Contacts::all();
            return responseJson($contacts, 200, 'Contacts retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $contact = Contacts::find($id);

            if (!$contact) {
                return responseJson(null, 404, 'Contact not found');
            }

            return responseJson($contact, 200, 'Contact found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'email'   => 'required|email|max:255',
                'phone'   => 'required|string|max:20',
                'title'   => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $newContact = Contacts::create($validated);

            return responseJson($newContact, 201, 'Contact created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $contact = Contacts::find($id);

            if (!$contact) {
                return responseJson(null, 404, 'Contact not found');
            }

            $contact->delete();

            return responseJson(null, 200, 'Contact deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
