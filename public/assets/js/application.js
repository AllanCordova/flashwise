// FlashWise - Application JavaScript
// Minimal JavaScript for essential functionality

console.log('FlashWise application loaded');

// Prevent double submission on study answer forms
document.addEventListener('DOMContentLoaded', function() {
    const studyForms = document.querySelectorAll('.study-actions form');
    
    studyForms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                // Re-enable after 2 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                }, 1000);
            }
        });
    });
});

