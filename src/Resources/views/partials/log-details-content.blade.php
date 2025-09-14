<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-8 px-6 py-4" aria-label="Tabs">
            <button onclick="switchTab('steps')" class="tab-button active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600" data-tab="steps">
                <i class="fas fa-list-ul mr-2"></i>Steps ({{ $meta['steps_count'] ?? 0 }})
            </button>
            <button onclick="switchTab('request')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="request">
                <i class="fas fa-arrow-right mr-2"></i>Request
            </button>
            <button onclick="switchTab('response')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="response">
                <i class="fas fa-arrow-left mr-2"></i>Response
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
        <!-- Steps Tab -->
        <div id="steps-tab" class="tab-content active">
            @include('simple-logging::partials.steps-content', ['steps' => $data['steps'] ?? []])
        </div>

        <!-- Request Tab -->
        <div id="request-tab" class="tab-content hidden">
            @include('simple-logging::partials.request-content', ['requestInfo' => $data['request_info'] ?? []])
        </div>

        <!-- Response Tab -->
        <div id="response-tab" class="tab-content hidden">
            @include('simple-logging::partials.response-content', ['responseInfo' => $data['response_info'] ?? []])
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        // Hide all tab contents except the one we want to show
        document.querySelectorAll(".tab-content").forEach(content => {
            if (content.id !== tabName + "-tab") {
                content.classList.add("hidden");
            } else {
                content.classList.remove("hidden");
            }
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll(".tab-button").forEach(button => {
            button.classList.remove("active", "border-blue-500", "text-blue-600");
            button.classList.add("border-transparent", "text-gray-500");
        });
        
        // Add active class to selected tab button
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        activeButton.classList.add("active", "border-blue-500", "text-blue-600");
        activeButton.classList.remove("border-transparent", "text-gray-500");
    }
    
    // Ensure steps tab is visible by default when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Hide request and response tabs by default, keep steps visible
        const requestTab = document.getElementById('request-tab');
        const responseTab = document.getElementById('response-tab');
        const stepsTab = document.getElementById('steps-tab');
        
        if (requestTab) requestTab.classList.add('hidden');
        if (responseTab) responseTab.classList.add('hidden');
        if (stepsTab) stepsTab.classList.remove('hidden');
    });
    
    function showDataValue(key, data, visualIndicator, methodName) {
        const modal = document.getElementById("data-modal");
        if (!modal) {
            // Create modal if it doesn't exist
            const modal = document.createElement("div");
            modal.id = "data-modal";
            modal.className = "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden";
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 pb-2">
                        <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Data Viewer</h3>
                        <button onclick="closeDataModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <span class="text-sm text-gray-600">From:</span>
                            <span class="text-sm font-medium text-gray-900" id="modal-message"></span>
                        </div>
                        <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-96">
                            <pre class="text-green-400 text-sm font-mono" id="modal-content"></pre>
                        </div>
                    </div>
                    <div class="flex justify-end p-4 border-t border-gray-200">
                        <button onclick="closeDataModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Update modal content
        document.getElementById("modal-title").innerHTML = key;
        document.getElementById("modal-message").textContent = methodName;
        document.getElementById("modal-content").textContent = JSON.stringify(data, null, 2);
        
        // Show modal
        modal.classList.remove("hidden");
    }
    
    function closeDataModal() {
        const modal = document.getElementById("data-modal");
        if (modal) {
            modal.classList.add("hidden");
        }
    }
    
    // Close modal when clicking outside
    document.addEventListener("click", (e) => {
        const modal = document.getElementById("data-modal");
        if (e.target === modal) {
            closeDataModal();
        }
    });
</script>
