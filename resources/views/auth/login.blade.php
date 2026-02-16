@extends('layouts.app')

@section('title', 'Login - Cooperative Society')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                <i class="fas fa-users text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Welcome Back</h1>
            <p class="text-gray-600 mt-2">Sign in to your account</p>
        </div>

        <form id="loginForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email" required
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="you@example.com">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" required
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="••••••••">
                </div>
            </div>

            <div id="error" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"></div>

            <button type="submit" id="submitBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                <span id="btnText">Sign In</span>
                <i id="btnSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Don't have an account? 
                <a href="/register" class="text-blue-600 hover:text-blue-700 font-medium">Register</a>
            </p>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500 text-center mb-3">Demo Accounts:</p>
            <div class="text-xs text-gray-600 space-y-1">
                <p><strong>Admin:</strong> admin@cooperative.com / admin123</p>
                <p><strong>Chairman:</strong> chairman@cooperative.com / chairman123</p>
                <p><strong>Secretary:</strong> secretary@cooperative.com / secretary123</p>
                <p><strong>Treasurer:</strong> treasurer@cooperative.com / treasurer123</p>
                <p><strong>Member:</strong> member4@cooperative.com / member123</p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('error');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    errorDiv.classList.add('hidden');
    submitBtn.disabled = true;
    btnText.textContent = 'Signing in...';
    btnSpinner.classList.remove('hidden');

    try {
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            localStorage.setItem('token', data.data.token);
            localStorage.setItem('user', JSON.stringify(data.data.user));
            window.location.href = '/dashboard';
        } else {
            errorDiv.textContent = data.message || 'Login failed. Please try again.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            btnText.textContent = 'Sign In';
            btnSpinner.classList.add('hidden');
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        btnText.textContent = 'Sign In';
        btnSpinner.classList.add('hidden');
    }
});
</script>
@endsection