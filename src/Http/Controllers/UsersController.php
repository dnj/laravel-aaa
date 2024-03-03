<?php

namespace dnj\AAA\Http\Controllers;

use dnj\AAA\Contracts\IUserManager;
use dnj\AAA\Contracts\UserStatus;
use dnj\AAA\Http\Requests\UsersSearchRequest;
use dnj\AAA\Http\Requests\UserStoreRequest;
use dnj\AAA\Http\Requests\UserUpdateRequest;
use dnj\AAA\Http\Resources\UserCollection;
use dnj\AAA\Http\Resources\UserResource;
use dnj\AAA\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public function __construct(
        protected IUserManager $userManager
    ) {
    }

    public function index(UsersSearchRequest $request)
    {
        $users = User::query()
            ->filter($request->validated())
            ->userHasAccess(Auth::user())
            ->cursorPaginate();

        return UserCollection::make($users, true);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $data['status'] = isset($data['status']) ? UserStatus::from($data['status']) : UserStatus::ACTIVE;
        $usernames = array_keys($data['usernames']);
        $user = $this->userManager->store(
            name: $data['name'],
            username: $usernames[0],
            password: Hash::make($data['usernames'][$usernames[0]]),
            type: $data['type_id'],
            meta: $data['meta'] ?? [],
            userActivityLog: true
        );
        if (UserStatus::ACTIVE != $data['status']) {
            $user = $this->userManager->update(
                user: $user,
                changes: ['status' => $data['status']],
                userActivityLog: false
            );
        }
        $user->wasRecentlyCreated = true;

        return UserResource::make($user);
    }

    public function show(int $user)
    {
        $user = $this->userManager->findOrFail($user);
        $this->authorize('view', $user);

        return UserResource::make($user);
    }

    public function update(int $user, UserUpdateRequest $request)
    {
        $data = $request->validated();
        $user = $request->getTheUser();
        $this->authorize('update', $user);

        $changes = $data;
        if (isset($data['type_id'])) {
            $data['type'] = $data['type_id'];
            unset($data['type_id']);
        }
        $user = $this->userManager->update(
            user: $user,
            changes: $changes,
            userActivityLog: true,
        );

        return UserResource::make($user);
    }

    public function destroy(int $user)
    {
        $user = $this->userManager->findOrFail($user);
        $this->authorize('destroy', $user);
        $this->userManager->destroy(
            user: $user,
            userActivityLog: true
        );

        return response()->noContent();
    }
}
