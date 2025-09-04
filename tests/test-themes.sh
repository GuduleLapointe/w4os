#!/bin/bash
# Test W4OS plugin with different themes

themes=("twentytwenty" "twentytwentyone" "twentytwentytwo" "twentytwentythree" "twentytwentyfour")

for theme in "${themes[@]}"; do
    echo "## $theme"
    echo
    
    # Activate theme
    wp theme activate "$theme" --quiet
    
    # Clear caches
    wp cache flush --quiet 2>/dev/null || true
    wp rewrite flush --quiet
    
    # Run profile tests
    php tests/test-profile.php | grep -A99 "^Test Summary:"
    
    echo ""
done
