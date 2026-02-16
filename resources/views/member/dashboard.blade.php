@extends('layouts.app')

@section('title', 'Dashboard - Member')

@section('content')
<div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-bold text-gray-800">Cooperative Society</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <i class="fas fa-bell text-gray-600 text-xl cursor-pointer hover:text-blue-600"></i>
                        <span id="notificationBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                    </div>
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600">
                            <i class="fas fa-user-circle text-2xl"></i>
                            <span id="userName" class="font-medium">Loading...</span>
                        </button>
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="/profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="/savings" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-piggy-bank mr-2"></i>Savings
                            </a>
                            <a href="/loans" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-hand-holding-usd mr-2"></i>Loans
                            </a>
                            <hr class="my-2">
                            <button id="logoutBtn" class="w-full text-left px-4 py-2 text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="loading" class="flex items-center justify-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
        </div>

        <div id="dashboardContent" class="hidden">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Welcome back, <span id="welcomeName"></span>!</h2>
                <p class="text-gray-600 mt-1">Here's an overview of your cooperative account</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Savings Balance</p>
                            <p id="savingsBalance" class="text-2xl font-bold text-gray-800 mt-1">₦0.00</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-wallet text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-sm text-green-600 mt-4"><i class="fas fa-arrow-up mr-1"></i>Available for loan</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Outstanding Loan</p>
                            <p id="outstandingLoan" class="text-2xl font-bold text-gray-800 mt-1">₦0.00</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-landmark text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <p id="loanStatus" class="text-sm text-gray-600 mt-4">No active loan</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Credit Score</p>
                            <p id="creditScore" class="text-2xl font-bold text-gray-800 mt-1">0</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-star text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p id="creditRating" class="text-sm text-green-600 mt-4">Excellent</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Loan Eligibility</p>
                            <p id="loanEligibility" class="text-2xl font-bold text-gray-800 mt-1">₦0.00</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-4">Maximum loan amount</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Savings</h3>
                    <div id="recentSavings" class="space-y-3">
                        <p class="text-gray-500 text-center py-4">No recent savings</p>
                    </div>
                    <a href="/savings" class="block mt-4 text-center text-blue-600 hover:text-blue-700">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Loans</h3>
                    <div id="recentLoans" class="space-y-3">
                        <p class="text-gray-500 text-center py-4">No recent loans</p>
                    </div>
                    <a href="/loans" class="block mt-4 text-center text-blue-600 hover:text-blue-700">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Transactions</h3>
                <div id="recentTransactions" class="space-y-3">
                    <p class="text-gray-500 text-center py-4">No recent transactions</p>
                </div>
            </div>

            <div id="activeLoanSection" class="hidden bg-gradient-to-r from-blue-500 to-blue-700 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-semibold mb-4"><i class="fas fa-info-circle mr-2"></i>Active Loan Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-blue-100 text-sm">Loan Number</p>
                        <p id="activeLoanNumber" class="text-xl font-bold"></p>
                    </div>
                    <div>
                        <p class="text-blue-100 text-sm">Total Amount</p>
                        <p id="activeLoanAmount" class="text-xl font-bold"></p>
                    </div>
                    <div>
                        <p class="text-blue-100 text-sm">Next Payment</p>
                        <p id="nextPaymentAmount" class="text-xl font-bold"></p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-blue-100 text-sm mb-2">Progress</p>
                    <div class="w-full bg-blue-400 rounded-full h-2">
                        <div id="loanProgressBar" class="bg-white h-2 rounded-full transition-all duration-300"></div>
                    </div>
                    <p id="loanProgressText" class="text-sm mt-1"></p>
                </div>
                <a href="/loans" class="inline-block mt-4 bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-blue-50 transition">
                    View Loan Details
                </a>
            </div>

            <div class="fixed bottom-6 right-6 flex space-x-4">
                <a href="/loans/apply" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full shadow-lg transition flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Apply for Loan</span>
                </a>
                <a href="/savings/contribute" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-full shadow-lg transition flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Savings</span>
                </a>
            </div>
        </div>
    </main>
</div>

<script>
const token = localStorage.getItem('token');
const user = JSON.parse(localStorage.getItem('user'));

if (!token) {
    window.location.href = '/login';
}

document.getElementById('userName').textContent = user.name;

document.getElementById('userMenuBtn').addEventListener('click', function() {
    document.getElementById('userMenu').classList.toggle('hidden');
});

