// Global variables
let currentUser = null;
let transporters = [];
let existingLoads = [];

// DOM Elements
document.addEventListener('DOMContentLoaded', function() {
    // Login form handling
    const loginForm = document.getElementById('login-form');
    const loginSection = document.getElementById('login-section');
    const dashboardSection = document.getElementById('dashboard-section');
    const userNameSpan = document.getElementById('user-name');
    const logoutBtn = document.getElementById('logout-btn');
    const addDriverBtn = document.getElementById('add-driver-btn');
    const sendLoadsBtn = document.getElementById('send-loads-btn');
    const forwardModal = document.getElementById('forward-modal');
    const sendLoadsModal = document.getElementById('send-loads-modal');
    const closeModals = document.querySelectorAll('.close-modal');
    const forwardForm = document.getElementById('forward-form');
    const sendLoadsForm = document.getElementById('send-loads-form');
    const selectAllDriversCheckbox = document.getElementById('select-all-drivers');
    const loadSelect = document.getElementById('load-select');
    
    // Make sure all modals are properly hidden
    if (forwardModal) {
        forwardModal.classList.add('hidden');
        forwardModal.style.display = 'none';
    }
    
    if (sendLoadsModal) {
        sendLoadsModal.classList.add('hidden');
        sendLoadsModal.style.display = 'none';
    }
    
    // Stats elements
    const totalTransportersEl = document.getElementById('total-transporters');
    const availableTodayEl = document.getElementById('available-today');
    const assignedOrdersEl = document.getElementById('assigned-orders');
    const newTransportersEl = document.getElementById('new-transporters');
    
    // Load selection change handler
    if (loadSelect) {
        loadSelect.addEventListener('change', function() {
            const selectedLoadId = parseInt(this.value);
            if (selectedLoadId) {
                // Find the selected load
                const selectedLoad = existingLoads.find(load => load.id === selectedLoadId);
                if (selectedLoad) {
                    // Fill in the form with the selected load details
                    document.getElementById('load-title').value = selectedLoad.title;
                    document.getElementById('pickup-location').value = selectedLoad.pickup_location;
                    document.getElementById('delivery-location').value = selectedLoad.delivery_location;
                    document.getElementById('load-date').value = selectedLoad.load_date;
                    document.getElementById('load-weight').value = selectedLoad.weight;
                    document.getElementById('load-cost').value = selectedLoad.cost;
                    document.getElementById('load-notes').value = selectedLoad.notes || '';
                }
            } else {
                // Clear the form for a new load
                resetLoadForm();
            }
        });
    }
    
    // Login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Show loading indicator on button
            const loginButton = this.querySelector('.login-btn');
            const originalText = loginButton.textContent;
            loginButton.textContent = 'Logging in...';
            loginButton.disabled = true;
            
            // Call login function that connects to the server
            login(username, password)
                .finally(() => {
                    // Restore button state
                    loginButton.textContent = originalText;
                    loginButton.disabled = false;
                });
        });
    }
    
    // Logout button
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            logout();
        });
    }
    
    // Close modal buttons
    closeModals.forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            if (modalId) {
                // Close specific modal
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }
            } else {
                // Close parent modal (for backwards compatibility)
                const modal = this.closest('.modal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }
            }
        });
    });
    
    // Forward form submission
    if (forwardForm) {
        forwardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const driverId = document.getElementById('driver-id').value;
            const notes = document.getElementById('notes').value;
            
            // Call forward function
            forwardToDispatch(driverId, notes);
            forwardModal.classList.add('hidden');
            forwardModal.style.display = 'none';
        });
    }
    
    // Send Loads button
    if (sendLoadsBtn) {
        sendLoadsBtn.addEventListener('click', function() {
            openSendLoadsModal();
        });
    }
    
    // Send Loads form submission
    if (sendLoadsForm) {
        sendLoadsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendLoadInformation();
        });
    }
    
    // Select All Drivers checkbox
    if (selectAllDriversCheckbox) {
        selectAllDriversCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            const driverCheckboxes = document.querySelectorAll('.driver-checkbox input[type="checkbox"]');
            
            driverCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    }
    
    // Add driver button
    if (addDriverBtn) {
        addDriverBtn.addEventListener('click', function() {
            // This would open a form to add a new driver
            alert('Add new driver functionality will be connected to the database.');
        });
    }
    
    // Load initial data (mock data for demonstration)
    loadMockData();
    
    // Check if user is logged in (for demonstration, we'll always start logged out)
    checkLoginStatus();

    // Profile tab buttons
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Handle URL hash for direct navigation
    if (window.location.hash === '#dashboard-section' && currentUser) {
        document.getElementById('login-section').classList.add('hidden');
        document.getElementById('dashboard-section').classList.remove('hidden');
    }
});

