/**
 * Landing Page JavaScript
 * Funcionalidades interativas e animações
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavigation();
    initScrollEffects();
    initPlansLoader();

    initAnimations();
    initParticles();
});

/**
 * Navigation functionality
 */
function initNavigation() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    const navLinks = document.querySelectorAll('.nav-link');
    const header = document.getElementById('header');

    // Mobile menu toggle
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }

    // Smooth scrolling for navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            if (href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                
                if (target) {
                    // Close mobile menu
                    navToggle?.classList.remove('active');
                    navMenu?.classList.remove('active');
                    document.body.classList.remove('menu-open');
                    
                    // Smooth scroll to target
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update active link
                    updateActiveNavLink(href);
                }
            }
        });
    });

    // Header scroll effect
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Update active nav link on scroll
    window.addEventListener('scroll', throttle(updateActiveNavLinkOnScroll, 100));
}

/**
 * Update active navigation link
 */
function updateActiveNavLink(activeHref) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === activeHref) {
            link.classList.add('active');
        }
    });
}

/**
 * Update active nav link based on scroll position
 */
function updateActiveNavLinkOnScroll() {
    const sections = document.querySelectorAll('section[id]');
    const scrollPos = window.scrollY + 150;

    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute('id');

        if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
            updateActiveNavLink(`#${sectionId}`);
        }
    });
}

/**
 * Scroll effects and animations
 */
function initScrollEffects() {
    // Parallax effect for hero background
    const heroBg = document.querySelector('.hero-bg');
    
    if (heroBg) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            heroBg.style.transform = `translateY(${rate}px)`;
        });
    }

    // Fade in elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.feature-card, .plan-card, .about-feature, .contact-item').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Load and display plans from API
 */
async function initPlansLoader() {
    const plansGrid = document.getElementById('plansGrid');
    
    if (!plansGrid) return;

    try {
        // Show loading state
        plansGrid.innerHTML = `
            <div class="plans-loading">
                <div class="spinner"></div>
                <p>Carregando planos...</p>
            </div>
        `;

        // Fetch plans from public API
        const response = await fetch('/api-public-plans.php');
        const data = await response.json();

        if (data.success && data.plans) {
            renderPlans(data.plans);
        } else {
            throw new Error(data.error || 'Erro ao carregar planos');
        }
    } catch (error) {
        console.error('Erro ao carregar planos:', error);
        plansGrid.innerHTML = `
            <div class="plans-loading">
                <p style="color: var(--danger);">Erro ao carregar planos. Tente novamente mais tarde.</p>
            </div>
        `;
    }
}

/**
 * Render plans in the grid
 */
function renderPlans(plans) {
    const plansGrid = document.getElementById('plansGrid');
    
    if (!plansGrid || !plans.length) {
        plansGrid.innerHTML = `
            <div class="plans-loading">
                <p>Nenhum plano disponível no momento.</p>
            </div>
        `;
        return;
    }

    // Filter only active plans for public display
    const activePlans = plans.filter(plan => plan.is_active);
    
    if (!activePlans.length) {
        plansGrid.innerHTML = `
            <div class="plans-loading">
                <p>Nenhum plano disponível no momento.</p>
            </div>
        `;
        return;
    }

    // Sort plans: trial first, then by price
    activePlans.sort((a, b) => {
        if (a.is_trial && !b.is_trial) return -1;
        if (!a.is_trial && b.is_trial) return 1;
        return a.price - b.price;
    });

    // Find most popular plan (middle price range, not trial)
    const paidPlans = activePlans.filter(p => !p.is_trial);
    const popularPlan = paidPlans.length > 1 ? paidPlans[Math.floor(paidPlans.length / 2)] : null;

    plansGrid.innerHTML = activePlans.map(plan => {
        const isPopular = popularPlan && plan.id === popularPlan.id;
        const features = getPlanFeatures(plan);
        
        return `
            <div class="plan-card ${isPopular ? 'popular' : ''}" data-aos="fade-up">
                <div class="plan-badge ${plan.is_active ? 'active' : 'inactive'}">
                    ${plan.is_trial ? 'Gratuito' : 'Premium'}
                </div>
                
                <h3 class="plan-name">${plan.name}</h3>
                
                <div class="plan-price ${plan.price === 0 ? 'free' : ''}">
                    <span class="currency">R$</span>
                    <span class="value">${formatPrice(plan.price)}</span>
                </div>
                
                <p class="plan-description">
                    ${getPlanDescription(plan)}
                </p>
                
                <ul class="plan-features">
                    ${features.map(feature => `
                        <li><i class="fas fa-check"></i> ${feature}</li>
                    `).join('')}
                </ul>
                
                <a href="/register" class="btn btn-primary plan-action">
                    <i class="fas fa-rocket"></i>
                    ${plan.is_trial ? 'Começar Grátis' : 'Assinar Agora'}
                </a>
            </div>
        `;
    }).join('');

    // Re-observe new elements for animations
    document.querySelectorAll('.plan-card').forEach(el => {
        el.classList.add('fade-in-up');
    });
}

