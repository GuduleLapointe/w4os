document.addEventListener('DOMContentLoaded', () => {
    // Function to apply the color scheme
    const applyColorScheme = (e) => {
        if (e.matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
    };
    
    // Detect and apply user's preferred color scheme on initial load
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    applyColorScheme(darkModeMediaQuery);
    
    // Listen for changes in the user's color scheme preference
    darkModeMediaQuery.addEventListener('change', applyColorScheme);
});
