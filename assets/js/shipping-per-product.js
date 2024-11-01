jQuery(document).ready(function ($) {
    var i = 1;

    $('#_bswspp_per_product_shipping').click(function () {
        var thisCheck = $(this);
        if (thisCheck.is(':checked')) {
            $("#woocommerce-product-data .bswspp_per_product_shipping_rules").css("display", "block");
        } else {
            $("#woocommerce-product-data .bswspp_per_product_shipping_rules").css("display", "none");
        }
    });

    let isChecked = $('#_bswspp_per_product_shipping').is(':checked');
    if (isChecked) {
        $("#woocommerce-product-data .bswspp_per_product_shipping_rules").css("display", "block");
    }

    $('.bswspp_per_product_shipping_rules .insert').click(function () {
        var ele = $(this).closest(".bswspp_per_product_shipping_rules").find("tbody");
        var postid = $(this).data("postid");

        var added_tr = '<tr class="custom-fields-row draggable-row" data-order="' + i + '">\
                         <td class="sort draggable-handle">&nbsp;</td>\
                         <td class="zone draggable-handle">\
                          <input type="text" placeholder="*" name="per_product_zone['+ postid + '][new][]" />\
                         </td>\
                         <td class="country draggable-handle">\
                          <input type="text" placeholder="*" name="per_product_country['+ postid + '][new][]" />\
                         </td>\
                         <td class="state draggable-handle">\
                          <input type="text" placeholder="*" name="per_product_state['+ postid + '][new][]" />\
                         </td>\
                         <td class="postcode draggable-handle">\
                          <textarea placeholder="*" name="per_product_postcode['+ postid + '][new][]"></textarea>\
                         </td>\
                         <td class="cost draggable-handle">\
                          <input type="text" placeholder="0.00" name="per_product_cost['+ postid + '][new][]" />\
                         </td>\
                         <td class="item_cost draggable-handle">\
                          <input type="text" placeholder="0.00" name="per_product_item_cost['+ postid + '][new][]" /></td>\
                       </tr>';

        ele.append(added_tr);
        i++;
    });

    $('.bswspp_per_product_shipping_rules .remove').click(function () {
        var ele = $(this).closest(".bswspp_per_product_shipping_rules").find("tbody tr:last");
        ele.remove();
        i--;
    });

    $('.bswspp_per_product_shipping_rules .copy').click(function () {
        var ele = $(this).closest(".bswspp_per_product_shipping_rules").find("span#copy_from_product_id");
        ele.css("display", "inline-block");
    });

    $('.bswspp_per_product_shipping_rules span#copy_from_product_id .copy-action').click(function () {
        var product_id_to = $(this).data("postid");
        var product_id_from = $(".bswspp_per_product_shipping_rules span#copy_from_product_id input#product_id").val() || '';

        if (product_id_from) {
            var data = {
                action: 'get_product_shipping_rules',
                product_id_from: product_id_from,
                product_id_to: product_id_to,
                ajax_nonce: BSWSPP_Shipping_Per_Product_params.ajax_nonce
            }

            jQuery.ajax({
                type: 'POST',
                url: BSWSPP_Shipping_Per_Product_params.ajax_url,
                data: data,
                success: function (response) {
                    var ele = $(".bswspp_per_product_shipping_rules").find("tbody");
                    ele.html(response.data.html);
                }
            });
        }
    });


    // Sortable Table Rows
    $('.sortable-table-body').each(function () {
        $(this).sortable({
            items: '.draggable-row',
            connectWith: '.sortable-table-body',
            handle: '.draggable-handle',
            update: function (event, ui) {
                updateOrder($(this));
            }
        });
    });

    // Set cursor style when dragging starts
    $(".draggable-row").mousedown(function () {
        $(this).css("cursor", "grabbing");
    });

    // Set cursor style back to default when dragging stops
    $(".draggable-row").mouseup(function () {
        $(this).css("cursor", "grab");
    });

    // Function to update the data-order attribute based on the new order
    function updateOrder($table) {
        $table.find(".draggable-row").each(function (index) {
            $(this).attr("data-order", index + 1);
        });
    }
});