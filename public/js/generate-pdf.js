jQuery(document).ready(function ($) {



    $('#pluginsdk_dhl_shipping_button-ajax-generate').on('click', function (e) {
        e.preventDefault();

        var orderId = $('#order-id').text();

        $.ajax({
            url: pluginsdk_dhl_shipping_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pluginsdk_dhl_shipping_ajax_generate_pdf',
                order_id: orderId,
            },
            success: function (response) {
                if (response.success) {

                    window.location.href = response.pdf_url;
                } else {
                    console.error('PDF generation failed:', response.error);
                }
            },
            error: function (error) {
                console.error('Ajax request failed:', error);
            }
        });
    });
});
