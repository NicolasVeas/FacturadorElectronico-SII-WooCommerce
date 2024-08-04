jQuery(document).ready(function($) {
  $('.nav-link.tab-link').on('click', function(e) {
    e.preventDefault();
    const href = $(this).attr('href');

    // Show loading modal
    $('#loadingModal').fadeIn();

    // Simulate delay (remove in production)
    setTimeout(function() {
      window.location.href = href; // Redirect after delay
    }, 1000); 
  });
});
