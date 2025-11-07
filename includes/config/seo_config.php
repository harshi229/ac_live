<?php
/**
 * SEO Configuration File
 * Centralized SEO settings and Google Analytics configuration
 */

// Google Analytics Configuration
define('GA_MEASUREMENT_ID', 'GA_MEASUREMENT_ID'); // Replace with your actual GA4 ID
define('GOOGLE_SITE_VERIFICATION', ''); // Google Search Console verification code

// SEO Settings
define('SEO_SITE_NAME', 'Akash Enterprise - AC Sales & Service');
define('SEO_DEFAULT_DESCRIPTION', 'Professional air conditioning sales, installation, and maintenance services since 1962');
define('SEO_DEFAULT_KEYWORDS', 'air conditioning, AC sales, AC installation, AC maintenance, split AC, window AC, commercial AC, residential AC, AMC services');

// Social Media URLs
define('FACEBOOK_URL', 'https://www.facebook.com/akashenterprise');
define('INSTAGRAM_URL', 'https://www.instagram.com/akashenterprise');
define('TWITTER_URL', 'https://www.twitter.com/akashenterprise');
define('LINKEDIN_URL', 'https://www.linkedin.com/company/akashenterprise');

// Contact Information for Schema
define('COMPANY_PHONE', '+91-98792-35475');
define('COMPANY_EMAIL', 'aakashjamnagar@gmail.com');
define('COMPANY_ADDRESS', 'Your Business Address, City, State, India');

// SEO Helper Functions
function getSEOKeywords($page = '') {
    $keywords = [
        'home' => 'air conditioning, AC sales, AC installation, AC maintenance, split AC, window AC, commercial AC, residential AC, AMC services, best AC dealer',
        'products' => 'AC products, air conditioner, buy AC online, split AC, window AC, commercial AC, residential AC, inverter AC, energy efficient AC',
        'about' => 'about us, company history, AC experts, air conditioning professionals, cooling solutions, trusted AC dealer',
        'contact' => 'contact us, AC support, air conditioning help, customer service, AC consultation, AC repair service',
        'services' => 'AC installation, AC repair, AC maintenance, AMC plans, air conditioning service, professional AC service'
    ];
    
    return $keywords[$page] ?? SEO_DEFAULT_KEYWORDS;
}

function getSEODescription($page = '') {
    $descriptions = [
        'home' => 'Welcome to Akash Enterprise - Your trusted partner for premium air conditioning solutions since 1962. Quality AC sales, installation, and maintenance services.',
        'products' => 'Browse our complete range of air conditioning products including residential, commercial, and cassette AC units from top brands.',
        'about' => 'Learn about Akash Enterprise - Over 60 years of excellence in air conditioning solutions since 1962. Professional AC experts.',
        'contact' => 'Get in touch with Akash Enterprise - We\'re here to help with all your air conditioning needs. Professional AC consultation.',
        'services' => 'Professional air conditioning services including installation, repair, maintenance, and AMC plans. Expert AC technicians.'
    ];
    
    return $descriptions[$page] ?? SEO_DEFAULT_DESCRIPTION;
}

function generateMetaTags($page = '', $customTitle = '', $customDescription = '', $customKeywords = '') {
    $title = $customTitle ?: ucfirst($page) . ' - ' . SEO_SITE_NAME;
    $description = $customDescription ?: getSEODescription($page);
    $keywords = $customKeywords ?: getSEOKeywords($page);
    
    return [
        'title' => $title,
        'description' => $description,
        'keywords' => $keywords
    ];
}
?>