// Functions

// Login function
function login(username, password) {
    console.log("Login attempted with:", username, password);
    
    // Call the login API
    return fetch('api/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            username: username,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Login successful
            currentUser = data.user;
            document.getElementById('login-section').classList.add('hidden');
            document.getElementById('dashboard-section').classList.remove('hidden');
            
            if (document.getElementById('user-name')) {
                document.getElementById('user-name').textContent = currentUser.name;
            }
            
            // Update URL for bookmarking
            window.location.hash = 'dashboard-section';
            
            // Load transporters data
            loadTransporters();
            
            // Load existing loads
            loadExistingLoads();
            
            return true;
        } else {
            // Login failed
            alert('Login failed: ' + (data.error || 'Invalid credentials'));
            return false;
        }
    })
    .catch(error => {
        console.error('Error during login:', error);
        
        // Fallback to mock login for demonstration since server might not be running
        console.log("Using mock login due to server error");
        
        // Define credentials for both coordinator and drivers
        const coordinatorCredentials = {
            username: 'transport1',
            password: 'transport1',
            role: 'coordinator',
            name: 'Transport Coordinator'
        };
        
        const driverCredentials = [
            { username: 'driver1', password: 'driver1', id: 1, role: 'driver', name: 'John Smith' },
            { username: 'driver2', password: 'driver2', id: 2, role: 'driver', name: 'Sarah Johnson' },
            { username: 'driver3', password: 'driver3', id: 3, role: 'driver', name: 'Michael Brown' }
        ];
        
        // Check if coordinator credentials match
        if (username === coordinatorCredentials.username && password === coordinatorCredentials.password) {
            console.log("Coordinator login successful");
            
            currentUser = {
                username: username,
                role: coordinatorCredentials.role,
                name: coordinatorCredentials.name
            };
            
            // Store login info in session storage for persistence
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
            
            // Update UI for coordinator
            document.getElementById('user-name').textContent = currentUser.name;
            document.getElementById('login-section').classList.add('hidden');
            document.getElementById('dashboard-section').classList.remove('hidden');
            
            // Load transporters data
            loadTransporters();
            loadExistingLoads();
            return true;
        }
        
        // Check if driver credentials match
        const matchedDriver = driverCredentials.find(
            driver => driver.username === username && driver.password === password
        );
        
        if (matchedDriver) {
            console.log("Driver login successful");
            
            currentUser = {
                username: username,
                role: matchedDriver.role,
                id: matchedDriver.id,
                name: matchedDriver.name
            };
            
            // For driver login, redirect to driver profile
            alert('Driver login successful! Redirecting to driver profile.');
            window.location.href = `driver-profile.html?id=${matchedDriver.id}&driver_view=true`;
            return true;
        }
        
        // If no match found
        console.log("Invalid credentials");
        alert('Invalid credentials. Please try again.\n\nFor demo, use:\nCoordinator: transport1/transport1\nDriver: driver1/driver1');
        return false;
    });
}

// Logout function
function logout() {
    currentUser = null;
    // Clear the session storage
    sessionStorage.removeItem('currentUser');
    document.getElementById('login-section').classList.remove('hidden');
    document.getElementById('dashboard-section').classList.add('hidden');
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
}