document.getElementById('logoutBtn').addEventListener('click', async function() {
    localStorage.clear();
    window.location.href = '/login';
});

async function loadDashboard() {
    try {
        const response = await fetch('/api/member/dashboard', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('dashboardContent').classList.remove('hidden');

            const d = data.data;
            
            document.getElementById('welcomeName').textContent = d.member.first_name;
            document.getElementById('savingsBalance').textContent = '₦' + formatNumber(d.savings_balance);
            document.getElementById('outstandingLoan').textContent = '₦' + formatNumber(d.outstanding_loan_balance);
            document.getElementById('creditScore').textContent = d.credit_score;
            document.getElementById('loanEligibility').textContent = '₦' + formatNumber(d.loan_eligibility);

            const creditRating = d.credit_score >= 80 ? 'Excellent' : d.credit_score >= 60 ? 'Good' : 'Fair';
            document.getElementById('creditRating').textContent = creditRating;

            if (d.outstanding_loan_balance > 0) {
                document.getElementById('loanStatus').textContent = 'Active loan in progress';
                document.getElementById('activeLoanSection').classList.remove('hidden');
                document.getElementById('activeLoanNumber').textContent = d.active_loan.loan_number;
                document.getElementById('activeLoanAmount').textContent = '₦' + formatNumber(d.active_loan.total_repayment);
                document.getElementById('nextPaymentAmount').textContent = '₦' + formatNumber(d.next_payment.due_amount);
                document.getElementById('loanProgressBar').style.width = d.active_loan.completion_percentage + '%';
                document.getElementById('loanProgressText').textContent = d.active_loan.completion_percentage.toFixed(1) + '% repaid';
            }

            renderRecentSavings(d.recent_savings);
            renderRecentLoans(d.recent_loans);
            renderRecentTransactions(d.recent_transactions);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function renderRecentSavings(savings) {
    const container = document.getElementById('recentSavings');
    if (savings.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent savings</p>';
        return;
    }

    container.innerHTML = savings.map(s => `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="font-medium text-gray-800">${s.contribution_type}</p>
                <p class="text-sm text-gray-500">${formatDate(s.payment_date)}</p>
            </div>
            <p class="font-semibold text-green-600">+₦${formatNumber(s.amount)}</p>
        </div>
    `).join('');
}

function renderRecentLoans(loans) {
    const container = document.getElementById('recentLoans');
    if (loans.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent loans</p>';
        return;
    }

    container.innerHTML = loans.map(l => `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="font-medium text-gray-800">${l.loan_number}</p>
                <p class="text-sm text-gray-500">${formatDate(l.created_at)}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(l.status)}">${l.status}</span>
        </div>
    `).join('');
}

function renderRecentTransactions(transactions) {
    const container = document.getElementById('recentTransactions');
    if (transactions.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent transactions</p>';
        return;
    }

    container.innerHTML = transactions.map(t => `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center ${getTransactionColor(t.transaction_type)}">
                    <i class="${getTransactionIcon(t.transaction_type)}"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">${t.transaction_type.replace('_', ' ')}</p>
                    <p class="text-sm text-gray-500">${formatDate(t.transaction_date)}</p>
                </div>
            </div>
            <p class="font-semibold ${t.transaction_type.includes('repayment') || t.transaction_type === 'savings' ? 'text-green-600' : 'text-red-600'}">
                ${t.transaction_type.includes('repayment') || t.transaction_type === 'savings' ? '+' : '-'}₦${formatNumber(t.amount)}
            </p>
        </div>
    `).join('');
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-GB');
}

function getStatusColor(status) {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-blue-100 text-blue-800',
        'active': 'bg-green-100 text-green-800',
        'completed': 'bg-gray-100 text-gray-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function getTransactionColor(type) {
    const colors = {
        'savings': 'bg-green-100 text-green-600',
        'loan_repayment': 'bg-green-100 text-green-600',
        'loan_disbursement': 'bg-blue-100 text-blue-600',
        'withdrawal': 'bg-red-100 text-red-600'
    };
    return colors[type] || 'bg-gray-100 text-gray-600';
}

function getTransactionIcon(type) {
    const icons = {
        'savings': 'fas fa-piggy-bank',
        'loan_repayment': 'fas fa-hand-holding-usd',
        'loan_disbursement': 'fas fa-money-bill-wave',
        'withdrawal': 'fas fa-arrow-down'
    };
    return icons[type] || 'fas fa-exchange-alt';
}

loadDashboard();
</script>
@endsection