<?php

namespace App\Http\Controllers;

use App\Models\UserModel;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Get all the users from the database
    public function index()
    {
        $users = UserModel::all();

        if ($users->count() > 0) {
            return response()->json([
                'status' => 200,
                'users' => $users
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No records found!'
            ], 404);
        }
    }

    // Store users in the database
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:258',
            'email' => 'required|email|max:258',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'consent' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        } else if (!$request->consent && $request->hasFile('image')) {
            return response()->json([
                'status' => 400,
                'errors' => "Consent must be true when uploading an image!"
            ], 400);
        } else {
            $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();

            $user = UserModel::create([
                'name' => $request->name,
                'email' => $request->email,
                'image' => $imageName,
                'consent' => $request->consent
            ]);

            if ($user) {
                Storage::disk('public')->put($imageName, file_get_contents($request->image));

                return response()->json([
                    'status' => 200,
                    'message' => 'User created successfully!'
                ], 200);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Something went wrong!'
                ], 500);
            }
        }
    }

    // Search for a user based on his "id"
    public function show($id)
    {
        $user = UserModel::find($id);

        if ($user) {
            return response()->json([
                'status' => 200,
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No such user found!'
            ], 404);
        }
    }

    // Update a user based on his "id"
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:258',
            'email' => 'required|email|max:258',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'consent' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        } else if (!$request->consent && $request->hasFile('image')) {
            return response()->json([
                'status' => 400,
                'errors' => "Consent must be true when uploading an image!"
            ], 400);
        } else {
            $user = UserModel::find($id);

            if ($user) {
                $user->name = $request->name;
                $user->email = $request->email;
                $user->consent = $request->consent;

                if ($request->image) {
                    $storage = Storage::disk('public');

                    if ($storage->exists($user->image))
                        $storage->delete($user->image);

                    $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
                    $user->image = $imageName;

                    $storage->put($imageName, file_get_contents($request->image));
                }

                $user->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'User updated successfully!'
                ], 200);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'No such user found!'
                ], 404);
            }
        }
    }

    // Delete a user based on his "id"
    public function destroy($id)
    {
        $user = UserModel::find($id);

        if ($user) {
            $storage = Storage::disk('public');

            if ($storage->exists($user->image))
                $storage->delete($user->image);

            $user->delete();

            return response()->json([
                "status" => 200,
                'message' => "User deleted successfully!"
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No such user found!'
            ], 404);
        }
    }
}
