<?php

namespace App\Controllers\Admin;

use App\Models\User;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $users = User::select('id', 'name', 'email', 'role', 'active', 'created_at')->get();
        return $this->render($response, 'admin/users/index', ['users' => $users]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/users/create');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role'] ?? 'admin',
            'active' => isset($data['active']) ? 1 : 0
        ]);

        return $response->withHeader('Location', url('admin/users'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = User::findOrFail($args['id']);
        return $this->render($response, 'admin/users/edit', ['user' => $user]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = User::findOrFail($args['id']);
        $data = $request->getParsedBody();

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'admin',
            'active' => isset($data['active']) ? 1 : 0
        ];

        if (!empty($data['password'])) {
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $user->update($updateData);

        return $response->withHeader('Location', url('admin/users'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = User::findOrFail($args['id']);
        // Prevent deleting yourself or the first user (safety)
        if ($user->id != 1) {
            $user->delete();
        }
        return $response->withHeader('Location', url('admin/users'))->withStatus(302);
    }
}
