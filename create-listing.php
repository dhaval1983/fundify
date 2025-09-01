<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Business Listing - Fundify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'fundify-blue': '#3B82F6',
                        'fundify-dark': '#1E293B',
                        'fundify-gray': '#F8FAFC'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-fundify-gray min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-4xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-fundify-dark">Create Your Business Listing</h1>
                    <p class="text-gray-600 mt-1">Share your startup story with potential investors</p>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="fas fa-clock mr-2"></i>Step 1 of 3
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="max-w-4xl mx-auto px-6 py-4">
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-fundify-blue h-2 rounded-full transition-all duration-300" style="width: 33%"></div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="max-w-4xl mx-auto px-6 py-8">
        <form class="space-y-8">
            <!-- Basic Information Section -->
            <div class="bg-white rounded-xl shadow-sm border p-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-fundify-blue rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-fundify-dark">Basic Information</h2>
                        <p class="text-gray-600">Tell us about your business in a few key details</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <!-- Business Title -->
                    <div>
                        <label for="business_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Business Title *
                        </label>
                        <input 
                            type="text" 
                            id="business_title" 
                            name="business_title"
                            placeholder="e.g., EcoTech Solutions - Sustainable Energy Platform"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fundify-blue focus:border-transparent transition-all duration-200"
                            required
                        >
                        <p class="text-sm text-gray-500 mt-1">Make it compelling and descriptive</p>
                    </div>

                    <!-- Short Pitch -->
                    <div>
                        <label for="short_pitch" class="block text-sm font-medium text-gray-700 mb-2">
                            One-Line Pitch *
                        </label>
                        <textarea 
                            id="short_pitch" 
                            name="short_pitch"
                            rows="2"
                            placeholder="Summarize your business in one powerful sentence that grabs investor attention"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fundify-blue focus:border-transparent transition-all duration-200 resize-none"
                            maxlength="150"
                            required
                        ></textarea>
                        <div class="flex justify-between text-sm text-gray-500 mt-1">
                            <span>This will be the first thing investors see</span>
                            <span id="pitch-counter">0/150</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investment Details Section -->
            <div class="bg-white rounded-xl shadow-sm border p-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-chart-pie text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-fundify-dark">Investment Details</h2>
                        <p class="text-gray-600">Define your equity offering range</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Minimum Equity -->
                    <div>
                        <label for="min_equity" class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Equity Offered (%) *
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="min_equity" 
                                name="min_equity"
                                min="0" 
                                max="100" 
                                step="0.1"
                                placeholder="5.0"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fundify-blue focus:border-transparent transition-all duration-200"
                                required
                            >
                            <span class="absolute right-4 top-3 text-gray-500">%</span>
                        </div>
                    </div>

                    <!-- Maximum Equity -->
                    <div>
                        <label for="max_equity" class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Equity Offered (%) *
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="max_equity" 
                                name="max_equity"
                                min="0" 
                                max="100" 
                                step="0.1"
                                placeholder="25.0"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fundify-blue focus:border-transparent transition-all duration-200"
                                required
                            >
                            <span class="absolute right-4 top-3 text-gray-500">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-700">
                            <strong>Tip:</strong> A flexible range attracts more investors. Most startups offer 10-25% equity in early rounds.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Classification Section -->
            <div class="bg-white rounded-xl shadow-sm border p-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-tags text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-fundify-dark">Business Classification</h2>
                        <p class="text-gray-600">Help investors find you in the right category</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Industry -->
                    <div>
                        <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
                            Industry *
                        </label>
                        <select 
                            id="industry" 
                            name="industry"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fundify-blue focus:border-transparent transition-all duration-200"
                            required
                        >
                            <option value="">Select Industry</option>
                            <option value="technology">Technology</option>
                            <option value="healthcare">Healthcare & Medical</option>
                            <option value="fintech">Financial Technology</option>
                            <option value="ecommerce">E-commerce & Retail</option>
                            <option value="education">Education & EdTech</option>
                            <option value="agriculture">Agriculture & AgriTech</option>
                            <option value="logistics">Logistics & Supply Chain</option>
                            <option value="energy">Energy & Environment</option>
                            <option value="food">Food & Beverage</option>
                            <option value="travel">Travel & Tourism</option>
                            <option value="real-estate">Real Estate & PropTech</option>
                            <option value="media">Media & Entertainment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Business Stage -->
                    <div>
                        <label for="business_stage" class="block text-sm font-medium text-gray-700 mb-2">
                            Business Stage *
                        </label>
                        <select 
                            id="business_stage" 
                            name="business_stage"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fundify-blue focus:border-transparent transition-all duration-200"
                            required
                        >
                            <option value="">Select Stage</option>
                            <option value="idea">Idea Stage</option>
                            <option value="prototype">Prototype/MVP</option>
                            <option value="pre-revenue">Pre-Revenue</option>
                            <option value="early-revenue">Early Revenue</option>
                            <option value="growth">Growth Stage</option>
                            <option value="expansion">Expansion Stage</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6">
                <button 
                    type="button" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-all duration-200"
                >
                    <i class="fas fa-save mr-2"></i>Save as Draft
                </button>
                
                <div class="space-x-4">
                    <button 
                        type="button" 
                        class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-all duration-200"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-8 py-3 bg-fundify-blue text-white rounded-lg hover:bg-blue-600 transition-all duration-200 shadow-sm"
                    >
                        Continue to Step 2 <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Character counter for pitch
        document.getElementById('short_pitch').addEventListener('input', function() {
            const counter = document.getElementById('pitch-counter');
            const length = this.value.length;
            counter.textContent = `${length}/150`;
            
            if (length > 140) {
                counter.classList.add('text-red-500');
            } else {
                counter.classList.remove('text-red-500');
            }
        });

        // Equity validation
        document.getElementById('min_equity').addEventListener('blur', validateEquity);
        document.getElementById('max_equity').addEventListener('blur', validateEquity);

        function validateEquity() {
            const minEquity = parseFloat(document.getElementById('min_equity').value) || 0;
            const maxEquity = parseFloat(document.getElementById('max_equity').value) || 0;
            
            if (minEquity > maxEquity && maxEquity > 0) {
                alert('Minimum equity cannot be greater than maximum equity');
                document.getElementById('min_equity').focus();
            }
        }

        // Form animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.bg-white');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    section.style.transition = 'all 0.6s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>