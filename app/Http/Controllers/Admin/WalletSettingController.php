<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletSetting;
use Illuminate\Http\Request;

class WalletSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $wallets = WalletSetting::orderBy('cryptocurrency')->get();
        return view('admin.wallet-settings.index', compact('wallets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.wallet-settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cryptocurrency' => 'required|string|unique:wallet_settings,cryptocurrency',
            'wallet_address' => 'required|string',
            'currency_name' => 'required|string|max:255',
            'currency_symbol' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
            'is_active' => 'boolean',
        ]);

        WalletSetting::create($request->all());

        return redirect()->route('admin.wallet-settings.index')
            ->with('success', 'Wallet setting created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WalletSetting $walletSetting)
    {
        return view('admin.wallet-settings.edit', compact('walletSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WalletSetting $walletSetting)
    {
        $request->validate([
            'wallet_address' => 'required|string',
            'currency_name' => 'required|string|max:255',
            'currency_symbol' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
            'is_active' => 'boolean',
        ]);

        $walletSetting->update($request->all());

        return redirect()->route('admin.wallet-settings.index')
            ->with('success', 'Wallet setting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WalletSetting $walletSetting)
    {
        $walletSetting->delete();

        return redirect()->route('admin.wallet-settings.index')
            ->with('success', 'Wallet setting deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(WalletSetting $walletSetting)
    {
        $walletSetting->update(['is_active' => !$walletSetting->is_active]);

        $status = $walletSetting->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.wallet-settings.index')
            ->with('success', "Wallet setting {$status} successfully.");
    }
}