// Check login status
function checkLoginStatus() {
    // Check if the user is already logged in from session storage
    const storedUser = sessionStorage.getItem('currentUser');
    if (storedUser) {
        currentUser = JSON.parse(storedUser);
        console.log("User found in session storage:", currentUser);
        
        document.getElementById('login-section').classList.add('hidden');
        document.getElementById('dashboard-section').classList.remove('hidden');
        
        if (document.getElementById('user-name')) {
            document.getElementById('user-name').textContent = currentUser.name || currentUser.username;
        }
        
        // Load data if we're at the dashboard
        if (document.getElementById('drivers-list')) {
            loadTransporters();
            loadExistingLoads();
        }
    } else {
        console.log("No user found in session storage, showing login screen");
        document.getElementById('login-section').classList.remove('hidden');
        document.getElementById('dashboard-section').classList.add('hidden');
    }
}

// Load transporters data
function loadTransporters() {
    console.log("Loading transporters data");
    
    // Fetch transporters from API
    fetch('api/get_drivers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                transporters = data.drivers;
                renderTransporters(transporters);
                
                // Update stats if available
                if (data.stats) {
                    document.getElementById('total-transporters').textContent = data.stats.totalTransporters;
                    document.getElementById('available-today').textContent = data.stats.availableToday;
                    document.getElementById('assigned-orders').textContent = data.stats.assignedOrders;
                    document.getElementById('new-transporters').textContent = data.stats.newTransporters;
                } else {
                    updateStats();
                }
                
                // Store in session storage
                sessionStorage.setItem('transporters', JSON.stringify(transporters));
            } else {
                console.error('Error loading transporters:', data.error);
                fallbackToStoredTransporters();
            }
        })
        .catch(error => {
            console.error('Error fetching transporters:', error);
            fallbackToStoredTransporters();
        });
}

// Fallback to stored transporters data
function fallbackToStoredTransporters() {
    console.log("Falling back to stored transporters data");
    const storedTransporters = sessionStorage.getItem('transporters');
    
    if (storedTransporters) {
        transporters = JSON.parse(storedTransporters);
    } else {
        // If nothing in storage, load mock data
        loadMockData();
    }
    
    renderTransporters(transporters);
    updateStats();
}

// Load existing loads data
function loadExistingLoads() {
    console.log("Loading existing loads data");
    
    // Fetch loads from API
    fetch('api/get_loads.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                existingLoads = data.loads;
                
                // Store in session storage
                sessionStorage.setItem('existingLoads', JSON.stringify(existingLoads));
                
                // Populate load select dropdown
                populateLoadSelect();
            } else {
                console.error('Error loading loads:', data.error);
                fallbackToStoredLoads();
            }
        })
        .catch(error => {
            console.error('Error fetching loads:', error);
            fallbackToStoredLoads();
        });
}

// Fallback to stored loads data
function fallbackToStoredLoads() {
    console.log("Falling back to stored loads data");
    const storedLoads = sessionStorage.getItem('existingLoads');
    
    if (storedLoads) {
        existingLoads = JSON.parse(storedLoads);
    } else {
        // If nothing in storage, use mock data
        existingLoads = [
            {
                id: 1,
                title: 'Grain Shipment',
                pickup_location: 'Farm A - 123 Rural Road',
                delivery_location: 'SkyAgro Processing Plant',
                load_date: '2023-06-15',
                weight: 2500.00,
                cost: 1200.00,
                notes: 'Handle with care, organic grain'
            },
            {
                id: 2,
                title: 'Equipment Transport',
                pickup_location: 'SkyAgro Warehouse',
                delivery_location: 'Farm B - 456 Country Lane',
                load_date: '2023-06-18',
                weight: 1800.50,
                cost: 950.75,
                notes: 'Agricultural equipment for seasonal harvest'
            },
            {
                id: 3,
                title: 'Fertilizer Delivery',
                pickup_location: 'SkyAgro Supply Center',
                delivery_location: 'Multiple Farms (See route details)',
                load_date: '2023-06-20',
                weight: 3200.00,
                cost: 1450.00,
                notes: 'Route details attached. Delivery to 3 farms in the northern region.'
            }
        ];
        
        sessionStorage.setItem('existingLoads', JSON.stringify(existingLoads));
    }
    
    populateLoadSelect();
}

