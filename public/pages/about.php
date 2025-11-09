<?php
// Set page metadata
$pageTitle = 'About Us';
$pageDescription = 'Learn about Akash Enterprise - Over 60 years of excellence in air conditioning solutions since 1995';
$pageKeywords = 'about us, company history, AC experts, air conditioning professionals, cooling solutions';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
?>

<style>
/* About Us Page - Modern & Professional Design */

/* Hero Section */
.about-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 40px 0 60px;
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.about-hero .container {
    position: relative;
    z-index: 1;
}

.hero-content {
    text-align: center;
    color: white;
    max-width: 900px;
    margin: -20px auto 0;
}

.hero-content h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin: 0 0 20px 0;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: fadeInUp 0.8s ease;
}

.hero-content .subtitle {
    font-size: 1.4rem;
    color: #cbd5e1;
    margin-bottom: 30px;
    line-height: 1.6;
    animation: fadeInUp 0.8s ease 0.2s both;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 60px;
    margin-top: 50px;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease 0.4s both;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: #3b82f6;
    display: block;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 1rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* Story Section */
.story-section {
    padding: 100px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
}

.story-content {
    display: flex;
    align-items: center;
    gap: 60px;
    margin-bottom: 80px;
}

.story-content.reverse {
    flex-direction: row-reverse;
}

.story-image {
    flex: 1;
    position: relative;
}

.story-image img {
    width: 100%;
    height: 500px;
    object-fit: cover;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
}

.story-image:hover img {
    transform: scale(1.02);
}

.story-text {
    flex: 1;
}

.story-text h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 25px;
    position: relative;
    padding-bottom: 20px;
}

.story-text h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    border-radius: 2px;
}

.story-text p {
    font-size: 1.1rem;
    color: #475569;
    line-height: 1.8;
    margin-bottom: 20px;
}

.story-text .highlight {
    color: #3b82f6;
    font-weight: 600;
}

/* Mission Vision Values */
.mvv-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
}

.mvv-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    margin-top: 60px;
}

.mvv-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    transition: all 0.3s ease;
}

.mvv-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(59, 130, 246, 0.5);
}

.mvv-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    font-size: 2rem;
}

.mvv-card h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
}

.mvv-card p {
    font-size: 1.05rem;
    line-height: 1.7;
    color: #cbd5e1;
}

/* Values Grid */
.values-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.value-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.2);
}

.value-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 1.5rem;
}

.value-card h4 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 10px;
}

.value-card p {
    font-size: 0.95rem;
    color: #64748b;
    line-height: 1.6;
}

/* Why Choose Us */
.why-choose-section {
    padding: 100px 0;
    background: white;
}

.section-header {
    text-align: center;
    margin-bottom: 60px;
}

