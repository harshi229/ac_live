<?php
/**
 * Enhanced Schema Markup Generator
 * Adds product-specific structured data
 */

function generateProductSchema($product) {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => $product['product_name'],
        "description" => $product['description'] ?? $product['product_name'],
        "brand" => [
            "@type" => "Brand",
            "name" => $product['brand_name'] ?? 'Unknown'
        ],
        "category" => $product['category_name'] ?? 'Air Conditioning',
        "image" => [
            UPLOAD_URL . '/' . $product['product_image']
        ],
        "offers" => [
            "@type" => "Offer",
            "price" => $product['price'],
            "priceCurrency" => "INR",
            "availability" => $product['stock'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "seller" => [
                "@type" => "Organization",
                "name" => "Akash Enterprise"
            ]
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => $product['star_rating'] ?? 4.5,
            "reviewCount" => $product['review_count'] ?? 1
        ]
    ];
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function generateOrganizationSchema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => "Akash Enterprise",
        "description" => "Professional Air Conditioning Sales & Service since 1962",
        "url" => BASE_URL,
        "logo" => IMG_URL . "/full-logo.png",
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => "+91-98792-35475",
            "contactType" => "customer service",
            "availableLanguage" => ["English", "Hindi"]
        ],
        "address" => [
            "@type" => "PostalAddress",
            "addressCountry" => "IN",
            "addressLocality" => "Your City",
            "addressRegion" => "Your State"
        ],
        "sameAs" => [
            "https://www.facebook.com/akashenterprise",
            "https://www.instagram.com/akashenterprise"
        ]
    ];
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function generateBreadcrumbSchema($breadcrumbs) {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => []
    ];
    
    foreach ($breadcrumbs as $index => $crumb) {
        $schema["itemListElement"][] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $crumb['title'],
            "item" => $crumb['url']
        ];
    }
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>