// Populate load select dropdown
function populateLoadSelect() {
    const loadSelect = document.getElementById('load-select');
    if (!loadSelect) return;
    
    // Clear existing options except the first one
    while (loadSelect.options.length > 1) {
        loadSelect.remove(1);
    }
    
    // Add options for each load
    existingLoads.forEach(load => {
        const option = document.createElement('option');
        option.value = load.id;
        option.textContent = `${load.title} - ${load.load_date}`;
        loadSelect.appendChild(option);
    });
}

// Reset load form
function resetLoadForm() {
    document.getElementById('load-title').value = '';
    document.getElementById('pickup-location').value = '';
    document.getElementById('delivery-location').value = '';
    document.getElementById('load-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('load-weight').value = '';
    document.getElementById('load-cost').value = '';
    document.getElementById('load-notes').value = '';
}

// Forward driver to dispatch
function forwardToDispatch(driverId, notes) {
    // This would send data to the server
    const driver = transporters.find(d => d.id === parseInt(driverId));
    
    if (!driver) {
        alert('Driver not found!');
        return;
    }
    
    // Call the API to forward the driver
    fetch('api/forward_driver.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            driver_id: driverId,
            notes: notes,
            date_time: new Date().toISOString()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update driver status locally
            driver.status = 'assigned';
            
            // Update UI
            renderTransporters(transporters);
            updateStats();
            
            alert(`Driver ${driver.name} has been forwarded to dispatch coordinator.`);
        } else {
            console.error('Error forwarding driver:', data.error);
            alert('Failed to forward driver. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error forwarding driver:', error);
        alert('Failed to forward driver. Please try again.');
    });
}

// Open forward modal
function openForwardModal(driverId) {
    const driver = transporters.find(d => d.id === parseInt(driverId));
    
    if (!driver) {
        alert('Driver not found!');
        return;
    }
    
    // Set modal data
    document.getElementById('driver-id').value = driver.id;
    document.getElementById('driver-name').value = driver.name;
    
    // Set current date and time
    const now = new Date();
    const dateTimeStr = now.toLocaleString();
    document.getElementById('date-time').value = dateTimeStr;
    
    // Show modal
    const forwardModal = document.getElementById('forward-modal');
    forwardModal.classList.remove('hidden');
    forwardModal.style.display = 'flex';
}

// Open send loads modal
function openSendLoadsModal() {
    // Get available drivers
    const availableDrivers = transporters.filter(d => d.status === 'available');
    
    if (availableDrivers.length === 0) {
        alert('There are no available drivers to send load information to.');
        return;
    }
    
    // Populate available drivers list
    const availableDriversList = document.getElementById('available-drivers-list');
    availableDriversList.innerHTML = '';
    
    availableDrivers.forEach(driver => {
        const driverCheckbox = document.createElement('div');
        driverCheckbox.className = 'driver-checkbox';
        driverCheckbox.innerHTML = `
            <input type="checkbox" id="driver-${driver.id}" name="selected-drivers" value="${driver.id}">
            <label for="driver-${driver.id}">${driver.name} (${driver.vehicleType})</label>
        `;
        availableDriversList.appendChild(driverCheckbox);
    });
    
    // Reset form and set current date
    const loadSelect = document.getElementById('load-select');
    if (loadSelect) {
        loadSelect.selectedIndex = 0;
        resetLoadForm();
    }
    
    // Show modal
    const sendLoadsModal = document.getElementById('send-loads-modal');
    sendLoadsModal.classList.remove('hidden');
    sendLoadsModal.style.display = 'flex';
}

