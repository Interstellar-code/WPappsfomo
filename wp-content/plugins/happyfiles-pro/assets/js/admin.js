jQuery(document).ready(function() {

  // Hide first use admin notification
  jQuery(document).on('click', '#hf-notification-first-use .notice-dismiss', function(e) {
    jQuery.ajax({
      method: 'POST',
      url: happyFiles.ajaxUrl,
      data: {
        action: 'happyfiles_dismiss_first_use_notification'
      }
    })
  })

  // Hide rate us admin notification
  jQuery(document).on('click', '#hf-notification-rate-plugin .notice-dismiss', function(e) {
    jQuery.ajax({
      method: 'POST',
      url: happyFiles.ajaxUrl,
      data: {
        action: 'happyfiles_dismiss_rate_us_notification'
      }
    })
  })

})