/**
 * Mitzies Jerk - Public JavaScript
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/public/js
 */

(function($) {
    'use strict';

    /**
     * Mitzies Jerk Public Object
     */
    var MitziesJerk = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initMiniCart();
            this.initPreorderDateTime();
            this.initPaymentMethods();
            this.initQuantityControls();
        },

        /**
         * Bind Events
         */
        bindEvents: function() {
            // Add to cart
            $(document).on('click', '.mj-add-to-cart-btn', this.addToCart.bind(this));

            // Update cart quantity
            $(document).on('click', '.mj-quantity-btn', this.updateQuantity.bind(this));
            $(document).on('change', '.mj-quantity-input', this.updateCartItem.bind(this));

            // Remove from cart
            $(document).on('click', '.mj-remove-item', this.removeFromCart.bind(this));

            // Apply coupon
            $(document).on('click', '.mj-coupon-btn', this.applyCoupon.bind(this));

            // Category filter
            $(document).on('click', '.mj-category-filter', this.filterByCategory.bind(this));

            // Load more items
            $(document).on('click', '.mj-load-more-btn', this.loadMoreItems.bind(this));

            // Place order
            $(document).on('submit', '.mj-checkout-form', this.processCheckout.bind(this));

            // Track order
            $(document).on('submit', '.mj-tracking-form', this.trackOrder.bind(this));

            // Reorder
            $(document).on('click', '.mj-reorder-btn', this.reorder.bind(this));

            // Mini cart toggle
            $(document).on('click', '.mj-mini-cart-toggle', this.toggleMiniCart.bind(this));

            // Close mini cart when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.mj-mini-cart').length) {
                    $('.mj-mini-cart-dropdown').removeClass('active');
                }
            });
        },

        /**
         * Add to Cart
         */
        addToCart: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var itemId = $btn.data('item-id');
            var quantity = $btn.closest('.mj-food-item, .mj-single-food-item').find('.mj-quantity-input').val() || 1;
            var options = this.getItemOptions($btn.closest('.mj-food-item, .mj-single-food-item'));

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);
            $btn.html('<span class="mj-spinner"></span>');

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_add_to_cart',
                    nonce: mitzies_jerk_params.nonce,
                    item_id: itemId,
                    quantity: quantity,
                    options: options
                },
                success: function(response) {
                    if (response.success) {
                        $btn.removeClass('loading').addClass('added');
                        $btn.html('<span class="dashicons dashicons-yes"></span> ' + mitzies_jerk_params.i18n.added);

                        MitziesJerk.updateMiniCart(response.data.cart);
                        MitziesJerk.showNotice('success', response.data.message);

                        setTimeout(function() {
                            $btn.removeClass('added').prop('disabled', false);
                            $btn.html('<span class="dashicons dashicons-cart"></span> ' + mitzies_jerk_params.i18n.add_to_cart);
                        }, 2000);
                    } else {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.html('<span class="dashicons dashicons-cart"></span> ' + mitzies_jerk_params.i18n.add_to_cart);
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    $btn.html('<span class="dashicons dashicons-cart"></span> ' + mitzies_jerk_params.i18n.add_to_cart);
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Get Item Options
         */
        getItemOptions: function($container) {
            var options = {};

            $container.find('.mj-item-option').each(function() {
                var $option = $(this);
                var name = $option.attr('name');
                var value = $option.val();

                if ($option.is(':checkbox')) {
                    if ($option.is(':checked')) {
                        if (!options[name]) {
                            options[name] = [];
                        }
                        options[name].push(value);
                    }
                } else if ($option.is(':radio')) {
                    if ($option.is(':checked')) {
                        options[name] = value;
                    }
                } else {
                    options[name] = value;
                }
            });

            return options;
        },

        /**
         * Update Quantity (+ / - buttons)
         */
        updateQuantity: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $input = $btn.siblings('.mj-quantity-input');
            var currentVal = parseInt($input.val()) || 1;
            var min = parseInt($input.attr('min')) || 1;
            var max = parseInt($input.attr('max')) || 99;

            if ($btn.hasClass('mj-quantity-minus')) {
                if (currentVal > min) {
                    $input.val(currentVal - 1).trigger('change');
                }
            } else if ($btn.hasClass('mj-quantity-plus')) {
                if (currentVal < max) {
                    $input.val(currentVal + 1).trigger('change');
                }
            }
        },

        /**
         * Update Cart Item
         */
        updateCartItem: function(e) {
            var $input = $(e.currentTarget);
            var $row = $input.closest('tr');
            var itemKey = $row.data('item-key');
            var quantity = parseInt($input.val()) || 1;

            if (!itemKey) {
                return;
            }

            $row.addClass('loading');

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_update_cart',
                    nonce: mitzies_jerk_params.nonce,
                    item_key: itemKey,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.success) {
                        $row.removeClass('loading');
                        MitziesJerk.updateCartTable(response.data.cart);
                        MitziesJerk.updateMiniCart(response.data.cart);
                    } else {
                        $row.removeClass('loading');
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $row.removeClass('loading');
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Remove from Cart
         */
        removeFromCart: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $row = $btn.closest('tr');
            var itemKey = $row.data('item-key');

            if (!confirm(mitzies_jerk_params.i18n.confirm_remove)) {
                return;
            }

            $row.addClass('loading');

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_remove_from_cart',
                    nonce: mitzies_jerk_params.nonce,
                    item_key: itemKey
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            MitziesJerk.updateCartTable(response.data.cart);
                            MitziesJerk.updateMiniCart(response.data.cart);

                            if (response.data.cart.items.length === 0) {
                                MitziesJerk.showEmptyCart();
                            }
                        });
                        MitziesJerk.showNotice('success', response.data.message);
                    } else {
                        $row.removeClass('loading');
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $row.removeClass('loading');
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Apply Coupon
         */
        applyCoupon: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $input = $btn.siblings('.mj-coupon-input');
            var couponCode = $input.val().trim();

            if (!couponCode) {
                MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.enter_coupon);
                return;
            }

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_apply_coupon',
                    nonce: mitzies_jerk_params.nonce,
                    coupon_code: couponCode
                },
                success: function(response) {
                    $btn.removeClass('loading').prop('disabled', false);

                    if (response.success) {
                        MitziesJerk.updateCartTotals(response.data.cart);
                        MitziesJerk.showNotice('success', response.data.message);
                        $input.val('');
                    } else {
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Filter by Category
         */
        filterByCategory: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var category = $btn.data('category');
            var $container = $btn.closest('.mj-food-menu');

            $('.mj-category-filter').removeClass('active');
            $btn.addClass('active');

            $container.find('.mj-food-grid').addClass('loading');

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_filter_items',
                    nonce: mitzies_jerk_params.nonce,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        $container.find('.mj-food-grid').html(response.data.html).removeClass('loading');
                    } else {
                        $container.find('.mj-food-grid').removeClass('loading');
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $container.find('.mj-food-grid').removeClass('loading');
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Load More Items
         */
        loadMoreItems: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var page = $btn.data('page') + 1;
            var category = $btn.data('category');
            var $grid = $btn.closest('.mj-food-menu').find('.mj-food-grid');

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_load_more_items',
                    nonce: mitzies_jerk_params.nonce,
                    page: page,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        $grid.append(response.data.html);
                        $btn.data('page', page).removeClass('loading').prop('disabled', false);

                        if (!response.data.has_more) {
                            $btn.hide();
                        }
                    } else {
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Process Checkout
         */
        processCheckout: function(e) {
            e.preventDefault();

            var $form = $(e.currentTarget);
            var $btn = $form.find('.mj-place-order-btn');

            if (!this.validateCheckoutForm($form)) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);
            $btn.html('<span class="mj-spinner"></span> ' + mitzies_jerk_params.i18n.processing);

            var formData = $form.serialize();
            formData += '&action=mj_process_checkout&nonce=' + mitzies_jerk_params.nonce;

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else if (response.data.payment_url) {
                            window.location.href = response.data.payment_url;
                        }
                    } else {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.html(mitzies_jerk_params.i18n.place_order);
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    $btn.html(mitzies_jerk_params.i18n.place_order);
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Validate Checkout Form
         */
        validateCheckoutForm: function($form) {
            var isValid = true;
            var $requiredFields = $form.find('[required]');

            $requiredFields.each(function() {
                var $field = $(this);
                var value = $field.val().trim();

                if (!value) {
                    isValid = false;
                    $field.addClass('mj-field-error');
                } else {
                    $field.removeClass('mj-field-error');
                }
            });

            // Validate email
            var $email = $form.find('input[type="email"]');
            if ($email.length && $email.val()) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($email.val())) {
                    isValid = false;
                    $email.addClass('mj-field-error');
                }
            }

            // Validate phone
            var $phone = $form.find('input[name="phone"]');
            if ($phone.length && $phone.val()) {
                var phoneRegex = /^[\d\s\-\+\(\)]+$/;
                if (!phoneRegex.test($phone.val())) {
                    isValid = false;
                    $phone.addClass('mj-field-error');
                }
            }

            // Validate payment method
            var $paymentMethod = $form.find('input[name="payment_method"]:checked');
            if (!$paymentMethod.length) {
                isValid = false;
                MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.select_payment);
            }

            if (!isValid) {
                MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.fill_required);
            }

            return isValid;
        },

        /**
         * Track Order
         */
        trackOrder: function(e) {
            e.preventDefault();

            var $form = $(e.currentTarget);
            var $btn = $form.find('.mj-tracking-btn');
            var orderNumber = $form.find('.mj-tracking-input').val().trim();

            if (!orderNumber) {
                MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.enter_order_number);
                return;
            }

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_track_order',
                    nonce: mitzies_jerk_params.nonce,
                    order_number: orderNumber
                },
                success: function(response) {
                    $btn.removeClass('loading').prop('disabled', false);

                    if (response.success) {
                        $('.mj-tracking-result').html(response.data.html).show();
                    } else {
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Reorder
         */
        reorder: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var orderId = $btn.data('order-id');

            if (!confirm(mitzies_jerk_params.i18n.confirm_reorder)) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_reorder',
                    nonce: mitzies_jerk_params.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.cart_url;
                    } else {
                        $btn.removeClass('loading').prop('disabled', false);
                        MitziesJerk.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Initialize Mini Cart
         */
        initMiniCart: function() {
            // Get cart on page load
            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_get_cart',
                    nonce: mitzies_jerk_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        MitziesJerk.updateMiniCart(response.data.cart);
                    }
                }
            });
        },

        /**
         * Toggle Mini Cart
         */
        toggleMiniCart: function(e) {
            e.preventDefault();
            e.stopPropagation();

            $('.mj-mini-cart-dropdown').toggleClass('active');
        },

        /**
         * Update Mini Cart
         */
        updateMiniCart: function(cart) {
            var $miniCart = $('.mj-mini-cart');

            if (!$miniCart.length) {
                return;
            }

            // Update count
            $miniCart.find('.mj-mini-cart-count').text(cart.item_count);

            // Update items
            var itemsHtml = '';
            if (cart.items && cart.items.length > 0) {
                cart.items.forEach(function(item) {
                    itemsHtml += '<div class="mj-mini-cart-item">';
                    if (item.thumbnail) {
                        itemsHtml += '<img src="' + item.thumbnail + '" alt="' + item.name + '" />';
                    }
                    itemsHtml += '<div class="mj-mini-cart-item-info">';
                    itemsHtml += '<div class="mj-mini-cart-item-name">' + item.name + '</div>';
                    itemsHtml += '<div class="mj-mini-cart-item-qty">' + item.quantity + ' x ' + item.price_formatted + '</div>';
                    itemsHtml += '</div>';
                    itemsHtml += '<div class="mj-mini-cart-item-total">' + item.subtotal_formatted + '</div>';
                    itemsHtml += '</div>';
                });
            } else {
                itemsHtml = '<p class="mj-mini-cart-empty">' + mitzies_jerk_params.i18n.cart_empty + '</p>';
            }

            $miniCart.find('.mj-mini-cart-items').html(itemsHtml);

            // Update total
            $miniCart.find('.mj-mini-cart-total').text(cart.total_formatted);
        },

        /**
         * Update Cart Table
         */
        updateCartTable: function(cart) {
            // Update subtotals
            cart.items.forEach(function(item) {
                var $row = $('tr[data-item-key="' + item.key + '"]');
                $row.find('.mj-cart-item-subtotal').text(item.subtotal_formatted);
            });

            this.updateCartTotals(cart);
        },

        /**
         * Update Cart Totals
         */
        updateCartTotals: function(cart) {
            $('.mj-cart-subtotal-value').text(cart.subtotal_formatted);

            if (cart.discount > 0) {
                $('.mj-cart-discount-row').show();
                $('.mj-cart-discount-value').text('-' + cart.discount_formatted);
            } else {
                $('.mj-cart-discount-row').hide();
            }

            $('.mj-cart-total-value').text(cart.total_formatted);
        },

        /**
         * Show Empty Cart
         */
        showEmptyCart: function() {
            var emptyHtml = '<div class="mj-cart-empty">';
            emptyHtml += '<div class="mj-cart-empty-icon"><span class="dashicons dashicons-cart"></span></div>';
            emptyHtml += '<p class="mj-cart-empty-text">' + mitzies_jerk_params.i18n.cart_empty + '</p>';
            emptyHtml += '<a href="' + mitzies_jerk_params.menu_url + '" class="mj-cart-btn mj-continue-shopping-btn">' + mitzies_jerk_params.i18n.continue_shopping + '</a>';
            emptyHtml += '</div>';

            $('.mj-cart').html(emptyHtml);
        },

        /**
         * Initialize Pre-order Date/Time
         */
        initPreorderDateTime: function() {
            var $dateInput = $('.mj-preorder-date');
            var $timeInput = $('.mj-preorder-time');

            if (!$dateInput.length || !$timeInput.length) {
                return;
            }

            // Set min date (today or minimum preorder hours)
            var minHours = parseInt(mitzies_jerk_params.min_preorder_hours) || 2;
            var minDate = new Date();
            minDate.setHours(minDate.getHours() + minHours);

            var minDateStr = minDate.toISOString().split('T')[0];
            $dateInput.attr('min', minDateStr);

            // Set max date
            var maxDays = parseInt(mitzies_jerk_params.max_preorder_days) || 7;
            var maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + maxDays);

            var maxDateStr = maxDate.toISOString().split('T')[0];
            $dateInput.attr('max', maxDateStr);

            // Update available times when date changes
            $dateInput.on('change', function() {
                MitziesJerk.updateAvailableTimes($dateInput.val(), $timeInput);
            });
        },

        /**
         * Update Available Times
         */
        updateAvailableTimes: function(date, $timeInput) {
            var businessHours = mitzies_jerk_params.business_hours || {};
            var dayOfWeek = new Date(date).getDay();
            var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            var dayName = dayNames[dayOfWeek];

            var hours = businessHours[dayName] || { open: '09:00', close: '21:00' };

            if (hours.closed) {
                $timeInput.prop('disabled', true);
                MitziesJerk.showNotice('warning', mitzies_jerk_params.i18n.closed_day);
                return;
            }

            $timeInput.prop('disabled', false);
            $timeInput.attr('min', hours.open);
            $timeInput.attr('max', hours.close);

            // If selected date is today, update min time
            var today = new Date().toISOString().split('T')[0];
            if (date === today) {
                var minHours = parseInt(mitzies_jerk_params.min_preorder_hours) || 2;
                var minTime = new Date();
                minTime.setHours(minTime.getHours() + minHours);

                var minTimeStr = minTime.toTimeString().slice(0, 5);
                if (minTimeStr > hours.open) {
                    $timeInput.attr('min', minTimeStr);
                }
            }
        },

        /**
         * Initialize Payment Methods
         */
        initPaymentMethods: function() {
            $('.mj-payment-method').on('click', function() {
                var $method = $(this);
                var $radio = $method.find('input[type="radio"]');

                $('.mj-payment-method').removeClass('selected');
                $method.addClass('selected');
                $radio.prop('checked', true);
            });
        },

        /**
         * Initialize Quantity Controls
         */
        initQuantityControls: function() {
            // Prevent non-numeric input
            $(document).on('input', '.mj-quantity-input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        },

        /**
         * Show Notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="mj-notice mj-notice-' + type + '">' + message + '</div>');

            // Remove existing notices
            $('.mj-notice').remove();

            // Add notice
            if ($('.mj-checkout').length) {
                $('.mj-checkout').prepend($notice);
            } else if ($('.mj-cart').length) {
                $('.mj-cart').prepend($notice);
            } else {
                $('body').prepend($notice.css({ position: 'fixed', top: '20px', right: '20px', zIndex: 99999 }));
            }

            // Auto remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        MitziesJerk.init();
    });

})(jQuery);