/**
 * Get plan features based on plan data
 */
function getPlanFeatures(plan) {
    const baseFeatures = [
        `${plan.duration_days} dias de acesso`,
        'Dashboard completo',
        'Gestão de clientes',
        'Relatórios básicos'
    ];

    if (!plan.is_trial) {
        baseFeatures.push(
            'Automação WhatsApp',
            'Múltiplos servidores',
            'Suporte prioritário',
            'Relatórios avançados'
        );
    }

    return baseFeatures;
}

/**
 * Get plan description
 */
function getPlanDescription(plan) {
    if (plan.is_trial) {
        return 'Teste todas as funcionalidades gratuitamente';
    }
    
    if (plan.duration_days <= 31) {
        return 'Ideal para começar seu negócio IPTV';
    } else if (plan.duration_days <= 93) {
        return 'Melhor custo-benefício para crescimento';
    } else {
        return 'Máximo desconto para negócios estabelecidos';
    }
}

/**
 * Format price for display
 */
function formatPrice(price) {
    if (price === 0) return '0';
    return price.toFixed(2).replace('.', ',');
}



/**
 * Initialize animations
 */
function initAnimations() {
    // Counter animation for hero stats
    const counters = document.querySelectorAll('.stat-number');
    
    const animateCounter = (counter) => {
        const target = counter.textContent;
        const isNumber = /^\d+$/.test(target);
        
        if (!isNumber) return;
        
        const targetNum = parseInt(target);
        const duration = 2000;
        const step = targetNum / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= targetNum) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 16);
    };

    // Observe counters for animation
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

/**
 * Initialize particle effects
 */
function initParticles() {
    const heroParticles = document.querySelector('.hero-particles');
    
    if (!heroParticles) return;

    // Create floating particles
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.cssText = `
            position: absolute;
            width: ${Math.random() * 4 + 2}px;
            height: ${Math.random() * 4 + 2}px;
            background: rgba(99, 102, 241, ${Math.random() * 0.5 + 0.2});
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: float ${Math.random() * 10 + 10}s ease-in-out infinite;
            animation-delay: ${Math.random() * 5}s;
        `;
        
        heroParticles.appendChild(particle);
    }
}

/**
 * Utility function: Throttle
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Utility function: Debounce
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * Smooth scroll polyfill for older browsers
 */
if (!('scrollBehavior' in document.documentElement.style)) {
    const smoothScrollPolyfill = () => {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                
                if (target) {
                    const targetPosition = target.offsetTop - 80;
                    const startPosition = window.pageYOffset;
                    const distance = targetPosition - startPosition;
                    const duration = 800;
                    let start = null;

                    const step = (timestamp) => {
                        if (!start) start = timestamp;
                        const progress = timestamp - start;
                        const progressPercentage = Math.min(progress / duration, 1);
                        
                        // Easing function
                        const ease = progressPercentage < 0.5 
                            ? 2 * progressPercentage * progressPercentage
                            : -1 + (4 - 2 * progressPercentage) * progressPercentage;
                        
                        window.scrollTo(0, startPosition + distance * ease);
                        
                        if (progress < duration) {
                            requestAnimationFrame(step);
                        }
                    };
                    
                    requestAnimationFrame(step);
                }
            });
        });
    };
    
    smoothScrollPolyfill();
}

/**
 * Handle page visibility changes
 */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Page is hidden - pause animations if needed
        document.body.classList.add('page-hidden');
    } else {
        // Page is visible - resume animations
        document.body.classList.remove('page-hidden');
    }
});

/**
 * Handle resize events
 */
window.addEventListener('resize', debounce(() => {
    // Close mobile menu on resize
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (window.innerWidth > 768) {
        navToggle?.classList.remove('active');
        navMenu?.classList.remove('active');
        document.body.classList.remove('menu-open');
    }
}, 250));

/**
 * Preload critical images
 */
function preloadImages() {
    const imageUrls = [
        // Add any critical images here
    ];
    
    imageUrls.forEach(url => {
        const img = new Image();
        img.src = url;
    });
}

// Initialize image preloading
preloadImages();

/**
 * Service Worker registration (if available)
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}