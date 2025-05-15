// Driver Profile JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Hide back button for driver users
    checkUserRoleAndUpdateUI();
    
    // Tab functionality
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
    
    // Initialize charts
    initCharts();
    
    // Load driver data (would connect to an API in a real app)
    loadDriverData();
});

// Check if user is a driver or coordinator and update UI accordingly
function checkUserRoleAndUpdateUI() {
    const urlParams = new URLSearchParams(window.location.search);
    const isDriverView = urlParams.has('driver_view');
    const fromDashboard = urlParams.has('from_dashboard');
    
    // Get the back button
    const backButton = document.querySelector('.back-btn');
    if (!backButton) return;
    
    // If this is a driver view (after driver login) or there's no 'from_dashboard' parameter
    if (isDriverView || (!fromDashboard && !urlParams.has('id'))) {
        // Hide the back button for drivers
        backButton.style.display = 'none';
    } else if (fromDashboard) {
        // For coordinators, ensure the back button preserves their login state
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'index.html#dashboard-section';
            return false;
        });
    }
}

// Initialize all charts
function initCharts() {
    // Create charts
    createDistanceChart();
    createTimeUtilizationChart();
    createTripsChart();
    createBreakComplianceChart();
}

// Create Monthly Distance Chart
function createDistanceChart() {
    const ctx = document.getElementById('distanceChart').getContext('2d');
    
    const data = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Distance (km)',
            data: [1250, 1480, 1320, 1700, 1458, 1623],
            backgroundColor: 'rgba(52, 152, 219, 0.2)',
            borderColor: 'rgba(52, 152, 219, 1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    };
    
    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Distance (km)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Create Time Utilization Chart
function createTimeUtilizationChart() {
    const ctx = document.getElementById('timeUtilizationChart').getContext('2d');
    
    const data = {
        labels: ['Driving', 'Loading/Unloading', 'Breaks', 'Idle', 'Maintenance'],
        datasets: [{
            data: [62, 15, 10, 8, 5],
            backgroundColor: [
                'rgba(52, 152, 219, 0.7)',  // Blue - Driving
                'rgba(155, 89, 182, 0.7)',  // Purple - Loading/Unloading
                'rgba(46, 204, 113, 0.7)',  // Green - Breaks
                'rgba(241, 196, 15, 0.7)',  // Yellow - Idle
                'rgba(231, 76, 60, 0.7)'    // Red - Maintenance
            ],
            borderColor: [
                'rgba(52, 152, 219, 1)',
                'rgba(155, 89, 182, 1)',
                'rgba(46, 204, 113, 1)',
                'rgba(241, 196, 15, 1)',
                'rgba(231, 76, 60, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    const config = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.raw}%`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Create Weekly Trips Chart
function createTripsChart() {
    const ctx = document.getElementById('tripsChart').getContext('2d');
    
    const data = {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            label: 'Completed Trips',
            data: [5, 6, 8, 5],
            backgroundColor: 'rgba(46, 204, 113, 0.7)',
            borderColor: 'rgba(46, 204, 113, 1)',
            borderWidth: 1
        }]
    };
    
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Trips'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Create Break Compliance Chart
function createBreakComplianceChart() {
    const ctx = document.getElementById('breakComplianceChart').getContext('2d');
    
    const data = {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            label: 'Required Breaks',
            data: [12, 14, 16, 12],
            backgroundColor: 'rgba(52, 152, 219, 0.5)',
            borderColor: 'rgba(52, 152, 219, 1)',
            borderWidth: 1
        }, {
            label: 'Taken Breaks',
            data: [11, 14, 16, 12],
            backgroundColor: 'rgba(46, 204, 113, 0.5)',
            borderColor: 'rgba(46, 204, 113, 1)',
            borderWidth: 1
        }]
    };
    
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Breaks'
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Load driver data (simulated for demo purposes)
function loadDriverData() {
    // In a real app, this would fetch data from an API
    const driverId = getDriverIdFromUrl();
    
    // If no driver ID is specified, use a default
    if (!driverId) {
        console.log('No driver ID specified, using demo data');
        return;
    }
    
    // Try to get drivers data from sessionStorage if available
    let transporters = [];
    const storedTransporters = sessionStorage.getItem('transporters');
    if (storedTransporters) {
        transporters = JSON.parse(storedTransporters);
    }
    
    // Find the driver with the specified ID
    const driver = transporters.find(d => d.id === parseInt(driverId));
    
    if (driver) {
        // Update the UI with the driver's data
        updateDriverUI(driver);
    }
    
    // Fetch data from API regardless of session storage (to get latest data)
    fetch(`api/get_driver.php?id=${driverId}`)
        .then(response => response.json())
        .then(data => {
            // Check if the API returned valid data or an error
            if (data.error) {
                console.error('Error fetching driver data:', data.error);
                
                // If no data from session storage, use mock data
                if (!driver) {
                    const mockDriver = getMockDriverById(driverId);
                    if (mockDriver) {
                        updateDriverUI(mockDriver);
                    }
                }
                return;
            }
            
            // Success - update UI with API data
            updateDriverUI(data);
        })
        .catch(error => {
            console.error('Error fetching driver data from API:', error);
            
            // If no data from session storage, use mock data
            if (!driver) {
                const mockDriver = getMockDriverById(driverId);
                if (mockDriver) {
                    updateDriverUI(mockDriver);
                }
            }
        });
}

// Get mock driver data by ID
function getMockDriverById(driverId) {
    // Sample mock data for demo purposes
    const mockDrivers = [
        {
            id: 1,
            name: 'John Smith',
            phone: '555-123-4567',
            email: 'jsmith@skyagro.com',
            status: 'available',
            vehicleType: 'Heavy Truck (Class 8)',
            location: 'Chicago, IL',
            vehicleInfo: 'Peterbilt 579 (T-123)',
            driverSince: 'Jan 2019',
            metrics: {
                tripsCompleted: 247,
                totalDistance: "15,873",
                drivingTime: "738",
                idleTime: "92",
                breaksTaken: "183"
            }
        },
        {
            id: 2,
            name: 'Sarah Johnson',
            phone: '555-234-5678',
            email: 'sjohnson@skyagro.com',
            status: 'assigned',
            vehicleType: 'Medium Truck (Class 6)',
            location: 'Milwaukee, WI',
            vehicleInfo: 'Freightliner M2 (T-245)',
            driverSince: 'Mar 2020',
            metrics: {
                tripsCompleted: 185,
                totalDistance: "12,350",
                drivingTime: "542",
                idleTime: "78",
                breaksTaken: "156"
            }
        },
        {
            id: 3,
            name: 'Michael Brown',
            phone: '555-345-6789',
            email: 'mbrown@skyagro.com',
            status: 'available',
            vehicleType: 'Light Truck (Class 4)',
            location: 'Detroit, MI',
            vehicleInfo: 'Isuzu NPR (T-387)',
            driverSince: 'Apr 2023',
            metrics: {
                tripsCompleted: 32,
                totalDistance: "2,780",
                drivingTime: "124",
                idleTime: "18",
                breaksTaken: "28"
            }
        },
        {
            id: 4,
            name: 'Lisa Davis',
            phone: '555-456-7890',
            email: 'ldavis@skyagro.com',
            status: 'unavailable',
            vehicleType: 'Heavy Truck (Class 7)',
            location: 'Indianapolis, IN',
            vehicleInfo: 'Kenworth T680 (T-492)',
            driverSince: 'Aug 2019',
            metrics: {
                tripsCompleted: 203,
                totalDistance: "14,250",
                drivingTime: "624",
                idleTime: "86",
                breaksTaken: "167"
            }
        },
        {
            id: 5,
            name: 'Robert Wilson',
            phone: '555-567-8901',
            email: 'rwilson@skyagro.com',
            status: 'available',
            vehicleType: 'Medium Truck (Class 5)',
            location: 'Columbus, OH',
            vehicleInfo: 'Hino 268A (T-573)',
            driverSince: 'Jan 2023',
            metrics: {
                tripsCompleted: 48,
                totalDistance: "4,380",
                drivingTime: "196",
                idleTime: "24",
                breaksTaken: "42"
            }
        }
    ];
    
    return mockDrivers.find(d => d.id === parseInt(driverId));
}

// Update UI with driver data
function updateDriverUI(driver) {
    // Update header
    document.getElementById('driver-name').textContent = driver.name;
    const statusElement = document.querySelector('.driver-status');
    statusElement.textContent = driver.status.charAt(0).toUpperCase() + driver.status.slice(1);
    statusElement.className = `driver-status status-${driver.status}`;
    
    // Update sidebar
    document.getElementById('sidebar-driver-name').textContent = driver.name;
    document.getElementById('driver-id').textContent = `ID: ${driver.id}`;
    document.getElementById('driver-vehicle').textContent = `Vehicle: ${driver.vehicleInfo || driver.vehicleType}`;
    document.getElementById('driver-since').textContent = `Driver since: ${driver.driverSince}`;
    document.getElementById('driver-phone').textContent = driver.phone;
    document.getElementById('driver-email').textContent = driver.email;
    document.getElementById('driver-location').textContent = driver.location;
    
    // Update metrics
    document.getElementById('trips-completed').textContent = driver.metrics.tripsCompleted;
    document.getElementById('total-distance').textContent = `${driver.metrics.totalDistance} km`;
    document.getElementById('driving-time').textContent = `${driver.metrics.drivingTime} hours`;
    document.getElementById('idle-time').textContent = `${driver.metrics.idleTime} hours`;
    document.getElementById('breaks-taken').textContent = `${driver.metrics.breaksTaken} breaks`;
}

// Get driver ID from URL parameter
function getDriverIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

// Update filter date range
document.getElementById('date-range').addEventListener('change', function() {
    // In a real app, this would reload the trip history data based on the selected range
    console.log(`Date range changed to: ${this.value} days`);
});

// Trip search functionality
const searchBox = document.querySelector('.search-box input');
const searchButton = document.querySelector('.search-box button');

searchButton.addEventListener('click', function() {
    const searchTerm = searchBox.value.trim();
    if (searchTerm) {
        // In a real app, this would filter the trips based on the search term
        console.log(`Searching for: ${searchTerm}`);
    }
});

searchBox.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        searchButton.click();
    }
});

// Pagination buttons
const paginationButtons = document.querySelectorAll('.pagination button');
paginationButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all pagination buttons
        paginationButtons.forEach(btn => btn.classList.remove('active'));
        
        // Add active class to clicked button if it has a number
        if (!this.classList.contains('fa-chevron-left') && !this.classList.contains('fa-chevron-right')) {
            this.classList.add('active');
        }
        
        // In a real app, this would load the corresponding page of trips
        console.log(`Pagination button clicked: ${this.textContent}`);
    });
}); 