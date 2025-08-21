<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\WalletSetting;

class CryptocurrencyService
{


    // CoinGecko API endpoint for price data
    private const COINGECKO_API = 'https://api.coingecko.com/api/v3/simple/price';

    /**
     * Get current cryptocurrency prices from CoinGecko API
     *
     * @return array
     */
    public function getCurrentPrices(): array
    {
        try {
            // Cache prices for 2 minutes to avoid hitting API limits
            return Cache::remember('crypto_prices', 120, function () {
                $response = Http::timeout(10)->get(self::COINGECKO_API, [
                    'ids' => 'bitcoin,ethereum,tether',
                    'vs_currencies' => 'usd'
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'bitcoin' => $data['bitcoin']['usd'] ?? 0,
                        'ethereum' => $data['ethereum']['usd'] ?? 0,
                        'usdt' => $data['tether']['usd'] ?? 1, // USDT should be ~$1
                    ];
                }

                // Fallback prices if API fails
                Log::warning('CoinGecko API failed, using fallback prices', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'bitcoin' => 45000,
                    'ethereum' => 3000,
                    'usdt' => 1,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to fetch cryptocurrency prices', [
                'error' => $e->getMessage()
            ]);

            // Return fallback prices
            return [
                'bitcoin' => 45000,
                'ethereum' => 3000,
                'usdt' => 1,
            ];
        }
    }

    /**
     * Convert USD amount to cryptocurrency amount
     *
     * @param float $usdAmount
     * @param string $cryptocurrency
     * @return array
     */
    public function convertUsdToCrypto(float $usdAmount, string $cryptocurrency): array
    {
        $prices = $this->getCurrentPrices();

        if (!isset($prices[$cryptocurrency]) || $prices[$cryptocurrency] <= 0) {
            throw new \Exception("Invalid cryptocurrency or price not available: {$cryptocurrency}");
        }

        $cryptoPrice = $prices[$cryptocurrency];
        $cryptoAmount = $usdAmount / $cryptoPrice;

        // Format crypto amount based on currency
        $formattedAmount = $this->formatCryptoAmount($cryptoAmount, $cryptocurrency);

        return [
            'crypto_amount' => $formattedAmount,
            'crypto_price_usd' => $cryptoPrice,
            'usd_amount' => $usdAmount,
            'currency' => $cryptocurrency,
            'currency_symbol' => $this->getCurrencySymbol($cryptocurrency),
            'currency_name' => $this->getCurrencyName($cryptocurrency),
        ];
    }

    /**
     * Get wallet address for a cryptocurrency
     *
     * @param string $cryptocurrency
     * @return string
     */
    public function getWalletAddress(string $cryptocurrency): string
    {
        $address = WalletSetting::getWalletAddress($cryptocurrency);

        if (!$address) {
            throw new \Exception("Wallet address not configured for: {$cryptocurrency}");
        }

        return $address;
    }

    /**
     * Generate QR code for cryptocurrency payment
     *
     * @param string $cryptocurrency
     * @param float $amount
     * @return string Base64 encoded QR code image
     */
    public function generatePaymentQrCode(string $cryptocurrency, float $amount): string
    {
        $address = $this->getWalletAddress($cryptocurrency);

        // Create payment URI based on cryptocurrency
        $paymentUri = $this->createPaymentUri($cryptocurrency, $address, $amount);

        // Generate QR code as base64 image
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($paymentUri);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Create payment URI for QR code
     *
     * @param string $cryptocurrency
     * @param string $address
     * @param float $amount
     * @return string
     */
    private function createPaymentUri(string $cryptocurrency, string $address, float $amount): string
    {
        switch ($cryptocurrency) {
            case 'bitcoin':
                return "bitcoin:{$address}?amount={$amount}";

            case 'ethereum':
                return "ethereum:{$address}?value=" . ($amount * 1e18); // Convert to Wei

            case 'usdt':
                // For USDT ERC-20, we use Ethereum format with contract address
                $usdtContract = '0xdAC17F958D2ee523a2206206994597C13D831ec7';
                return "ethereum:{$address}?contractAddress={$usdtContract}&value=" . ($amount * 1e6); // USDT has 6 decimals

            default:
                return $address; // Fallback to just the address
        }
    }

    /**
     * Format cryptocurrency amount with appropriate decimal places
     *
     * @param float $amount
     * @param string $cryptocurrency
     * @return string
     */
    private function formatCryptoAmount(float $amount, string $cryptocurrency): string
    {
        switch ($cryptocurrency) {
            case 'bitcoin':
                return number_format($amount, 8, '.', ''); // Bitcoin uses 8 decimal places

            case 'ethereum':
                return number_format($amount, 6, '.', ''); // Ethereum typically shown with 6 decimals

            case 'usdt':
                return number_format($amount, 2, '.', ''); // USDT typically shown with 2 decimals

            default:
                return number_format($amount, 8, '.', '');
        }
    }

    /**
     * Get currency symbol
     *
     * @param string $cryptocurrency
     * @return string
     */
    private function getCurrencySymbol(string $cryptocurrency): string
    {
        $symbols = [
            'bitcoin' => '₿',
            'ethereum' => 'Ξ',
            'usdt' => '₮',
        ];

        return $symbols[$cryptocurrency] ?? strtoupper($cryptocurrency);
    }

    /**
     * Get currency name
     *
     * @param string $cryptocurrency
     * @return string
     */
    private function getCurrencyName(string $cryptocurrency): string
    {
        $names = [
            'bitcoin' => 'Bitcoin',
            'ethereum' => 'Ethereum',
            'usdt' => 'USDT (ERC-20)',
        ];

        return $names[$cryptocurrency] ?? ucfirst($cryptocurrency);
    }

    /**
     * Get all supported cryptocurrencies with their details
     *
     * @return array
     */
    public function getSupportedCryptocurrencies(): array
    {
        $prices = $this->getCurrentPrices();
        $wallets = WalletSetting::getActiveWallets();
        $result = [];

        foreach ($wallets as $crypto => $wallet) {
            $result[$crypto] = [
                'name' => $wallet->currency_name,
                'symbol' => $wallet->currency_symbol,
                'code' => $wallet->currency_code,
                'price_usd' => $prices[$crypto] ?? 0,
                'icon' => $wallet->currency_symbol,
            ];
        }

        return $result;
    }

    /**
     * Validate cryptocurrency selection
     *
     * @param string $cryptocurrency
     * @return bool
     */
    public function isValidCryptocurrency(string $cryptocurrency): bool
    {
        return WalletSetting::where('cryptocurrency', $cryptocurrency)
            ->where('is_active', true)
            ->exists();
    }
}
