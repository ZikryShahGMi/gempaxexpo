// script.js - FIXED VERSION with working booking functionality

document.addEventListener('DOMContentLoaded', function() {
    // Scroll to reveal data-reveal elements
    const revealElements = document.querySelectorAll('[data-reveal]');
    if (revealElements.length > 0) {
        const revealOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.2
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
    }

    // Language Switcher
    const langButtons = document.querySelectorAll('.lang-btn');
    
    // Define translations
    const translations = {
        en: {
            "nav.about": 'About',
            "nav.events": 'Events',
            "nav.gallery": 'Gallery',
            "nav.booking": 'Booking',
            "nav.contact": 'Contact',
            "about.title": 'About GEMPAX EXPO',
            "events.title": 'Upcoming Events',
            "gallery.title": 'Gallery',
            "booking.title": 'Book Your Tickets',
            "contact.title": 'Contact Us',
            "footer": '© GEMPAX EXPO — 2025'
        },
        ms: {
            "nav.about": 'Tentang',
            "nav.events": 'Acara',
            "nav.gallery": 'Galeri',
            "nav.booking": 'Tempahan',
            "nav.contact": 'Hubungi',
            "about.title": 'Tentang GEMPAX EXPO',
            "events.title": 'Acara Akan Datang',
            "gallery.title": 'Galeri',
            "booking.title": 'Tempah Tiket Anda',
            "contact.title": 'Hubungi Kami',
            "footer": '© GEMPAX EXPO — 2025'
        }
    };

    // Saved language preference 
    let currentLang = localStorage.getItem('preferredLang') || 'en';
    setLanguage(currentLang);

    // Apply translations and update toggle button text
    function setLanguage(lang) {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            // Cache the original text so we can fall back to it when a translation is missing
            if (!el.dataset.i18nDefault) el.dataset.i18nDefault = el.textContent;
            el.textContent = (translations[lang] && translations[lang][key]) ? translations[lang][key] : el.dataset.i18nDefault;
        });
        // Update all language buttons to show the alternate language code
        langButtons.forEach(btn => btn.textContent = lang === 'en' ? 'MS' : 'EN');
    }

    // Toggle and persist language
    function toggleLanguage() {
        currentLang = currentLang === 'en' ? 'ms' : 'en';
        setLanguage(currentLang);
        localStorage.setItem('preferredLang', currentLang);
    }

    // Attach click handler to each language button (if any exist)
    if (langButtons.length > 0) {
        langButtons.forEach(btn => btn.addEventListener('click', toggleLanguage));
    }

    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.dropdown');
    if (dropdowns.length > 0) {
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('mouseenter', function() {
                const content = this.querySelector('.dropdown-content');
                if (content) content.style.display = 'block';
            });
            
            dropdown.addEventListener('mouseleave', function() {
                const content = this.querySelector('.dropdown-content');
                if (content) content.style.display = 'none';
            });
        });
    }

    // ===== BOOKING PAGE FUNCTIONALITY =====
    function initializeBookingPage() {
        const ticketCounters = document.querySelectorAll('.ticket-counter');
        const totalAmountElement = document.getElementById('total-amount');
        
        if (ticketCounters.length > 0 && totalAmountElement) {
            // Initialize each ticket counter
            ticketCounters.forEach(counter => {
                const minusBtn = counter.querySelector('.minus');
                const plusBtn = counter.querySelector('.plus');
                const countSpan = counter.querySelector('.count');
                
                if (minusBtn && plusBtn && countSpan) {
                    let count = 0;
                    
                    // Update button states
                    function updateButtonStates() {
                        minusBtn.disabled = count === 0;
                        minusBtn.style.opacity = count === 0 ? '0.5' : '1';
                        minusBtn.style.cursor = count === 0 ? 'not-allowed' : 'pointer';
                    }
                    
                    // Minus button click
                    minusBtn.addEventListener('click', () => {
                        if (count > 0) {
                            count--;
                            countSpan.textContent = count;
                            updateButtonStates();
                            updateTotalAmount();
                        }
                    });
                    
                    // Plus button click
                    plusBtn.addEventListener('click', () => {
                        count++;
                        countSpan.textContent = count;
                        updateButtonStates();
                        updateTotalAmount();
                    });
                    
                    // Initialize button states
                    updateButtonStates();
                }
            });
            
            // Update total amount
            function updateTotalAmount() {
                let total = 0;
                
                document.querySelectorAll('.ticket-type').forEach(ticket => {
                    const countElement = ticket.querySelector('.count');
                    const priceElement = ticket.querySelector('.ticket-price');
                    
                    if (countElement && priceElement) {
                        const count = parseInt(countElement.textContent) || 0;
                        const priceText = priceElement.textContent;
                        // Extract numbers from price text (handles RM, ¥, S$ etc.)
                        const price = parseInt(priceText.replace(/[^\d]/g, '')) || 0;
                        total += count * price;
                    }
                });
                
                totalAmountElement.textContent = total;
            }
            
            // Initialize total amount
            updateTotalAmount();
            
            // Booking confirmation
            const confirmBtn = document.getElementById('confirm-booking');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const firstName = document.getElementById('first-name');
                    const lastName = document.getElementById('last-name');
                    const email = document.getElementById('email');
                    const phone = document.getElementById('phone');
                    const terms = document.getElementById('terms');
                    const totalAmount = document.getElementById('total-amount');
                    
                    // Validate form fields
                    if (!firstName || !lastName || !email || !phone || !terms) {
                        alert('Please fill in all required fields.');
                        return;
                    }
                    
                    if (!firstName.value.trim() || !lastName.value.trim() || !email.value.trim() || !phone.value.trim()) {
                        alert('Please fill in all required fields.');
                        return;
                    }
                    
                    // Email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email.value)) {
                        alert('Please enter a valid email address.');
                        return;
                    }
                    
                    if (!terms.checked) {
                        alert('Please agree to the Terms & Conditions.');
                        return;
                    }
                    
                    const total = parseInt(totalAmount.textContent) || 0;
                    if (total === 0) {
                        alert('Please select at least one ticket.');
                        return;
                    }
                    
                    // Success - show confirmation
                    alert(`🎉 Booking Confirmed!\n\nTotal: RM ${total}\nA confirmation email will be sent to ${email.value}\n\nThank you for choosing GEMPAX EXPO!`);
                    
                    // In a real application, you would submit the form data to a server here
                    // For demo purposes, we'll just reset the form
                    setTimeout(() => {
                        document.querySelector('.booking-form').reset();
                        document.querySelectorAll('.count').forEach(count => count.textContent = '0');
                        document.querySelectorAll('.minus').forEach(btn => {
                            btn.disabled = true;
                            btn.style.opacity = '0.5';
                        });
                        totalAmount.textContent = '0';
                    }, 2000);
                });
            }
            
            // Back button functionality
            const backBtn = document.querySelector('.btn-secondary');
            if (backBtn) {
                backBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to go back? Your current selections will be lost.')) {
                        window.history.back();
                    }
                });
            }
        }
    }

    // ===== CONTACT PAGE FUNCTIONALITY =====
    function initializeContactPage() {
        // FAQ Toggle Functionality
        const faqItems = document.querySelectorAll('.faq-item');
        if (faqItems.length > 0) {
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                if (question) {
                    question.addEventListener('click', () => {
                        // Close all other items
                        faqItems.forEach(otherItem => {
                            if (otherItem !== item) {
                                otherItem.classList.remove('active');
                            }
                        });
                        
                        // Toggle current item
                        item.classList.toggle('active');
                    });
                }
            });
        }

        // Contact Form submission
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Simple form validation
                const name = document.getElementById('name');
                const email = document.getElementById('email');
                const subject = document.getElementById('subject');
                const category = document.getElementById('category');
                const message = document.getElementById('message');
                
                if (name && email && subject && category && message && 
                    name.value && email.value && subject.value && category.value && message.value) {
                    
                    // Email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email.value)) {
                        alert('Please enter a valid email address.');
                        return;
                    }
                    
                    // Success message
                    alert('Thank you for your message! We will get back to you within 24 hours.');
                    contactForm.reset();
                } else {
                    alert('Please fill in all required fields.');
                }
            });
        }
    }

    // ===== GALLERY FUNCTIONALITY =====
    function initializeGallery() {
        // Lightbox functionality for gallery
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const closeBtn = document.querySelector('.close');
        const galleryImages = document.querySelectorAll('.gallery-grid img');
        
        if (lightbox && lightboxImg && galleryImages.length > 0) {
            galleryImages.forEach(img => {
                img.addEventListener('click', function() {
                    lightbox.style.display = 'block';
                    lightboxImg.src = this.src;
                    lightboxImg.alt = this.alt;
                });
            });
            
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    lightbox.style.display = 'none';
                });
            }
            
            lightbox.addEventListener('click', function(e) {
                if (e.target !== lightboxImg) {
                    lightbox.style.display = 'none';
                }
            });
        }

        // Simple modal gallery
        const thumb = document.querySelectorAll('.thumb');
        const modal = document.querySelector('.modal');
        if (modal && thumb.length > 0) {
            thumb.forEach(img => {
                img.addEventListener('click', () => {
                    modal.innerHTML = '<img src="' + img.src + '" alt="Gallery Image" />';
                    modal.setAttribute('aria-hidden', 'false');
                });
            });
            modal.addEventListener('click', () => {
                modal.setAttribute('aria-hidden', 'true');
            });
        }
    }

    // Initialize page-specific functionality based on current page
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    
    if (currentPage.includes('booking.html')) {
        initializeBookingPage();
    } else if (currentPage.includes('contact.html')) {
        initializeContactPage();
    } else if (currentPage.includes('gallery.html') || currentPage.includes('index.html')) {
        initializeGallery();
    }

    // Initialize common functionality for all pages
    console.log('GEMPAX EXPO - Website initialized successfully!');
});