@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="flex">
        <aside class="w-64 bg-gray-900 text-white min-h-screen fixed">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-users text-blue-400 text-2xl"></i>
                    <span class="text-xl font-bold">Admin Panel</span>
                </div>
            </div>
            <nav class="mt-6">
                <a href="/admin/dashboard" class="flex items-center space-x-3 px-6 py-3 bg-gray-800 border-l-4 border-blue-500">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/members" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-users"></i>
                    <span>Members</span>
                </a>
                <a href="/admin/loans" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Loans</span>
                </a>
                <a href="/admin/savings" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-piggy-bank"></i>
                    <span>Savings</span>
                </a>
                <a href="/admin/reports" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="/admin/documents" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-file-alt"></i>
                    <span>Documents</span>
                </a>
                <a href="/admin/meetings" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Meetings</span>
                </a>
                <a href="/admin/settings" class="flex items-center space-x-3 px-6 py-3 hover:bg-gray-800 transition">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
            <div class="absolute bottom-0 left-0 right-0 p-4">
                <button id="logoutBtn" class="w-full flex items-center justify-center space-x-2 bg-red-600 hover:bg-red-700 py-2 rounded transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

        <main class="ml-64 flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-600">Welcome back, <span id="adminName"></span></p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <i class="fas fa-bell text-gray-600 text-xl cursor-pointer hover:text-blue-600"></i>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-circle text-2xl text-gray-600"></i>
                        <span id="userName" class="font-medium text-gray-700"></span>
                    </div>
                </div>
            </div>

            <div id="loading" class="flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            </div>

            <div id="dashboardContent" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Members</p>
                                <p id="totalMembers" class="text-3xl font-bold text-gray-800 mt-1">0</p>
                                <p id="pendingMembers" class="text-sm text-yellow-600 mt-2">0 pending approval</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Savings</p>
                                <p id="totalSavings" class="text-3xl font-bold text-gray-800 mt-1">₦0</p>
                                <p id="monthlySavings" class="text-sm text-green-600 mt-2">₦0 this month</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-wallet text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Active Loans</p>
                                <p id="activeLoans" class="text-3xl font-bold text-gray-800 mt-1">0</p>
                                <p id="pendingLoans" class="text-sm text-yellow-600 mt-2">0 pending approval</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-hand-holding-usd text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Outstanding</p>
                                <p id="outstandingBalance" class="text-3xl font-bold text-gray-800 mt-1">₦0</p>
                                <p id="repaidThisMonth" class="text-sm text-green-600 mt-2">₦0 repaid this month</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-clock mr-2 text-yellow-500"></i>Pending Approvals
                        </h3>
                        <div id="pendingApprovals" class="space-y-4">
                            <p class="text-gray-500 text-center py-4">No pending approvals</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-calendar mr-2 text-blue-500"></i>Upcoming Meetings
                        </h3>
                        <div id="upcomingMeetings" class="space-y-4">
                            <p class="text-gray-500 text-center py-4">No upcoming meetings</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-history mr-2 text-gray-500"></i>Recent Activity
                    </h3>
                    <div id="recentActivity" class="space-y-3">
                        <p class="text-gray-500 text-center py-4">No recent activity</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Savings Trend</h3>
                        <div id="savingsChart" class="h-64 flex items-center justify-center">
                            <p class="text-gray-500">Chart visualization area</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Status Overview</h3>
                        <div id="loanStatusChart" class="h-64 flex items-center justify-center">
                            <p class="text-gray-500">Chart visualization area</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const token = localStorage.getItem('token');
const user = JSON.parse(localStorage.getItem('user'));

if (!token) {
    window.location.href = '/login';
}

document.getElementById('userName').textContent = user.name;
document.getElementById('adminName').textContent = user.name;

document.getElementById('logoutBtn').addEventListener('click', async function() {
    localStorage.clear();
    window.location.href = '/login';
});

async function loadDashboard() {
    try {
        const response = await fetch('/api/admin/dashboard', {
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
            
            document.getElementById('totalMembers').textContent = d.members.total;
            document.getElementById('pendingMembers').textContent = d.members.pending + ' pending approval';
            document.getElementById('totalSavings').textContent = '₦' + formatNumber(d.savings.total);
            document.getElementById('monthlySavings').textContent = '₦' + formatNumber(d.savings.this_month) + ' this month';
            document.getElementById('activeLoans').textContent = d.loans.total;
            document.getElementById('pendingLoans').textContent = d.loans.pending + ' pending approval';
            document.getElementById('outstandingBalance').textContent = '₦' + formatNumber(d.loans.outstanding);
            document.getElementById('repaidThisMonth').textContent = '₦' + formatNumber(d.repayments.this_month) + ' repaid this month';

            renderPendingApprovals(d.pending_approvals);
            renderUpcomingMeetings(d.upcoming_meetings);
            renderRecentActivity(d.recent_activity);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function renderPendingApprovals(approvals) {
    const container = document.getElementById('pendingApprovals');
    
    const pendingMembers = approvals.pending_members || [];
    const pendingLoans = approvals.pending_loans || [];

    if (pendingMembers.length === 0 && pendingLoans.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No pending approvals</p>';
        return;
    }

    let html = '';

    pendingMembers.forEach(member => {
        html += `
            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">${member.first_name} ${member.last_name}</p>
                        <p class="text-sm text-gray-500">${member.member_number}</p>
                    </div>
                </div>
                <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                    Review
                </button>
            </div>
        `;
    });

    pendingLoans.forEach(loan => {
        html += `
            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-hand-holding-usd text-blue-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">${loan.member.first_name} ${loan.member.last_name}</p>
                        <p class="text-sm text-gray-500">${loan.loan_number} - ₦${formatNumber(loan.amount)}</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">${loan.status}</span>
            </div>
        `;
    });

    container.innerHTML = html;
}

function renderUpcomingMeetings(meetings) {
    const container = document.getElementById('upcomingMeetings');
    
    if (meetings.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No upcoming meetings</p>';
        return;
    }

    container.innerHTML = meetings.map(meeting => `
        <div class="p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800">${meeting.title}</p>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-calendar mr-1"></i>${formatDate(meeting.meeting_date)}
                    </p>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mr-1"></i>${meeting.venue}
                    </p>
                </div>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">${meeting.meeting_type}</span>
            </div>
        </div>
    `).join('');
}

function renderRecentActivity(activities) {
    const container = document.getElementById('recentActivity');
    
    if (activities.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent activity</p>';
        return;
    }

    container.innerHTML = activities.map(activity => `
        <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-200">
                <i class="fas fa-user text-gray-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-gray-800">${activity.description}</p>
                <p class="text-xs text-gray-500">${activity.user?.name || 'System'} - ${formatDate(activity.created_at)}</p>
            </div>
        </div>
    `).join('');
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

loadDashboard();
</script>
@endsection