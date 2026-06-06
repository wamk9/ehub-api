<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\Notification as UserNotification;
use App\Models\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    public function create(Request $request)
    {
        try {
            //Validated
            $dataToGet = ['name', 'surname', 'mail', 'phone', 'username', 'password'];

            $validateUser = Validator::make($request->only($dataToGet),
            [
                'name'     => 'required',
                'surname'  => 'required',
                'mail'     => 'required|email|unique:users,mail',
                'phone'    => 'required|unique:users,phone',
                'username' => 'required|unique:users,username',
                'password' => 'required',
            ],
            [
                'name.required'     => 'user.name.required',
                'surname.required'  => 'user.surname.required',
                'mail.required'     => 'user.mail.required',
                'mail.email'        => 'user.mail.email',
                'mail.unique'       => 'user.mail.unique',
                'phone.required'    => 'user.phone.required',
                'phone.unique'      => 'user.phone.unique',
                'username.required' => 'user.username.required',
                'username.unique'   => 'user.username.unique',
                'password.required' => 'user.password.required',
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $mail = strtolower($request->mail);
            if (!Cache::get("ev_verified:{$mail}")) {
                return response()->json([
                    'status' => false,
                    'message' => 'E-mail não verificado.',
                ], 403);
            }

            Cache::forget("ev_verified:{$mail}");

            $result = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name'              => $request->name,
                    'surname'           => $request->surname,
                    'mail'              => $request->mail,
                    'phone'             => $request->phone,
                    'username'          => $request->username,
                    'password'          => Hash::make($request->password),
                    'email_verified_at' => now(),
                ]);

                if ($request->filled('image')) {
                    $path = storage_path('app/public/users/'.$user->username.'/');

                    if (!File::isDirectory($path))
                        File::makeDirectory($path, 0755, true, true);

                    Image::make($request->only('image')['image'])
                        ->resize(400, 400, function ($constraint) { $constraint->aspectRatio(); })
                        ->encode('webp', 90)
                        ->save($path.'/profile.webp');
                }

                return [
                    'user'  => $user,
                    'token' => $user->createToken("API TOKEN")->plainTextToken,
                ];
            });

            return response()->json([
                'message' => 'User Created Successfully',
                'token'   => $result['token'],
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getToken(Request $request)
    {
        return response()->json(['message' => auth()->user()->tokens->first(), 'status' => true], 200);
    }

    public function getNotifications()
    {
        if (!Auth::check())
            return response()->json(['message' => 'Unauthorized', 'status' => false], 401);

        $where_statement = [
            ['user_id', '=', auth()->user()->id]
        ];

        $notifications = UserNotification::where($where_statement)->get();

        return response()->json(['message' => $notifications, 'status' => true], 200);
    }

    public function setNotificationRead(Request $request)
    {
        if (!Auth::check())
            return response()->json(['message' => 'Unauthorized', 'status' => false], 401);

        if (!$request->route('id'))
            return response()->json(['message' => 'Notification id not sent', 'status' => false], 401);

        $where_statement = [
            ['id', '=', $request->route('id')],
            ['user_id', '=', auth()->user()->id]
        ];

        $notification = UserNotification::where($where_statement)->first();
        $notification->read_at = now()->toDateTimeString();
        $notification->save();

        return response()->json(['message' => 'Notification read', 'status' => true], 200);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();

        $imageUrl = null;
        $imagePath = storage_path('app/public/users/' . $user->username . '/profile.webp');
        if (File::exists($imagePath)) {
            $imageUrl = asset('storage/users/' . $user->username . '/profile.webp');
        }

        return response()->json([
            'name'              => $user->name,
            'surname'           => $user->surname,
            'username'          => $user->username,
            'mail'              => $user->mail,
            'phone'             => $user->phone,
            'image'             => $imageUrl,
            'email_verified_at' => $user->email_verified_at,
            'created_at'        => $user->created_at,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->only(['name', 'surname', 'phone', 'username', 'image']), [
            'name'     => 'sometimes|required|string|max:180',
            'surname'  => 'sometimes|required|string|max:180',
            'phone'    => 'sometimes|required|unique:users,phone,' . $user->id,
            'username' => 'sometimes|required|unique:users,username,' . $user->id,
            'image'    => 'sometimes|nullable',
        ], [
            'phone.unique'    => 'user.phone.unique',
            'username.unique' => 'user.username.unique',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'validation error', 'errors' => $validator->errors()], 422);
        }

        $data        = $validator->validated();
        $oldUsername = $user->username;
        $newUsername = $data['username'] ?? $oldUsername;

        if ($newUsername !== $oldUsername) {
            $oldPath = storage_path('app/public/users/' . $oldUsername);
            $newPath = storage_path('app/public/users/' . $newUsername);
            if (File::isDirectory($oldPath)) {
                File::moveDirectory($oldPath, $newPath);
            }
        }

        if ($request->filled('image')) {
            $path = storage_path('app/public/users/' . $newUsername . '/');
            if (!File::isDirectory($path))
                File::makeDirectory($path, 0755, true, true);

            Image::make($request->image)
                ->resize(400, 400, function ($constraint) { $constraint->aspectRatio(); })
                ->encode('webp', 90)
                ->save($path . 'profile.webp');
        }

        $user->fill(array_diff_key($data, ['image' => null]));
        $user->save();

        return response()->json([
            'message' => 'Profile updated',
            'user'    => $user->only(['name', 'surname', 'username', 'phone', 'mail']),
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'validation error', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password incorrect'], 403);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'validation error', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password incorrect'], 403);
        }

        $user->tokens()->delete();

        $imagePath = storage_path('app/public/users/' . $user->username);
        if (File::isDirectory($imagePath)) {
            File::deleteDirectory($imagePath);
        }

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
