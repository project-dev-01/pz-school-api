$user = auth()->user(); $oldPassword = $request->input('old_password'); $newPassword = $request->input('password'); if (Hash::check($oldPassword, $user->password)) { $user->password = Hash::make($newPassword); $user->save(); return redirect()->route('home')->with('success', 'Password changed successfully!'); } else { throw ValidationException::withMessages(['old_password' => 'Old password is incorrect.']); }