@extends('layouts.app')

@section('title', 'Register - Cooperative Society')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-blue-600 px-8 py-6">
                <h1 class="text-2xl font-bold text-white">Join Our Cooperative</h1>
                <p class="text-blue-100 mt-1">Become a member and start saving today</p>
            </div>

            <form id="registerForm" class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                        <select id="gender" name="gender" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                    <textarea id="address" name="address" required rows="2"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                        <input type="text" id="city" name="city" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                        <input type="text" id="state" name="state" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Occupation</label>
                        <input type="text" id="occupation" name="occupation"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Income (₦)</label>
                        <input type="number" id="monthly_income" name="monthly_income" step="1000"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Next of Kin Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                            <input type="text" id="next_of_kin_name" name="next_of_kin_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" id="next_of_kin_phone" name="next_of_kin_phone" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Relationship *</label>
                        <input type="text" id="next_of_kin_relationship" name="next_of_kin_relationship" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div id="error" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"></div>
                <div id="success" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"></div>

                <button type="submit" id="submitBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                    <span id="btnText">Register</span>
                    <i id="btnSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                </button>
            </form>

            <div class="px-8 pb-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">Sign In</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    const errorDiv = document.getElementById('error');
    const successDiv = document.getElementById('success');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');
    submitBtn.disabled = true;
    btnText.textContent = 'Registering...';
    btnSpinner.classList.remove('hidden');

    try {
        const response = await fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            successDiv.textContent = result.message;
            successDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            btnText.textContent = 'Register';
            btnSpinner.classList.add('hidden');
            
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else {
            errorDiv.textContent = result.message || 'Registration failed. Please try again.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            btnText.textContent = 'Register';
            btnSpinner.classList.add('hidden');
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        btnText.textContent = 'Register';
        btnSpinner.classList.add('hidden');
    }
});
</script>
@endsection