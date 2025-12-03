<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinary
    ) {}

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
     *
     * Regra da foto:
     * - Se o front mandar uma foto nova (photoId diferente da atual) => deletar foto antiga no Cloudinary.
     * - Se não mandar foto ou mandar igual => não deleta nada.
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

        $oldPhotoId = $group->photo_id;

        // novos valores (se vierem no payload)
        $newPhotoId  = data_get($validated, 'photo.photoId', $group->photo_id);
        $newPhotoUrl = data_get($validated, 'photo.photoUrl', $group->photo_url);

        // se tinha foto antiga e o ID mudou => deleta a antiga
        if ($oldPhotoId && $oldPhotoId !== $newPhotoId) {
            $this->cloudinary->deleteImage($oldPhotoId);
        }

        $group->fill([
            'photo_id'    => $newPhotoId,
            'photo_url'   => $newPhotoUrl,
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
     * Aqui também podemos limpar a foto do Cloudinary, se existir.
     */
    public function destroy(Request $request, Group $group)
    {
        $this->authorizeGroup($request, $group);

        $photoId = $group->photo_id;

        // se o grupo tinha foto, apagamos ela também
        if ($photoId) {
            $this->cloudinary->deleteImage($photoId);
        }

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
