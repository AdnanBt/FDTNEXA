<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Checklist Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased pb-12" x-data="fieldTool()">

    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-lg font-bold tracking-tight text-gray-900">Field Tool</span>
            </div>
            <div class="text-xs font-medium text-gray-500 hidden sm:block">
                {{ Auth::user()->name }}
            </div>
        </div>
    </nav>

    <!-- STICKY CALCULATOR BAR -->
    <div class="sticky top-14 z-10 bg-gray-900 border-b border-gray-800 shadow-md">
        <div class="max-w-6xl mx-auto px-4 py-2 flex items-center justify-between">
            <div class="flex items-center space-x-4 sm:space-x-8">
                <!-- Dry Density -->
                <div>
                    <p class="text-[10px] sm:text-xs text-gray-400 uppercase tracking-wider font-bold">Dry Density (Q)</p>
                    <div class="text-lg sm:text-xl font-bold font-mono text-white leading-none">
                        <span x-text="Q ? Q.toFixed(3) : '0.000'"></span> <span class="text-xs text-gray-500">g/cm³</span>
                    </div>
                </div>
                
                <!-- Compaction % -->
                <div>
                    <p class="text-[10px] sm:text-xs text-gray-400 uppercase tracking-wider font-bold">Compaction</p>
                    <div class="text-lg sm:text-xl font-extrabold font-mono leading-none transition-colors duration-300" 
                         :class="compaction >= 95 ? 'text-green-400' : 'text-red-400'">
                        <span x-text="compaction ? compaction.toFixed(1) : '0.0'"></span><span class="text-xs">%</span>
                    </div>
                </div>
            </div>
            
            <!-- Pass/Fail Badge -->
            <div class="inline-flex items-center px-2 sm:px-3 py-1 rounded text-[10px] sm:text-xs font-bold border"
                 :class="compaction >= 95 ? 'bg-green-900/50 text-green-400 border-green-800' : 'bg-red-900/50 text-red-400 border-red-800'">
                <span x-text="compaction >= 95 ? 'PASS' : 'FAIL'"></span>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 mt-4">
        
        @if($is_trial)
            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border-l-4 border-amber-500 text-amber-800 p-3 mb-4 rounded-r-lg shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="font-bold text-sm">Free Trial Active</p>
                        <p class="text-xs opacity-90">You have <span class="font-bold">{{ $days_left }}</span> days left.</p>
                    </div>
                </div>
                <a href="#" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-1 px-3 rounded shadow transition text-xs w-full sm:w-auto text-center">Upgrade for $1</a>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            
            <div class="bg-blue-600 px-4 py-3 border-b border-blue-700">
                <h1 class="text-lg font-bold text-white">Sand-Cone Method Data Entry</h1>
            </div>

            <form action="{{ route('field-tool.generate') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-6 lg:p-8">
                @csrf
                
                <!-- Header Customization -->
                <div class="mb-6">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 border-b pb-1">1. Header Customization</h2>
                    
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Company Banner Logo</label>
                        <input type="file" name="logo" accept="image/*" class="w-full text-xs border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1.5 bg-gray-50">
                        <p class="text-[10px] text-gray-500 mt-1">Best size: Wide banner (760 x 100).</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Project Title</label>
                            <input type="text" name="project_title" placeholder="Replaces Arabic" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm transition">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Contract No.</label>
                            <input type="text" name="contract_no" placeholder="e.g., CN-123" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm transition">
                        </div>
                    </div>
                </div>

                <!-- Field Data (2 columns even on mobile) -->
                <div class="mb-6">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 border-b pb-1">2. Field Measurements</h2>
                    
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Sand Before (A)</label>
                            <input type="number" step="0.1" name="A" x-model.number="A" @input="calculate" class="w-full border border-gray-300 p-2 sm:p-3 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-base sm:text-lg font-mono transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Sand After (B)</label>
                            <input type="number" step="0.1" name="B" x-model.number="B" @input="calculate" class="w-full border border-gray-300 p-2 sm:p-3 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-base sm:text-lg font-mono transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Wet Soil (H)</label>
                            <input type="number" step="0.1" name="H" x-model.number="H" @input="calculate" class="w-full border border-gray-300 p-2 sm:p-3 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-base sm:text-lg font-mono transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Moisture (P) %</label>
                            <input type="number" step="0.1" name="P" x-model.number="P" @input="calculate" class="w-full border border-gray-300 p-2 sm:p-3 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-base sm:text-lg font-mono transition" required>
                        </div>
                    </div>
                </div>

                <!-- Proctor Data -->
                <div class="mb-6">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 border-b pb-1">3. Reference Proctor</h2>
                    
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Max Dry Density (M)</label>
                            <input type="number" step="0.001" name="M" x-model.number="M" @input="calculate" value="1.981" class="w-full border border-gray-300 p-2 sm:p-3 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-base sm:text-lg font-mono transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Opt. Moisture (N) %</label>
                            <input type="number" step="0.1" name="N" x-model.number="N" class="w-full border border-gray-300 p-2 sm:p-3 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-base sm:text-lg font-mono transition" value="8.6" required>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-8 pt-4 border-t border-gray-100">
                    <button type="submit" class="w-full flex justify-center items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-md hover:shadow-lg transition duration-200 text-base">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Generate & Download Excel</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function fieldTool() {
            return {
                A: null, B: null, H: null, P: null,
                M: 1.981, N: 8.6,
                D: 1694, F: 1.56, J: 306, 
                Q: 0, compaction: 0,
                calculate() {
                    if(this.A > this.B && this.H > this.J && this.P >= 0) {
                        let C = this.A - this.B;
                        let E = C - this.D;
                        let G = E / this.F;
                        let K = this.H - this.J;
                        let L = K / G;
                        this.Q = (L / (this.P + 100)) * 100;
                        if(this.M > 0) {
                            this.compaction = (this.Q / this.M) * 100;
                        }
                    } else {
                        this.Q = 0;
                        this.compaction = 0;
                    }
                }
            }
        }
    </script>
</body>
</html>