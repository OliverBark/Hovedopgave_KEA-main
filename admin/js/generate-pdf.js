jQuery(document).ready(function ($) {
    $('#generate-shipping-label').on('click', function (e) {
        e.preventDefault();

        var order_id = $('#order-id').text();
        var length = $('#parcel-length').val();
        var width = $('#parcel-width').val();
        var height = $('#parcel-height').val();
        var weight = $('#parcel-weight').val();

        // AJAX call to generate PDF
        $.ajax({
            type: 'POST',
            url: pluginsdk_dhl_shipping_data.ajax_url,
            data: {
                action: 'pluginsdk_dhl_shipping_ajax_generate_pdf',
                security: pluginsdk_dhl_shipping_data.nonce,
                order_id: order_id,
                length: length,
                width: width,
                height: height,
                weight: weight
            },
            success: function (response) {
                console.log('AJAX Request Success:', response);

                if (response && response.success) {
                    window.open(response.pdf_url, '_blank');
                } else {
                    console.error('Failed to generate return label PDF:', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Request Error:', error);
            }
        });
    });
});


