<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::latest()->paginate(15), 'roles' => User::ROLE_LABELS]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(), 'roles' => User::ROLE_LABELS, 'modules' => User::MODULE_LABELS, 'selectedPermissions' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['permissions'] = $data['permissions'] ?? User::defaultPermissions($data['role']);
        $data['active'] = $request->boolean('active');
        User::create($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuário criado e permissões definidas.');
    }

    public function edit(User $usuario): View
    {
        return view('users.form', ['user' => $usuario, 'roles' => User::ROLE_LABELS, 'modules' => User::MODULE_LABELS, 'selectedPermissions' => $usuario->effectivePermissions()]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $data = $this->validated($request, $usuario);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $data['permissions'] = $data['permissions'] ?? [];
        $data['active'] = $request->boolean('active');

        if ($usuario->is(auth()->user()) && ! $data['active']) {
            return back()->withErrors(['active' => 'Você não pode desativar sua própria conta.']);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuário e permissões atualizados.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
            'active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(User::MODULE_LABELS))],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
        ]);
    }
}
