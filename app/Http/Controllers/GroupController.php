<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Listar grupos do usuário logado.
     */
    public function index(Request $request)
    {
        $groups = Group::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'groups' => $groups,
        ]);
    }

    /**
     * Criar grupo (payload com "photo", "name", "description", "state", "city").
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'photo.photoId'  => 'nullable|string',
            'photo.photoUrl' => 'nullable|url',

            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'state'       => 'required|string|max:2',
            'city'        => 'required|string|max:255',
        ]);

        $group = Group::create([
            'user_id'     => $request->user()->id,
            'photo_id'    => data_get($validated, 'photo.photoId'),
            'photo_url'   => data_get($validated, 'photo.photoUrl'),
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'state'       => $validated['state'],
            'city'        => $validated['city'],
        ]);

        return response()->json([
            'group' => $group,
        ], 201);
    }

    /**
     * Mostrar um grupo específico.
     */
    public function show(Request $request, Group $group)
    {
        $this->authorizeGroup($request, $group);

        return response()->json([
            'group' => $group,
        ]);
    }

    /**
     * Atualizar um grupo.
     */
    public function update(Request $request, Group $group)
    {
        $this->authorizeGroup($request, $group);

        $validated = $request->validate([
            'photo.photoId'  => 'nullable|string',
            'photo.photoUrl' => 'nullable|url',

            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'state'       => 'sometimes|required|string|max:2',
            'city'        => 'sometimes|required|string|max:255',
        ]);

        $group->fill([
            'photo_id'    => data_get($validated, 'photo.photoId', $group->photo_id),
            'photo_url'   => data_get($validated, 'photo.photoUrl', $group->photo_url),
            'name'        => $validated['name']        ?? $group->name,
            'description' => $validated['description'] ?? $group->description,
            'state'       => $validated['state']       ?? $group->state,
            'city'        => $validated['city']        ?? $group->city,
        ]);

        $group->save();

        return response()->json([
            'group' => $group,
        ]);
    }

    /**
     * Deletar um grupo.
     * (Aqui a gente SÓ apaga do banco; remoção da imagem no Cloudinary
     * pode ser feita antes chamando /api/photos/delete, se o front quiser.)
     */
    public function destroy(Request $request, Group $group)
    {
        $this->authorizeGroup($request, $group);

        $group->delete();

        return response()->json([
            'message' => 'Grupo removido com sucesso.',
        ]);
    }

    /**
     * Garantir que o grupo pertence ao usuário autenticado.
     */
    protected function authorizeGroup(Request $request, Group $group): void
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403, 'Você não tem permissão para acessar este grupo.');
        }
    }
}