.section-header h2 {
    font-size: 2.8rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.section-header p {
    font-size: 1.2rem;
    color: #64748b;
    max-width: 700px;
    margin: 0 auto;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.feature-card {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 40px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.feature-card:hover {
    background: white;
    border-color: #3b82f6;
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
    color: white;
    font-size: 1.8rem;
}

.feature-card h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.feature-card p {
    font-size: 1rem;
    color: #64748b;
    line-height: 1.7;
}

/* Timeline */
.timeline-section {
    padding: 100px 0;
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
}

.timeline {
    position: relative;
    max-width: 1000px;
    margin: 60px auto 0;
    padding: 0 20px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #3b82f6, #8b5cf6);
    transform: translateX(-50%);
}

.timeline-item {
    display: flex;
    margin-bottom: 60px;
    position: relative;
}

.timeline-item:nth-child(even) {
    flex-direction: row-reverse;
}

.timeline-content {
    width: 45%;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    position: relative;
}

.timeline-year {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    font-weight: 800;
    box-shadow: 0 0 0 10px rgba(255, 255, 255, 1);
}

.timeline-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.timeline-content p {
    font-size: 1rem;
    color: #64748b;
    line-height: 1.7;
}

/* Team Section (Optional) */
.team-section {
    padding: 100px 0;
    background: white;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.team-card {
    text-align: center;
    background: #f8f9fa;
    border-radius: 15px;
    padding: 30px;
    transition: all 0.3s ease;
}

.team-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
}

.team-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
}

.team-card h4 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 5px;
}

.team-card .role {
    font-size: 0.95rem;
    color: #3b82f6;
    margin-bottom: 15px;
}

.team-card p {
    font-size: 0.9rem;
    color: #64748b;
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    text-align: center;
}

.cta-section h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.cta-section p {
    font-size: 1.2rem;
    margin-bottom: 40px;
    opacity: 0.95;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-cta {
    padding: 16px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-cta-primary {
    background: white;
    color: #3b82f6;
}

.btn-cta-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    color: #3b82f6;
}

.btn-cta-secondary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-cta-secondary:hover {
    background: white;
    color: #3b82f6;
    transform: translateY(-3px);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Clients Section */
.clients-section {
    padding: 100px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
}

.clients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 60px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

.client-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    transition: all 0.4s ease;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.client-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.client-card:hover::before {
    transform: scaleX(1);
}

.client-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
}

.client-logo {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.client-card:hover .client-logo {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.client-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 10px;
}

.client-type {
    font-size: 0.9rem;
    color: #64748b;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 992px) {
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .story-content {
        flex-direction: column !important;
    }
    
    .mvv-grid,
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .values-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .clients-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .timeline::before {
        left: 30px;
    }
    
    .timeline-item {
        flex-direction: column !important;
    }
    
    .timeline-content {
        width: calc(100% - 80px);
        margin-left: 80px;
    }
    
    .timeline-year {
        left: 30px;
    }
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-stats {
        gap: 30px;
    }
    
    .mvv-grid,
    .features-grid,
    .values-grid,
    .team-grid,
    .clients-grid {
        grid-template-columns: 1fr;
    }
    
    .story-image img {
        height: 300px;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="hero-content">
            <h1>About Akash Enterprise</h1>
            <p class="subtitle">
            “Your Trusted Partner for Cooling Comfort Since 2018(Formally Powertech Engineers)”, Akash Enterprise has been a trusted name in air conditioning solutions. With decades of expertise, we specialize in delivering energy-efficient, reliable, and innovative cooling systems for homes, offices, and industries.
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number">30+</span>
                    <span class="stat-label">Years Experience</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">30K+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">200+</span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Services</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="story-section">
    <div class="container">
        <div class="story-content">
            <div class="story-image">
                <img src="<?php echo IMG_URL; ?>/placeholder-product.png" alt="Akash Enterprise - Our Journey">
            </div>
            <div class="story-text">
                <h2>Our Story</h2>
                <p>
                    Founded in <span class="highlight">1995</span> as Powertech Engineers from 2018 as Akash Enterprise, Akash Enterprise began with a simple goal — to bring reliable air conditioning and cooling solutions to homes and businesses. Over the years, we’ve transformed into one of India’s most trusted AC service providers, specializing in AC installation, repair, and maintenance for both residential and commercial spaces.
                </p>
                <p>
                    Throughout our journey,  <span class="highlight">  With more than 30 years</span>. of continuous service in the air conditioning industry, our reputation is built on trust, technology, and total customer satisfaction. 

                </p>
                <p>
                Today, Akash Enterprise stands at the intersection of tradition and innovation, combining old-school craftsmanship with modern air conditioning technologies to deliver superior cooling performance, energy savings, and long-lasting comfort for every customer. 
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Mission, Vision, Values -->
<section class="mvv-section">
    <div class="container">
        <div class="section-header text-white">
            <h2 style="color:white;">Our Foundation</h2>
            <p style="color: #cbd5e1;">The principles that guide everything we do</p>
        </div>
        
        <div class="mvv-grid">
            <div class="mvv-card">
                <div class="mvv-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Our Mission</h3>
                <p>
                    To provide superior air conditioning solutions that enhance comfort, improve quality of life, 
                    and exceed customer expectations through professionalism, integrity, and excellence.
                </p>
            </div>
            
            <div class="mvv-card">
                <div class="mvv-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Our Vision</h3>
                <p>
                    To be the most trusted and innovative air conditioning solutions provider, known for exceptional 
                    service quality, sustainable practices, and creating lasting value for our customers.
                </p>
            </div>
            
            <div class="mvv-card">
                <div class="mvv-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Our Promise</h3>
                <p>
                    Every interaction with us is marked by unwavering commitment to quality, transparent communication, 
                    and genuine care for your comfort and satisfaction.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="why-choose-section">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose Akash Enterprise?</h2>
            <p>Experience the difference that decades of expertise and dedication make</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <h3>30+ Years Legacy</h3>
                <p>Three decades of industry experience ensuring you receive proven, reliable solutions backed by extensive knowledge.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Expert Team</h3>
                <p>Certified technicians and consultants committed to delivering professional service and expert guidance.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Quality Guaranteed</h3>
                <p>We stand behind our products and services with comprehensive warranties and ongoing support.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>Energy Efficient</h3>
                <p>Eco-friendly solutions that reduce your carbon footprint while lowering your energy bills.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Round-the-clock customer support ensuring you're never left in discomfort.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <h3>Competitive Pricing</h3>
                <p>Fair, transparent pricing with flexible payment options and financing solutions.</p>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="story-section" style="background: #f8f9fa; padding: 80px 0;">
    <div class="container">
        <div class="section-header">
            <h2>Our Core Values</h2>
            <p>The pillars that support our commitment to excellence</p>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h4>Integrity</h4>
                <p>Honest, transparent dealings in every interaction</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h4>Excellence</h4>
                <p>Striving for perfection in every project</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h4>Innovation</h4>
                <p>Embracing new technologies and solutions</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-smile"></i>
                </div>
                <h4>Customer Focus</h4>
                <p>Your satisfaction is our top priority</p>
            </div>
        </div>
    </div>
</section>

<!-- Timeline -->
<section class="timeline-section">
    <div class="container">
        <div class="section-header">
            <h2>Our Journey</h2>
            <p>Milestones that shaped who we are today</p>
        </div>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-year">1995</div>
                <div class="timeline-content">
                    <h3>The Beginning</h3>
                    <p>Founded as Powertech Engineers, starting our journey in air conditioning solutions with a vision to provide quality comfort.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-year">1985</div>
                <div class="timeline-content">
                    <h3>Major Expansion</h3>
                    <p>Expanded operations to serve commercial clients, becoming a trusted partner for large-scale AC installations.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-year">2000</div>
                <div class="timeline-content">
                    <h3>Rebranding</h3>
                    <p>Evolved into Akash Enterprise, reflecting our growth and commitment to modern cooling solutions.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-year">2025</div>
                <div class="timeline-content">
                    <h3>Digital Transformation</h3>
                    <p>Launched online platform, making it easier for customers to access our services and products anytime, anywhere.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Clients Section -->
<section class="clients-section">
    <div class="container">
        <div class="section-header">
            <h2>Our Trusted Clients</h2>
            <p>Proud to serve leading organizations across industries</p>
        </div>
        
        <div class="clients-grid">
            <div class="client-card">
                <div class="client-logo">
                    <i class="fas fa-building"></i>
                </div>
                <h3 class="client-name">Reliance</h3>
                <p class="client-type">Enterprise Client</p>
            </div>
            
            <div class="client-card">
                <div class="client-logo">
                    <i class="fas fa-industry"></i>
                </div>
                <h3 class="client-name">Nayara</h3>
                <p class="client-type">Enterprise Client</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Experience the Akash Difference?</h2>
        <p>Join thousands of satisfied customers who trust us for their cooling needs</p>
        <div class="cta-buttons">
            <a href="<?php echo USER_URL; ?>/products/" class="btn-cta btn-cta-primary">
                <i class="fas fa-shopping-cart"></i> Browse Products
            </a>
            <a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="btn-cta btn-cta-secondary">
                <i class="fas fa-phone"></i> Contact Us
            </a>
        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
