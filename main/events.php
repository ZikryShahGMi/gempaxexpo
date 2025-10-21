<?php
session_start();
include('db_connect.php')
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEMPAX EXPO — Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styling/events-styles.css">
</head>

<body>
    <!-- Header Section -->
    <header class="site-header">
        <div class="container header">
            <a class="logo" href="index.php">GEMPAX EXPO</a>
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="events.php" class="active">Events</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- If user is logged in -->
                    <div class="dropdown">
                        <button class="dropbtn">
                            <?= htmlspecialchars($_SESSION['fullname']) ?> ▾
                        </button>
                        <div class="dropdown-content">
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- If user is NOT logged in -->
                    <div class="dropdown">
                        <button class="dropbtn">Account ▾</button>
                        <div class="dropdown-content">
                            <a href="signin.php">Sign In</a>
                            <a href="signup.php">Sign Up</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="language-switcher">
                <button class="lang-btn">EN</button>
            </div>
        </div>
    </header>

    <!-- Events Section -->
    <section class="events-section">
        <div class="events-header" data-reveal>
            <h2 data-i18n="events.title">Upcoming Events</h2>
            <p>Discover all GEMPAX EXPO events happening worldwide</p>
        </div>

        <!-- Search Section -->
        <div class="search-section" data-reveal>
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="event-search"
                    placeholder="Search events by name, location, or date...">
            </div>
            <div class="search-results">
                <span id="results-count">Showing all events</span>
            </div>
        </div>

        <!-- View Controls -->
        <div class="view-controls" data-reveal>
            <button class="view-btn active" id="grid-view-btn">
                <i class="fas fa-th"></i> Grid View
            </button>
            <button class="view-btn" id="list-view-btn">
                <i class="fas fa-list"></i> List View
            </button>
        </div>

        <!-- Events Container -->
        <div class="events-grid" id="events-container">
            <!-- Event cards will be dynamically rendered by JavaScript -->
        </div>
    </section>

    <!-- Event Detail Modal -->
    <div class="modal" id="event-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Event Details</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Modal content will be dynamically populated -->
            </div>
            <div class="modal-footer">
                <button class="view-btn-small" id="close-modal-btn">Close</button>
                <button class="book-btn" id="modal-book-btn">Book Tickets</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p data-i18n="footer">© GEMPAX EXPO — 2025</p>
        </div>
    </footer>

    <?php
    include('db_connect.php');

    // Fetch events from database with venue information
    $events_sql = "SELECT c.*, v.venueName, v.venueLocation, v.venueCity, v.venueCountry, v.venueCapacity 
               FROM concert c 
               LEFT JOIN venue v ON c.venueID = v.venueID 
               WHERE c.status = 'upcoming' OR c.status IS NULL
               ORDER BY c.concertDate";
    $events_result = $conn->query($events_sql);
    $events = [];

    if ($events_result && $events_result->num_rows > 0) {
        while ($row = $events_result->fetch_assoc()) {
            // Get ticket types for this concert
            $ticket_sql = "SELECT ticketName as type, ticketPrice as price, ticketDescription as description 
                       FROM tickettypes 
                       WHERE concertID = ?";
            $ticket_stmt = $conn->prepare($ticket_sql);
            $ticket_stmt->bind_param("i", $row['concertID']);
            $ticket_stmt->execute();
            $ticket_result = $ticket_stmt->get_result();
            $tickets = [];

            while ($ticket = $ticket_result->fetch_assoc()) {
                $tickets[] = $ticket;
            }

            // Format location
            $location = $row['venueName'];
            if ($row['venueCity']) {
                $location .= ', ' . $row['venueCity'];
            }
            if ($row['venueCountry'] && $row['venueCountry'] != $row['venueCity']) {
                $location .= ', ' . $row['venueCountry'];
            }

            $events[] = [
                'id' => (int) $row['concertID'], // Ensure ID is integer
                'title' => $row['concertName'],
                'date' => date('M j, Y', strtotime($row['concertDate'])),
                'location' => $location,
                'description' => $row['description'] ?? 'An amazing GEMPAX EXPO experience',
                'longDescription' => $row['description'] ?? 'Join us for an unforgettable GEMPAX EXPO experience.',
                'price' => 'RM ' . $row['ticketPrice'],
                'features' => ['Main Stage', 'VIP Lounge', 'Special Performances'],
                'image' => $row['image_url'] ?? '../visuals/default.jpg',
                'tickets' => $tickets,
                'details' => [
                    'duration' => ($row['duration_minutes'] ?? 180) . ' minutes',
                    'ageRestriction' => $row['age_restriction'] ?? 'All ages',
                    'language' => 'Multilingual',
                    'capacity' => number_format($row['venueCapacity'] ?? 50000)
                ]
            ];
        }
    } else {
        // Fallback to empty array
        $events = [];
    }

    // Debug: Check what events we're sending to JavaScript
    error_log("PHP Events: " . print_r($events, true));

    $events_json = json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>

    <script>
        // Event data from database
        const events = <?php echo $events_json; ?>;

        // Debug: Check if events are loaded
        console.log('Events loaded from database:', events);
        console.log('Number of events:', events.length);

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM loaded, initializing events...');

            let currentView = 'grid';
            let currentEvents = [...events];

            renderEvents(currentEvents, currentView);

            // Search functionality
            const searchInput = document.getElementById('event-search');
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                filterEvents(searchTerm, currentView);
            });

            // View toggle functionality
            document.getElementById('grid-view-btn').addEventListener('click', function () {
                setActiveView('grid');
                renderEvents(currentEvents, 'grid');
            });

            document.getElementById('list-view-btn').addEventListener('click', function () {
                setActiveView('list');
                renderEvents(currentEvents, 'list');
            });

            // Modal functionality
            const modal = document.getElementById('event-modal');
            const closeModalBtn = document.querySelector('.close-modal');
            const closeModalBtn2 = document.getElementById('close-modal-btn');
            const modalBookBtn = document.getElementById('modal-book-btn');

            console.log('Modal elements:', {
                modal: modal,
                closeModalBtn: closeModalBtn,
                closeModalBtn2: closeModalBtn2,
                modalBookBtn: modalBookBtn
            });

            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', closeModal);
            } else {
                console.error('Close modal button not found!');
            }

            if (closeModalBtn2) {
                closeModalBtn2.addEventListener('click', closeModal);
            } else {
                console.error('Close modal button 2 not found!');
            }

            if (modalBookBtn) {
                modalBookBtn.addEventListener('click', function () {
                    const eventId = this.getAttribute('data-event-id');
                    console.log('Modal book button clicked for event:', eventId);
                    bookEvent(eventId);
                });
            } else {
                console.error('Modal book button not found!');
            }

            // Close modal when clicking outside
            window.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            console.log('Event listeners attached successfully');
        });

        // Set active view
        function setActiveView(view) {
            const gridBtn = document.getElementById('grid-view-btn');
            const listBtn = document.getElementById('list-view-btn');

            if (view === 'grid') {
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            } else {
                gridBtn.classList.remove('active');
                listBtn.classList.add('active');
            }
        }

        // Render events to the page
        function renderEvents(eventsToRender, view) {
            const container = document.getElementById('events-container');
            const resultsCount = document.getElementById('results-count');

            console.log('Rendering events:', eventsToRender.length, 'in', view, 'view');

            // Clear previous classes
            container.className = view === 'grid' ? 'events-grid' : 'events-list';

            if (eventsToRender.length === 0) {
                container.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No events found</h3>
                    <p>Try adjusting your search terms or browse all events</p>
                </div>
            `;
                resultsCount.textContent = `No events found`;
                return;
            }

            // In renderEvents function, update the event card generation:
            container.innerHTML = eventsToRender.map(event => `
    <div class="event-card ${view}-view" data-reveal data-event-id="${event.id}">
        <div class="event-image">
            ${event.image ?
                    `<img src="${event.image}" alt="${event.title}" loading="lazy" onerror="this.style.display='none'; this.parentNode.innerHTML='🎵';">` :
                    '🎵'
                }
        </div>
        <div class="event-content">
            <div class="event-header">
                <div>
                    <h3 class="event-title">${event.title}</h3>
                    <div class="event-location">
                        <i class="fas fa-map-marker-alt"></i>
                        ${event.location}
                    </div>
                </div>
                <div class="event-date">${event.date}</div>
            </div>
            <p class="event-description">${event.description}</p>
            <div class="event-features">
                ${event.features.map(feature => `
                    <span class="feature-tag">${feature}</span>
                `).join('')}
            </div>
            <div class="event-footer">
                <div class="event-price">From ${event.price}</div>
                <div class="event-actions">
                    <button class="view-btn-small" onclick="console.log('Clicking view for ID:', ${event.id}); viewEvent(${event.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="book-btn" onclick="bookEvent(${event.id})">Book Now</button>
                </div>
            </div>
        </div>
    </div>
`).join('');

            resultsCount.textContent = `Showing ${eventsToRender.length} events`;

            // Debug: Check if buttons were created
            const viewButtons = container.querySelectorAll('.view-btn-small');
            const bookButtons = container.querySelectorAll('.book-btn');
            console.log('Created view buttons:', viewButtons.length);
            console.log('Created book buttons:', bookButtons.length);
        }

        // Filter events based on search term
        function filterEvents(searchTerm, view) {
            if (!searchTerm) {
                renderEvents(events, view);
                return;
            }

            const filteredEvents = events.filter(event => {
                return (
                    event.title.toLowerCase().includes(searchTerm) ||
                    event.location.toLowerCase().includes(searchTerm) ||
                    event.date.toLowerCase().includes(searchTerm) ||
                    event.description.toLowerCase().includes(searchTerm) ||
                    event.features.some(feature =>
                        feature.toLowerCase().includes(searchTerm)
                    )
                );
            });

            renderEvents(filteredEvents, view);
        }

        // View event details
        function viewEvent(eventId) {
            console.log('=== VIEW EVENT FUNCTION CALLED ===');
            console.log('Event ID:', eventId);
            console.log('All events:', events);

            const event = events.find(e => e.id === eventId);
            console.log('Found event:', event);

            if (!event) {
                console.error('❌ Event not found with ID:', eventId);
                alert('Event not found! Check console for details.');
                return;
            }

            const modalBody = document.getElementById('modal-body');
            const modalBookBtn = document.getElementById('modal-book-btn');
            const modal = document.getElementById('event-modal');

            console.log('Modal elements for display:', {
                modalBody: modalBody,
                modalBookBtn: modalBookBtn,
                modal: modal
            });

            if (!modalBody) {
                console.error('❌ Modal body not found!');
                return;
            }

            modalBody.innerHTML = `
            <div class="event-detail-header">
                <div class="event-detail-image">
                    ${event.image ?
                    `<img src="${event.image}" alt="${event.title}" onerror="this.style.display='none'; this.parentNode.innerHTML='🎵';">` :
                    '🎵'
                }
                </div>
                <div class="event-detail-info">
                    <h2 class="event-detail-title">${event.title}</h2>
                    <div class="event-detail-meta">
                        <span><i class="fas fa-calendar"></i> ${event.date}</span>
                        <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
                    </div>
                    <p class="event-detail-description">${event.longDescription}</p>
                </div>
            </div>
            
            <div class="event-detail-features">
                <div class="feature-card">
                    <i class="fas fa-clock"></i>
                    <h4>Duration</h4>
                    <p>${event.details.duration}</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user"></i>
                    <h4>Age Restriction</h4>
                    <p>${event.details.ageRestriction}</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-language"></i>
                    <h4>Language</h4>
                    <p>${event.details.language}</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h4>Capacity</h4>
                    <p>${event.details.capacity}</p>
                </div>
            </div>
            
            <div class="ticket-options">
                <h3>Ticket Options</h3>
                ${event.tickets.map(ticket => `
                    <div class="ticket-type">
                        <div class="ticket-info">
                            <h4>${ticket.type}</h4>
                            <p>${ticket.description}</p>
                        </div>
                        <div class="ticket-price">RM ${ticket.price}</div>
                    </div>
                `).join('')}
            </div>
        `;

            if (modalBookBtn) {
                modalBookBtn.setAttribute('data-event-id', eventId);
            }

            if (modal) {
                modal.style.display = 'block';
                console.log('✅ Modal should be visible now');
            } else {
                console.error('❌ Modal not found!');
            }
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('event-modal');
            if (modal) {
                modal.style.display = 'none';
                console.log('Modal closed');
            }
        }

        // Book event function - redirects to booking page
        function bookEvent(eventId) {
            console.log('Book event called for ID:', eventId);
            // Redirect to booking page with event ID as parameter
            window.location.href = `booking.php?event=${eventId}`;
        }

        // Scroll reveal animation
        const revealElements = document.querySelectorAll('[data-reveal]');
        const revealOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, revealOptions);

        revealElements.forEach(element => {
            revealObserver.observe(element);
        });
    </script>
</body>

</html>