<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Delete Stripe Connect Account — {{ config('app.name') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen">
  <div class="max-w-lg mx-auto px-4 py-12">
    <div class="bg-white shadow-md rounded-xl p-6 md:p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">Delete Stripe Connect account</h1>
      <p class="text-sm text-gray-600 mb-6">
        Dev tool — permanently deletes a connected account in Stripe.
        Test-mode accounts can always be deleted.
      </p>

      @if (! $stripeConfigured)
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm mb-6">
          Stripe secret key is not configured on this server.
        </div>
      @endif

      @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-900 px-4 py-3 text-sm mb-6">
          {{ session('success') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm mb-6">
          <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('stripe.delete-account.destroy') }}" class="space-y-5">
        @csrf

        <div>
          <label for="stripe_account_id" class="block text-sm font-semibold text-gray-800 mb-2">
            Stripe account ID
          </label>
          <input
            type="text"
            id="stripe_account_id"
            name="stripe_account_id"
            value="{{ old('stripe_account_id') }}"
            placeholder="acct_1234567890"
            required
            pattern="acct_[a-zA-Z0-9]+"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            autocomplete="off"
          >
          <p class="text-xs text-gray-500 mt-2">Find IDs in the Stripe Dashboard → Connect → Accounts.</p>
        </div>

        @if ($requiresToken)
          <div>
            <label for="dev_token" class="block text-sm font-semibold text-gray-800 mb-2">
              Dev token
            </label>
            <input
              type="password"
              id="dev_token"
              name="dev_token"
              required
              class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"
              autocomplete="off"
            >
            <p class="text-xs text-gray-500 mt-2">Set <code class="text-xs bg-gray-100 px-1 rounded">STRIPE_CONNECT_DEV_DELETE_TOKEN</code> in .env.</p>
          </div>
        @endif

        <label class="flex items-start gap-3 cursor-pointer">
          <input
            type="checkbox"
            name="clear_local"
            value="1"
            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            @checked(old('clear_local', true))
          >
          <span class="text-sm text-gray-700">
            Also clear this account ID (and payout bank country) from InkJin <code class="text-xs bg-gray-100 px-1 rounded">user_details</code>.
          </span>
        </label>

        <button
          type="submit"
          @disabled(! $stripeConfigured)
          class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-red-600 text-white font-semibold py-3 px-4 text-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
          onclick="return confirm('Delete this Stripe connected account permanently?');"
        >
          Delete account
        </button>
      </form>

      <p class="text-xs text-gray-500 mt-6">
        Stripe Dashboard:
        <a href="https://dashboard.stripe.com/test/connect/accounts/overview" class="text-indigo-600 hover:underline" target="_blank" rel="noopener">Test accounts</a>
        ·
        <a href="https://dashboard.stripe.com/connect/accounts/overview" class="text-indigo-600 hover:underline" target="_blank" rel="noopener">Live accounts</a>
      </p>
    </div>
  </div>
</body>
</html>
