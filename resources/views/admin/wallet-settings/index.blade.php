@extends('layouts.admin')

@section('title', 'Wallet Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Cryptocurrency Wallet Settings</h1>
        <a href="{{ route('admin.wallet-settings.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Wallet
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Cryptocurrency</th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Code</th>
                            <th>Wallet Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallets as $wallet)
                        <tr>
                            <td>
                                <span class="badge bg-primary">{{ ucfirst($wallet->cryptocurrency) }}</span>
                            </td>
                            <td>{{ $wallet->currency_name }}</td>
                            <td>
                                <span style="font-size: 1.2em;">{{ $wallet->currency_symbol }}</span>
                            </td>
                            <td>{{ $wallet->currency_code }}</td>
                            <td>
                                <code class="small">{{ Str::limit($wallet->wallet_address, 30) }}</code>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $wallet->wallet_address }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.wallet-settings.toggle', $wallet) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $wallet->is_active ? 'success' : 'secondary' }}">
                                        {{ $wallet->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.wallet-settings.edit', $wallet) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.wallet-settings.destroy', $wallet) }}" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this wallet setting?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No wallet settings found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    Wallet address copied to clipboard!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            setTimeout(() => toast.remove(), 3000);
        });
    }
</script>
@endsection