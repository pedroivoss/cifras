<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Contas do sistema: só administradores cadastram, editam e ativam/desativam. Não há exclusão.
 * Ninguém altera o próprio acesso, então sempre resta pelo menos um administrador ativo.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('playlists')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.addcslashes($request->input('q'), '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('username', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $request->input('q')]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->userAttributes());

        return redirect()->route('admin.users.index')->with('success', "Conta de {$user->name} criada.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->userAttributes());

        return redirect()->route('admin.users.index')->with('success', 'Conta atualizada.');
    }

    /** Ativa ou desativa. Desativada, a pessoa perde a sessão aberta e seus links públicos param de abrir. */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Você não pode desativar a própria conta.']);
        }

        DB::transaction(function () use ($user) {
            $user->update(['is_active' => ! $user->is_active]);

            if (! $user->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        return back()->with('success', $user->is_active ? "{$user->name} foi ativado(a)." : "{$user->name} foi desativado(a).");
    }
}
