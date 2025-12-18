<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Interfaces\UserRepo;
use Illuminate\Http\Request;

class UserApiController extends Controller {
    public function __construct(private UserRepo $repo) {}

    public function all() {
        $lista = $this->repo->all();
        return response()->json(['users' => $lista]);
    }

    public function get(Request $request) {
        try {
            $id = filter_var($request->route('id'), FILTER_VALIDATE_INT);
            $item = $this->repo->get($id);
            return response()->json(['user' => $item]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function add(Request $request) {
        try {
            $user = User::factory()->make($request->input());
            $this->repo->add($user);
            return response()->json(['user' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function update(Request $request) {
        $me = $request->user();
        
        try {
            
            $id = filter_var($request->route('id'), FILTER_VALIDATE_INT);
            $user = $this->repo->get($id);
            $admin = $user->admin;
            $user->fill($request->all());
           
            if (
              ($me->id !== $id && !$me->admin) || //editing user is not the same user that is being edited AND the editor is not admin
              ($user->admin != $admin && $me->id == $id) || //If the admin stage have changed AND the user is editing themselves
              ($user->admin != $admin && !$me->admin) //Admin status of a user is changed AND the editor is not admin
            ) {
                return response()->json(['error' => 'forbidden'], 403);
                //check that id not changed
            } else if ($user->id !== $id) {
                return response()->json(['error' => 'forbidden. You cannot change id'], 403);
            }

            $this->repo->update($user);
            return response()->json(['user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function remove(Request $request) {
        $me = $request->user();
        //admin required to delete a user
        if (!$me->admin) {
            return response()->json(['error' => "unautorized, not admin"], 401);
        }
        try {
            $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
            //only admin can get in here( stop admin from deleting themselves)
            if ($me->id === $id) {
                return response()->json(['error' => 'forbidden, admin can not remove themselves'], 403);
            }
            $this->repo->delete($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }
}
