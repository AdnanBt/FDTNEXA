<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Layer Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased pb-12" x-data="{ showPaywall: false }">

    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span class="text-lg font-bold tracking-tight text-gray-900">Bulk Generator (Pro)</span>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 mt-8">
        
        <!-- GENERATOR FORM (Visible to everyone) -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="bg-indigo-600 px-6 py-4 border-b border-indigo-700">
                <h1 class="text-xl font-bold text-white">Generate Multiple Layers</h1>
                <p class="text-indigo-100 text-sm mt-1">Upload your master file and set the targets.</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm mx-6 mt-6">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm mx-6 mt-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Form (Bypassed paywall for testing) -->
            <form action="{{ route('bulk.generate') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8">
                @csrf
                
                <div class="space-y-6">
                    <!-- File Upload -->
                    <div>
                        <label for="master_file" class="block text-sm font-medium text-gray-700 mb-2">Master Excel File (Layer 1 Template)</label>
                        <input type="file" id="master_file" name="master_file" accept=".xlsx" required class="w-full text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2 bg-gray-50">
                        <p class="text-xs text-gray-500 mt-1">Must contain the formulas and formatting for your checklist.</p>
                    </div>

                    <!-- Settings Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 border-t pt-6">
                        <div>
                            <label for="total_layers" class="block text-sm font-medium text-gray-700 mb-1">Total Layers</label>
                            <input type="number" id="total_layers" name="total_layers" value="6" min="2" max="30" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-mono text-lg" required>
                        </div>
                        <div>
                            <label for="start_comp" class="block text-sm font-medium text-gray-700 mb-1">Start Compaction (%)</label>
                            <input type="number" step="0.1" id="start_comp" name="start_comp" value="95.2" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-mono text-lg" required>
                        </div>
                        <div>
                            <label for="step_comp" class="block text-sm font-medium text-gray-700 mb-1">Compaction Step (%)</label>
                            <input type="number" step="0.1" id="step_comp" name="step_comp" value="0.3" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-mono text-lg" required>
                        </div>
                        <div>
                            <label for="start_moist" class="block text-sm font-medium text-gray-700 mb-1">Start Moisture (%)</label>
                            <input type="number" step="0.1" id="start_moist" name="start_moist" value="8.0" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-mono text-lg" required>
                        </div>
                        <div>
                            <label for="step_moist" class="block text-sm font-medium text-gray-700 mb-1">Moisture Step (%)</label>
                            <input type="number" step="0.1" id="step_moist" name="step_moist" value="0.3" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-mono text-lg" required>
                        </div>
                    </div>

                    <!-- Date Options -->
                    <div class="border-t pt-6 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="skip_fridays" name="skip_fridays" value="1" checked class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="skip_fridays" class="ml-3 block text-sm font-medium text-gray-700">Skip Fridays for Test Dates</label>
                        </div>
                        
                        <div>
                            <label for="custom_dates" class="block text-sm font-medium text-gray-700 mb-1">Custom Dates (Optional)</label>
                            <input type="text" id="custom_dates" name="custom_dates" placeholder="e.g., 2026-03-26, 2026-03-28, 2026-03-31" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to auto-generate dates starting from today. Format: YYYY-MM-DD, comma separated.</p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4 border-t">
                        <button type="submit" class="w-full flex justify-center items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-md hover:shadow-lg transition duration-200 text-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span>Generate Layers</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- PAYWALL MODAL (Hidden by default) -->
    <div x-show="showPaywall" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <!-- Background overlay -->
            <div x-show="showPaywall" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPaywall = false"></div>

            <!-- Modal panel -->
            <div x-show="showPaywall" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Unlock Bulk Generation</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">You are 1 click away from generating 6 perfectly formatted layers. Upgrade to Pro to download the Excel file instantly.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row gap-2">
                    <form action="{{ route('subscribe.monthly') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:text-sm">Subscribe $5/mo</button>
                    </form>
                    <form action="{{ route('subscribe.yearly') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-white hover:bg-gray-900 focus:outline-none sm:text-sm">Subscribe $50/yr</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>