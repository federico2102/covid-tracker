@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h1 class="mb-4">User Management</h1>

        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered">
                <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone_number ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $user->is_admin ? 'bg-success' : 'bg-secondary' }}">
                                {{ $user->is_admin ? 'Admin' : 'User' }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('users.update.role', $user->id) }}" method="POST">
                                @csrf
                                @method('PUT') <!-- Spoof the method as PUT -->
                                <input type="hidden" name="is_admin" value="{{ $user->is_admin ? 0 : 1 }}">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="adminCheck{{ $user->id }}" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }} onchange="this.form.submit()">
                                    <label class="form-check-label" for="adminCheck{{ $user->id }}">
                                        {{ $user->is_admin ? 'Revoke Admin' : 'Grant Admin' }}
                                    </label>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