// Send load information to selected drivers
function sendLoadInformation() {
    const selectedDrivers = Array.from(document.querySelectorAll('.driver-checkbox input[type="checkbox"]:checked'))
        .map(checkbox => parseInt(checkbox.value));
    
    if (selectedDrivers.length === 0) {
        alert('Please select at least one driver.');
        return;
    }
    
    const loadData = {
        title: document.getElementById('load-title').value,
        pickup_location: document.getElementById('pickup-location').value,
        delivery_location: document.getElementById('delivery-location').value,
        date: document.getElementById('load-date').value,
        weight: document.getElementById('load-weight').value,
        cost: document.getElementById('load-cost').value,
        notes: document.getElementById('load-notes').value,
        driver_ids: selectedDrivers
    };
    
    const selectedDriversInfo = transporters.filter(driver => selectedDrivers.includes(driver.id));
    
    // Save load info to session storage (since we can't use PHP)
    const existingLoads = JSON.parse(sessionStorage.getItem('existingLoads') || '[]');
    const newLoad = {
        id: existingLoads.length + 1,
        ...loadData
    };
    existingLoads.push(newLoad);
    sessionStorage.setItem('existingLoads', JSON.stringify(existingLoads));
    
    // Send emails using EmailJS
    const emailPromises = selectedDriversInfo.map(driver => {
        const templateParams = {
            to_email: driver.email,
            to_name: driver.name,
            load_title: loadData.title,
            pickup_location: loadData.pickup_location,
            delivery_location: loadData.delivery_location,
            load_date: loadData.date,
            weight: loadData.weight,
            cost: loadData.cost,
            notes: loadData.notes
        };
        
        return emailjs.send(
            'YOUR_SERVICE_ID', // TODO: Replace with your EmailJS service ID from https://dashboard.emailjs.com/admin/services
            'YOUR_TEMPLATE_ID', // TODO: Replace with your EmailJS template ID from https://dashboard.emailjs.com/admin/templates
            templateParams
        );
    });
    
    // Handle all email sending attempts
    Promise.allSettled(emailPromises)
        .then(results => {
            const modal = document.getElementById('send-loads-modal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.getElementById('send-loads-form').reset();
            
            const successfulEmails = results.filter(r => r.status === 'fulfilled').length;
            const failedEmails = results.filter(r => r.status === 'rejected').length;
            
            if (successfulEmails > 0) {
                alert(`Load information sent to ${successfulEmails} driver(s).`);
                if (failedEmails > 0) {
                    console.warn(`${failedEmails} emails failed to send`);
                }
                loadTransporters();
                loadExistingLoads();
            } else {
                alert('Failed to send any emails. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to process load information. Please try again.');
        });
}

// Render transporters table
function renderTransporters(transporters) {
    const tbody = document.getElementById('drivers-list');
    tbody.innerHTML = '';
    
    transporters.forEach(driver => {
        const row = document.createElement('tr');
        
        // Define status class
        let statusClass = '';
        switch (driver.status) {
            case 'available':
                statusClass = 'status-available';
                break;
            case 'assigned':
                statusClass = 'status-assigned';
                break;
            case 'unavailable':
                statusClass = 'status-unavailable';
                break;
        }
        
        row.innerHTML = `
            <td>
                <a href="driver-profile.html?id=${driver.id}" class="profile-link">
                    <i class="fas fa-user-circle profile-icon" data-id="${driver.id}"></i>
                </a>
            </td>
            <td>${driver.id}</td>
            <td>${driver.name}</td>
            <td>${driver.phone}</td>
            <td>${driver.email}</td>
            <td><span class="status ${statusClass}">${driver.status}</span></td>
            <td>${driver.vehicleType}</td>
            <td>
                ${driver.status === 'available' ? 
                `<button class="action-btn forward-btn" data-id="${driver.id}">Forward</button>` : ''}
                <button class="action-btn delete-btn" data-id="${driver.id}">Delete</button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to action buttons
    const forwardBtns = document.querySelectorAll('.forward-btn');
    forwardBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const driverId = this.getAttribute('data-id');
            openForwardModal(driverId);
        });
    });
    
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const driverId = this.getAttribute('data-id');
            deleteDriver(driverId);
        });
    });

    // Add event listeners to profile icons
    const profileIcons = document.querySelectorAll('.profile-icon');
    profileIcons.forEach(icon => {
        icon.addEventListener('click', function(e) {
            // Stop propagation to prevent the link from activating
            e.stopPropagation();
            const driverId = this.getAttribute('data-id');
            openDriverProfile(driverId);
        });
    });
}

// Delete driver
function deleteDriver(driverId) {
    // Confirm deletion
    if (!confirm('Are you sure you want to delete this driver?')) {
        return;
    }
    
    // Call the API to delete the driver
    fetch(`api/delete_driver.php?id=${driverId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove driver from array
            transporters = transporters.filter(d => d.id !== parseInt(driverId));
            
            // Update UI
            renderTransporters(transporters);
            updateStats();
            
            alert('Driver has been deleted successfully.');
        } else {
            console.error('Error deleting driver:', data.error);
            alert('Failed to delete driver: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error deleting driver:', error);
        alert('Failed to delete driver. Please try again.');
    });
}

// Update statistics
function updateStats() {
    const totalCount = transporters.length;
    const availableCount = transporters.filter(d => d.status === 'available').length;
    const assignedCount = transporters.filter(d => d.status === 'assigned').length;
    const newCount = transporters.filter(d => d.isNew).length;
    
    document.getElementById('total-transporters').textContent = totalCount;
    document.getElementById('available-today').textContent = availableCount;
    document.getElementById('assigned-orders').textContent = assignedCount;
    document.getElementById('new-transporters').textContent = newCount;
}

// Load mock data for demonstration
function loadMockData() {
    console.log("Loading mock data");
    transporters = [
        {
            id: 1,
            name: 'John Smith',
            phone: '555-123-4567',
            email: 'jsmith@skyagro.com',
            status: 'available',
            vehicleType: 'Heavy Truck (Class 8)',
            isNew: false,
            // Additional metrics
            metrics: {
                tripsCompleted: 247,
                totalDistance: 15873,
                drivingTime: 738,
                idleTime: 92,
                breaksTaken: 183
            },
            // Additional details for profile
            profileDetails: {
                location: 'Chicago, IL',
                vehicleInfo: 'Peterbilt 579 (T-123)',
                driverSince: 'Jan 2019',
                licenseNumber: 'DL-456789',
                licenseExpiry: '12/15/2025',
                certifications: [
                    { name: 'Commercial Driver Certification', number: 'CDC-78901', expiry: '08/30/2024', status: 'valid' },
                    { name: 'Hazardous Materials Endorsement', number: 'HME-12345', expiry: '05/10/2023', status: 'expired' },
                    { name: 'Medical Certificate', number: 'MC-23456', expiry: '11/22/2024', status: 'valid' }
                ],
                recentTrips: [
                    { id: 'T-1089', date: '15 Jun 2023', origin: 'SkyAgro Warehouse', destination: 'Farm B - 456 Country Lane', distance: '78 km', duration: '1h 45m', status: 'completed' },
                    { id: 'T-1085', date: '13 Jun 2023', origin: 'SkyAgro Supply Center', destination: 'Multiple Farms (Route #32)', distance: '145 km', duration: '4h 20m', status: 'completed' },
                    { id: 'T-1076', date: '10 Jun 2023', origin: 'Farm A - 123 Rural Road', destination: 'SkyAgro Processing Plant', distance: '92 km', duration: '2h 10m', status: 'completed' }
                ]
            }
        },
        {
            id: 2,
            name: 'Sarah Johnson',
            phone: '555-234-5678',
            email: 'sjohnson@skyagro.com',
            status: 'assigned',
            vehicleType: 'Medium Truck (Class 6)',
            isNew: false,
            // Additional metrics
            metrics: {
                tripsCompleted: 185,
                totalDistance: 12350,
                drivingTime: 542,
                idleTime: 78,
                breaksTaken: 156
            },
            // Additional details for profile
            profileDetails: {
                location: 'Milwaukee, WI',
                vehicleInfo: 'Freightliner M2 (T-245)',
                driverSince: 'Mar 2020',
                licenseNumber: 'DL-789012',
                licenseExpiry: '06/22/2024',
                certifications: [
                    { name: 'Commercial Driver Certification', number: 'CDC-45678', expiry: '04/15/2024', status: 'valid' },
                    { name: 'Tanker Endorsement', number: 'TE-56789', expiry: '07/30/2024', status: 'valid' },
                    { name: 'Medical Certificate', number: 'MC-34567', expiry: '09/18/2023', status: 'valid' }
                ],
                recentTrips: [
                    { id: 'T-1083', date: '14 Jun 2023', origin: 'SkyAgro Distribution Center', destination: 'Regional Market #2', distance: '135 km', duration: '2h 40m', status: 'completed' },
                    { id: 'T-1079', date: '11 Jun 2023', origin: 'Farm C - 789 Rural Highway', destination: 'SkyAgro Processing Plant', distance: '110 km', duration: '2h 15m', status: 'completed' },
                    { id: 'T-1070', date: '08 Jun 2023', origin: 'SkyAgro Warehouse', destination: 'Multiple Farms (Route #18)', distance: '168 km', duration: '3h 50m', status: 'completed' }
                ]
            }
        },
        {
            id: 3,
            name: 'Michael Brown',
            phone: '555-345-6789',
            email: 'mbrown@skyagro.com',
            status: 'available',
            vehicleType: 'Light Truck (Class 4)',
            isNew: true,
            // Additional metrics
            metrics: {
                tripsCompleted: 32,
                totalDistance: 2780,
                drivingTime: 124,
                idleTime: 18,
                breaksTaken: 28
            },
            // Additional details for profile
            profileDetails: {
                location: 'Detroit, MI',
                vehicleInfo: 'Isuzu NPR (T-387)',
                driverSince: 'Apr 2023',
                licenseNumber: 'DL-234567',
                licenseExpiry: '09/30/2025',
                certifications: [
                    { name: 'Commercial Driver Certification', number: 'CDC-98765', expiry: '04/02/2025', status: 'valid' },
                    { name: 'Medical Certificate', number: 'MC-87654', expiry: '03/15/2024', status: 'valid' }
                ],
                recentTrips: [
                    { id: 'T-1081', date: '12 Jun 2023', origin: 'SkyAgro Supply Center', destination: 'Farm D - 234 Valley Road', distance: '58 km', duration: '1h 10m', status: 'completed' },
                    { id: 'T-1072', date: '09 Jun 2023', origin: 'Farm B - 456 Country Lane', destination: 'SkyAgro Warehouse', distance: '75 km', duration: '1h 35m', status: 'completed' },
                    { id: 'T-1063', date: '05 Jun 2023', origin: 'SkyAgro Processing Plant', destination: 'Local Distribution Center', distance: '42 km', duration: '50m', status: 'completed' }
                ]
            }
        },
        {
            id: 4,
            name: 'Lisa Davis',
            phone: '555-456-7890',
            email: 'ldavis@skyagro.com',
            status: 'unavailable',
            vehicleType: 'Heavy Truck (Class 7)',
            isNew: false,
            // Additional metrics
            metrics: {
                tripsCompleted: 203,
                totalDistance: 14250,
                drivingTime: 624,
                idleTime: 86,
                breaksTaken: 167
            },
            // Additional details for profile
            profileDetails: {
                location: 'Indianapolis, IN',
                vehicleInfo: 'Kenworth T680 (T-492)',
                driverSince: 'Aug 2019',
                licenseNumber: 'DL-345678',
                licenseExpiry: '11/05/2024',
                certifications: [
                    { name: 'Commercial Driver Certification', number: 'CDC-56789', expiry: '10/18/2024', status: 'valid' },
                    { name: 'Hazardous Materials Endorsement', number: 'HME-67890', expiry: '07/22/2024', status: 'valid' },
                    { name: 'Tanker Endorsement', number: 'TE-78901', expiry: '09/14/2024', status: 'valid' },
                    { name: 'Medical Certificate', number: 'MC-45678', expiry: '05/30/2023', status: 'valid' }
                ],
                recentTrips: [
                    { id: 'T-1024', date: '28 May 2023', origin: 'Farm E - 567 Mountain Pass', destination: 'SkyAgro Processing Plant', distance: '165 km', duration: '3h 15m', status: 'completed' },
                    { id: 'T-1018', date: '25 May 2023', origin: 'SkyAgro Supply Center', destination: 'Regional Distribution Hub', distance: '210 km', duration: '4h 05m', status: 'completed' },
                    { id: 'T-1010', date: '21 May 2023', origin: 'Chemical Supply Factory', destination: 'Multiple Farms (Route #42)', distance: '185 km', duration: '3h 45m', status: 'completed' }
                ]
            }
        },
        {
            id: 5,
            name: 'Robert Wilson',
            phone: '555-567-8901',
            email: 'rwilson@skyagro.com',
            status: 'available',
            vehicleType: 'Medium Truck (Class 5)',
            isNew: true,
            // Additional metrics
            metrics: {
                tripsCompleted: 48,
                totalDistance: 4380,
                drivingTime: 196,
                idleTime: 24,
                breaksTaken: 42
            },
            // Additional details for profile
            profileDetails: {
                location: 'Columbus, OH',
                vehicleInfo: 'Hino 268A (T-573)',
                driverSince: 'Jan 2023',
                licenseNumber: 'DL-123456',
                licenseExpiry: '03/18/2026',
                certifications: [
                    { name: 'Commercial Driver Certification', number: 'CDC-12345', expiry: '01/20/2025', status: 'valid' },
                    { name: 'Medical Certificate', number: 'MC-56789', expiry: '02/28/2024', status: 'valid' }
                ],
                recentTrips: [
                    { id: 'T-1075', date: '10 Jun 2023', origin: 'SkyAgro Warehouse', destination: 'Farm F - 890 Riverside Drive', distance: '82 km', duration: '1h 50m', status: 'completed' },
                    { id: 'T-1068', date: '07 Jun 2023', origin: 'Agricultural Supply Store', destination: 'SkyAgro Distribution Center', distance: '95 km', duration: '2h 05m', status: 'completed' },
                    { id: 'T-1054', date: '03 Jun 2023', origin: 'SkyAgro Supply Center', destination: 'Farm A - 123 Rural Road', distance: '68 km', duration: '1h 25m', status: 'completed' }
                ]
            }
        }
    ];
    
    // Store transporters in session storage for access from other pages
    sessionStorage.setItem('transporters', JSON.stringify(transporters));
    
    // Also set up mock loads data
    existingLoads = [
        {
            id: 1,
            title: 'Grain Shipment',
            pickup_location: 'Farm A - 123 Rural Road',
            delivery_location: 'SkyAgro Processing Plant',
            load_date: '2023-06-15',
            weight: 2500.00,
            cost: 1200.00,
            notes: 'Handle with care, organic grain'
        },
        {
            id: 2,
            title: 'Equipment Transport',
            pickup_location: 'SkyAgro Warehouse',
            delivery_location: 'Farm B - 456 Country Lane',
            load_date: '2023-06-18',
            weight: 1800.50,
            cost: 950.75,
            notes: 'Agricultural equipment for seasonal harvest'
        },
        {
            id: 3,
            title: 'Fertilizer Delivery',
            pickup_location: 'SkyAgro Supply Center',
            delivery_location: 'Multiple Farms (See route details)',
            load_date: '2023-06-20',
            weight: 3200.00,
            cost: 1450.00,
            notes: 'Route details attached. Delivery to 3 farms in the northern region.'
        }
    ];
    
    // Store loads in session storage too
    sessionStorage.setItem('existingLoads', JSON.stringify(existingLoads));
}

// Open driver profile modal
function openDriverProfile(driverId) {
    const driver = transporters.find(d => d.id === parseInt(driverId));
    
    if (!driver) {
        alert('Driver not found!');
        return;
    }
    
    // Navigate to the driver profile page
    window.location.href = `driver-profile.html?id=${driverId}&from_dashboard=true`;
} 