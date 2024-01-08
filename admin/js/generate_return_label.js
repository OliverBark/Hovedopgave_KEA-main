jQuery(document).ready(function ($) {
   
    $('#generate-return-label-button').on('click', function (e) {
        e.preventDefault();
        handleGenerateReturnLabel();
    });

    function handleGenerateReturnLabel() {
        console.log('Button clicked!');
        console.log('Generating return label...');
        var order_id = $('#order-id').text();
        console.log('Order ID:', order_id);

        $.ajax({
            type: 'POST',
            url: pluginsdk_dhl_shipping_data.ajax_url,
            data: {
                action: 'pluginsdk_dhl_shipping_ajax_generate_return_label',
                security: pluginsdk_dhl_shipping_data.nonce,
                order_id: order_id,
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
    }
});
