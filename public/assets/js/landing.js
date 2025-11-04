/**
 * Landing Page - JavaScript
 */

// Initialize AOS
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 100
});

// Header scroll effect
window.addEventListener('scroll', () => {
    const header = document.getElementById('header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Mobile menu toggle
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const navMenu = document.getElementById('navMenu');

if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        mobileMenuToggle.classList.toggle('active');
    });
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            // Close mobile menu if open
            navMenu.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
        }
    });
});

// Load plans from API
async function loadPlans() {
    try {
        const response = await fetch('/api-plans.php');
        const data = await response.json();
        
        if (data.success && data.plans) {
            renderPlans(data.plans.filter(plan => plan.status === 'active'));
        }
    } catch (error) {
        console.error('Error loading plans:', error);
    }
}

function renderPlans(plans) {
    const plansGrid = document.getElementById('plansGrid');
    if (!plans || plans.length === 0) {
        plansGrid.innerHTML = '<p>Nenhum plano disponível no momento.</p>';
        return;
    }
    
    plansGrid.innerHTML = plans.map((plan, index) => `
        <div class="plan-card ${index === 1 ? 'featured' : ''}" data-aos="fade-up" data-aos-delay="${index * 100}">
            ${index === 1 ? '<div class="plan-badge">Mais Popular</div>' : ''}
            <div class="plan-header">
                <h3>${plan.name}</h3>
                <div class="plan-price">
                    <span class="currency">R$</span>
                    <span class="amount">${parseFloat(plan.price).toFixed(2)}</span>
                    <span class="period">/mês</span>
                </div>
            </div>
            <ul class="plan-features">
                <li><i class="fas fa-check"></i> ${plan.max_clients || 'Ilimitado'} Clientes</li>
                <li><i class="fas fa-check"></i> ${plan.max_servers || 'Ilimitado'} Servidores</li>
                <li><i class="fas fa-check"></i> Automação WhatsApp</li>
                <li><i class="fas fa-check"></i> Pagamentos PIX</li>
                <li><i class="fas fa-check"></i> Dashboard Completo</li>
                <li><i class="fas fa-check"></i> Suporte 24/7</li>
            </ul>
            <a href="/register?plan=${plan.id}" class="btn-plan">
                Começar Agora
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    `).join('');
}

// Counter animation
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-count'));
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            element.textContent = target.toLocaleString('pt-BR');
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString('pt-BR');
        }
    }, 16);
}

// Observe demo stats
const demoObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counters = entry.target.querySelectorAll('[data-count]');
            counters.forEach(counter => animateCounter(counter));
            demoObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const demoSection = document.querySelector('.demo-stats');
if (demoSection) {
    demoObserver.observe(demoSection);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadPlans();
});
