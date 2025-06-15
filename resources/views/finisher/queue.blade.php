<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finisher Queue - Flashlight Cleanstar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .status-pending {
            background-color: rgb(254 240 138);
            color: rgb(146 64 14);
        }

        .status-in_progress {
            background-color: rgb(219 234 254);
            color: rgb(30 64 175);
        }

        .status-completed {
            background-color: rgb(220 252 231);
            color: rgb(21 128 61);
        }

        .membership-star {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .queue-table {
            background: linear-gradient(135deg, #1f2937, #374151);
        }

        .table-header {
            background: rgba(0, 0, 0, 0.2);
        }

        .table-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .auto-refresh {
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Loading state styles */
        .loading-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .queue-table {
                font-size: 14px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Better scrolling on mobile */
        .table-container {
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen font-inter" x-data="queueApp()" x-init="init()">
    <!-- Loading Overlay -->
    <div x-show="loading" class="fixed inset-0 z-50 loading-overlay flex items-center justify-center">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-white">Loading queue data...</p>
        </div>
    </div>

    <!-- Header -->
    <header class="bg-gray-800 shadow-lg py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-car text-yellow-500 text-2xl"></i>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">FLASHLIGHT CLEANSTAR</h1>
                    </div>
                    <div class="text-sm text-gray-300 text-center sm:text-left">
                        THE BEST STAR TO SERVICE ★ ★ ★
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="text-center sm:text-right">
                        <div class="text-sm text-gray-400">Finisher</div>
                        <div class="font-semibold" x-text="finisherName"></div>
                    </div>
                    <div class="text-center sm:text-right">
                        <div class="text-sm text-gray-400">Date</div>
                        <div class="font-semibold" x-text="currentDate"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-8 stats-grid">
            <div class="bg-yellow-500 rounded-lg p-4 sm:p-6 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-white" x-text="stats.pending || '0'"></div>
                <div class="text-yellow-100 text-sm sm:text-base">Pending</div>
            </div>
            <div class="bg-blue-500 rounded-lg p-4 sm:p-6 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-white" x-text="stats.in_progress || '0'"></div>
                <div class="text-blue-100 text-sm sm:text-base">In Progress</div>
            </div>
            <div class="bg-green-500 rounded-lg p-4 sm:p-6 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-white" x-text="stats.completed || '0'"></div>
                <div class="text-green-100 text-sm sm:text-base">Completed</div>
            </div>
            <div class="bg-purple-500 rounded-lg p-4 sm:p-6 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-white" x-text="stats.total || '0'"></div>
                <div class="text-purple-100 text-sm sm:text-base">Total Queue</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 space-y-4 sm:space-y-0">
            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <button
                    @click="refreshData()"
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors w-full sm:w-auto"
                    :disabled="loading"
                >
                    <i class="fas fa-sync-alt" :class="{ 'auto-refresh': loading }"></i>
                    <span>Refresh</span>
                </button>

                <div class="flex items-center space-x-2">
                    <input
                        type="checkbox"
                        x-model="autoRefresh"
                        id="autoRefresh"
                        class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                    >
                    <label for="autoRefresh" class="text-sm text-gray-300">Auto Refresh (30s)</label>
                </div>
            </div>

            <div class="text-sm text-gray-400 text-center sm:text-right">
                Last updated: <span x-text="lastUpdated || 'Never'"></span>
            </div>
        </div>

        <!-- Queue Table -->
        <div class="queue-table rounded-lg overflow-hidden shadow-2xl">
            <div class="overflow-x-auto table-container">
                <table class="w-full min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">No</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Motorbike</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">License Plate</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Customer Name</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Additional Services</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Food and Drinks</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Membership</th>
                            <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        <template x-for="(transaction, index) in transactions" :key="transaction.id">
                            <tr class="table-row transition-colors duration-200"
                                :class="{
                                    'bg-yellow-900 bg-opacity-20': transaction.status === 'pending',
                                    'bg-blue-900 bg-opacity-20': transaction.status === 'in_progress',
                                    'bg-green-900 bg-opacity-20': transaction.status === 'completed'
                                }">
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-white" x-text="index + 1"></td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-300" x-text="transaction.motorbike || 'N/A'"></td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-300 font-mono" x-text="transaction.license_plate || '-'"></td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-white" x-text="transaction.customer_name || 'N/A'"></td>
                                <td class="px-3 sm:px-6 py-4 text-sm text-gray-300">
                                    <div x-show="transaction.additional_services && transaction.additional_services.length > 0">
                                        <ul class="list-disc list-inside space-y-1">
                                            <template x-for="service in transaction.additional_services">
                                                <li x-text="service"></li>
                                            </template>
                                        </ul>
                                    </div>
                                    <div x-show="!transaction.additional_services || transaction.additional_services.length === 0" class="text-gray-500">-</div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 text-sm text-gray-300">
                                    <div x-show="transaction.food_drinks && transaction.food_drinks.length > 0">
                                        <ul class="list-disc list-inside space-y-1">
                                            <template x-for="item in transaction.food_drinks">
                                                <li x-text="item"></li>
                                            </template>
                                        </ul>
                                    </div>
                                    <div x-show="!transaction.food_drinks || transaction.food_drinks.length === 0" class="text-gray-500">-</div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm">
                                    <div x-show="transaction.membership" class="inline-flex items-center membership-star px-2 py-1 rounded-full text-xs font-medium">
                                        <i class="fas fa-star mr-1"></i>
                                        <span x-text="transaction.membership"></span>
                                    </div>
                                    <div x-show="!transaction.membership" class="text-gray-500">-</div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-bold text-yellow-400" x-text="'Rp. ' + numberFormat(transaction.total_amount || 0)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && (!transactions || transactions.length === 0)" class="text-center py-12">
                <i class="fas fa-clipboard-list text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-400 mb-2">No transactions in queue</h3>
                <p class="text-gray-500">All caught up! New transactions will appear here.</p>
            </div>

            <!-- Error State -->
            <div x-show="error" class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                <h3 class="text-xl font-medium text-red-400 mb-2">Failed to load data</h3>
                <p class="text-gray-500 mb-4" x-text="error"></p>
                <button @click="refreshData()" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg">
                    Try Again
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 py-4 border-t border-gray-700">
            <p class="text-gray-400 text-sm">~ We Wash Better Than You Do ~</p>
        </div>
    </main>

    <script>
        function queueApp() {
            return {
                transactions: @json($transactions ?? []),
                loading: false,
                autoRefresh: true,
                lastUpdated: '',
                finisherName: 'Fulan Maulana',
                currentDate: '',
                error: null,

                get stats() {
                    if (!this.transactions || !Array.isArray(this.transactions)) {
                        return { pending: 0, in_progress: 0, completed: 0, total: 0 };
                    }

                    const pending = this.transactions.filter(t => t.status === 'pending').length;
                    const in_progress = this.transactions.filter(t => t.status === 'in_progress').length;
                    const completed = this.transactions.filter(t => t.status === 'completed').length;

                    return {
                        pending,
                        in_progress,
                        completed,
                        total: this.transactions.length
                    };
                },

                init() {
                    console.log('Queue app initialized');
                    console.log('Initial transactions:', this.transactions);
                    this.updateDateTime();
                    this.startAutoRefresh();
                },

                startAutoRefresh() {
                    setInterval(() => {
                        this.updateDateTime();
                        if (this.autoRefresh) {
                            this.refreshData();
                        }
                    }, 30000);
                },

                updateDateTime() {
                    const now = new Date();
                    this.currentDate = now.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    this.lastUpdated = now.toLocaleTimeString();
                },

                async refreshData() {
                    this.loading = true;
                    this.error = null;

                    try {
                        console.log('Fetching data from /finisher/queue/data');
                        const response = await fetch('/finisher/queue/data');

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
                        console.log('Received data:', data);

                        this.transactions = Array.isArray(data) ? data : [];
                        this.lastUpdated = new Date().toLocaleTimeString();

                    } catch (error) {
                        console.error('Failed to refresh data:', error);
                        this.error = 'Failed to load queue data. Please check your connection.';
                    } finally {
                        this.loading = false;
                    }
                },

                numberFormat(number) {
                    if (!number || isNaN(number)) return '0';
                    return new Intl.NumberFormat('id-ID').format(number);
                }
            }
        }
    </script>
</body>
</html>
