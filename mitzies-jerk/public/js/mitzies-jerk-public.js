/**
 * Mitzies Jerk - Public JavaScript
 *
 * Uber Eats-inspired food ordering experience
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

        // Local cart state for persistence
        cartState: {},

        /**
         * Initialize
         */
        init: function() {
            this.loadCartState();
            this.bindEvents();
            this.initMiniCart();
            this.initPreorderDateTime();
            this.initPaymentMethods();
            this.initQuantityControls();
            this.updateAllButtonStates();
            this.initCardAddonPriceUpdate();
            this.initSingleViewButtonState();
            this.initDeliveryMethods();
            this.initOrderTracking();
        },

        /**
         * Load cart state from localStorage as backup
         */
        loadCartState: function() {
            try {
                var stored = localStorage.getItem('mj_cart_state');
                if (stored) {
                    this.cartState = JSON.parse(stored);
                }
            } catch (e) {
                this.cartState = {};
            }
        },

        /**
         * Save cart state to localStorage
         */
        saveCartState: function(cart) {
            try {
                if (cart && cart.items) {
                    this.cartState = {};
                    cart.items.forEach(function(item) {
                        MitziesJerk.cartState[item.food_item_id] = {
                            key: item.key,
                            quantity: item.quantity,
                            name: item.name
                        };
                    });
                    localStorage.setItem('mj_cart_state', JSON.stringify(this.cartState));
                }
            } catch (e) {
                console.log('Could not save cart state');
            }
        },

        /**
         * Bind Events
         */
        bindEvents: function() {
            // Add to cart
            $(document).on('click', '.mj-add-to-cart-btn', this.addToCart.bind(this));

            // Uber Eats style quantity controls on grid items
            $(document).on('click', '.mj-cart-qty-btn', this.handleCartQtyButton.bind(this));

            // Update cart quantity (supports both single item and cart page controls)
            $(document).on('click', '.mj-quantity-btn, .mj-qty-minus, .mj-qty-plus', this.updateQuantity.bind(this));
            $(document).on('change', '.mj-quantity-input, .mj-qty-input', this.updateCartItem.bind(this));

            // Remove from cart
            $(document).on('click', '.mj-remove-item', this.removeFromCart.bind(this));

            // Apply coupon
            $(document).on('click', '.mj-coupon-btn', this.applyCoupon.bind(this));

            // Category filter (supports both .mj-filter-btn and .mj-category-filter)
            $(document).on('click', '.mj-filter-btn, .mj-category-filter', this.filterByCategory.bind(this));

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

            // View button with addon persistence
            $(document).on('click', '.mj-food-item .mj-view-btn, .mj-food-item .mj-view-details', this.handleViewWithAddons.bind(this));

            // Pre-select addons on single page from URL params
            this.preSelectAddonsFromUrl();
        },

        /**
         * Update all add to cart button states based on cart
         */
        updateAllButtonStates: function() {
            var self = this;

            // Update buttons based on local cart state
            Object.keys(this.cartState).forEach(function(itemId) {
                var itemData = self.cartState[itemId];
                if (itemData && itemData.quantity > 0) {
                    self.switchToQuantityControls(itemId, itemData.quantity, itemData.key);
                }
            });
        },

        /**
         * Switch Add to Cart button to quantity controls (Uber Eats style)
         * Note: Single item page buttons have dedicated quantity controls, so skip them.
         */
        switchToQuantityControls: function(itemId, quantity, cartKey) {
            // Exclude single item page buttons (they have dedicated quantity controls)
            var $btn = $('.mj-add-to-cart-btn[data-item-id="' + itemId + '"]').not('.mj-add-to-cart-single');

            if (!$btn.length) return;

            // Check if already converted
            if ($btn.hasClass('mj-has-qty-controls')) {
                // Just update the quantity
                $btn.find('.mj-cart-qty-value').text(quantity);
                return;
            }

            // Convert button to quantity control
            var qtyHtml = '<div class="mj-cart-qty-controls" data-item-id="' + itemId + '" data-cart-key="' + cartKey + '">' +
                '<button type="button" class="mj-cart-qty-btn mj-cart-qty-minus" data-action="minus">-</button>' +
                '<span class="mj-cart-qty-value">' + quantity + '</span>' +
                '<button type="button" class="mj-cart-qty-btn mj-cart-qty-plus" data-action="plus">+</button>' +
                '</div>';

            $btn.addClass('mj-has-qty-controls').html(qtyHtml);
        },

        /**
         * Switch back to Add to Cart button
         */
        switchToAddButton: function(itemId) {
            var $btn = $('.mj-add-to-cart-btn[data-item-id="' + itemId + '"]');

            if (!$btn.length) return;

            $btn.removeClass('mj-has-qty-controls loading added').prop('disabled', false);
            $btn.html('<span class="dashicons dashicons-cart"></span> ' + mitzies_jerk_params.i18n.add_to_cart);
        },

        /**
         * Handle Uber Eats style quantity buttons
         */
        handleCartQtyButton: function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(e.currentTarget);
            var $controls = $btn.closest('.mj-cart-qty-controls');
            var itemId = $controls.data('item-id');
            var cartKey = $controls.data('cart-key');
            var action = $btn.data('action');
            var $qtyDisplay = $controls.find('.mj-cart-qty-value');
            var currentQty = parseInt($qtyDisplay.text()) || 1;

            if (action === 'plus') {
                this.updateCartQuantity(cartKey, currentQty + 1, itemId, $qtyDisplay);
            } else if (action === 'minus') {
                if (currentQty <= 1) {
                    this.removeItemByKey(cartKey, itemId);
                } else {
                    this.updateCartQuantity(cartKey, currentQty - 1, itemId, $qtyDisplay);
                }
            }
        },

        /**
         * Update cart quantity via AJAX
         */
        updateCartQuantity: function(cartKey, quantity, itemId, $qtyDisplay) {
            var self = this;

            // Optimistic UI update
            $qtyDisplay.text(quantity);

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_update_cart',
                    nonce: mitzies_jerk_params.nonce,
                    item_key: cartKey,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.success) {
                        self.updateMiniCart(response.data.cart);
                        self.saveCartState(response.data.cart);

                        // Update cart page if present
                        if ($('.mj-cart').length) {
                            self.updateCartTable(response.data.cart);
                        }
                    } else {
                        // Revert on failure
                        self.initMiniCart();
                        MitziesJerk.showNotice('error', response.data.message || mitzies_jerk_params.i18n.error);
                    }
                },
                error: function() {
                    self.initMiniCart();
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Remove item by cart key
         */
        removeItemByKey: function(cartKey, itemId) {
            var self = this;

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_remove_from_cart',
                    nonce: mitzies_jerk_params.nonce,
                    item_key: cartKey
                },
                success: function(response) {
                    if (response.success) {
                        // Switch back to add button
                        self.switchToAddButton(itemId);

                        // Remove from local state
                        delete self.cartState[itemId];
                        localStorage.setItem('mj_cart_state', JSON.stringify(self.cartState));

                        self.updateMiniCart(response.data.cart);
                        self.saveCartState(response.data.cart);

                        // Update cart page if present
                        if ($('.mj-cart').length) {
                            $('tr[data-item-key="' + cartKey + '"]').fadeOut(300, function() {
                                $(this).remove();
                                if (response.data.cart.items.length === 0) {
                                    MitziesJerk.showEmptyCart();
                                }
                            });
                            self.updateCartTable(response.data.cart);
                        }

                        MitziesJerk.showNotice('success', response.data.message);
                    }
                },
                error: function() {
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                }
            });
        },

        /**
         * Add to Cart
         */
        addToCart: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);

            // If already has quantity controls, don't proceed
            if ($btn.hasClass('mj-has-qty-controls')) {
                return;
            }

            var itemId = $btn.data('item-id');
            var $container = $btn.closest('.mj-food-item, .mj-single-food-item, .mj-single-food-details');
            var quantity = parseInt($container.find('.mj-quantity-input').val()) || 1;

            // Collect addons
            var addons = {};
            $container.find('.mj-addon-input:checked').each(function() {
                var addonId = $(this).data('addon-id');
                if (addonId) {
                    addons[addonId] = 1;
                }
            });

            // Collect extras
            var extras = {};
            $container.find('.mj-extras-group').each(function() {
                var groupId = $(this).data('group');
                var $checked = $(this).find('.mj-extra-input:checked');
                if ($checked.length) {
                    var values = [];
                    $checked.each(function() {
                        values.push($(this).val());
                    });
                    // For single select (radio), just use the value
                    if ($checked.first().attr('type') === 'radio') {
                        extras[groupId] = values[0];
                    } else {
                        extras[groupId] = values;
                    }
                }
            });

            // Collect special instructions
            var specialInstructions = $container.find('.mj-special-instructions').val() || '';

            // Legacy options support
            var options = this.getItemOptions($container);

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);
            $btn.html('<span class="mj-spinner"></span>');

            var self = this;

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_add_to_cart',
                    nonce: mitzies_jerk_params.nonce,
                    item_id: itemId,
                    quantity: quantity,
                    addons: addons,
                    extras: extras,
                    special_instructions: specialInstructions,
                    options: options
                },
                success: function(response) {
                    if (response.success) {
                        // Find the cart item key from response
                        var cartKey = '';
                        if (response.data.cart && response.data.cart.items) {
                            response.data.cart.items.forEach(function(item) {
                                if (item.food_item_id == itemId) {
                                    cartKey = item.key;
                                    quantity = item.quantity;
                                }
                            });
                        }

                        $btn.removeClass('loading').prop('disabled', false);

                        // Check if on single view page - show View Cart button
                        var isSinglePage = $btn.closest('.mj-single-food-item, .mj-single-food-details').length > 0;
                        if (isSinglePage) {
                            // Switch to View Cart button on single page
                            self.switchToViewCartButton($btn);
                        } else {
                            // Switch to quantity controls on card/grid (Uber Eats style)
                            self.switchToQuantityControls(itemId, quantity, cartKey);
                        }

                        // Update cart state
                        self.updateMiniCart(response.data.cart);
                        self.saveCartState(response.data.cart);
                        MitziesJerk.showNotice('success', response.data.message);

                        // Open mini cart briefly to show item added
                        $('.mj-mini-cart-dropdown').addClass('active');
                        setTimeout(function() {
                            $('.mj-mini-cart-dropdown').removeClass('active');
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
         * Update Quantity (+ / - buttons) on single product page
         */
        updateQuantity: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            // Support both button groups for single item and cart page
            var $input = $btn.siblings('.mj-quantity-input, .mj-qty-input');
            if (!$input.length) {
                $input = $btn.parent().find('.mj-quantity-input, .mj-qty-input');
            }
            var currentVal = parseInt($input.val()) || 1;
            var min = parseInt($input.attr('min')) || 1;
            var max = parseInt($input.attr('max')) || 99;

            if ($btn.hasClass('mj-quantity-minus') || $btn.hasClass('mj-qty-minus')) {
                if (currentVal > min) {
                    $input.val(currentVal - 1).trigger('change');
                }
            } else if ($btn.hasClass('mj-quantity-plus') || $btn.hasClass('mj-qty-plus')) {
                if (currentVal < max) {
                    $input.val(currentVal + 1).trigger('change');
                }
            }
        },

        /**
         * Update Cart Item (for cart page)
         */
        updateCartItem: function(e) {
            var $input = $(e.currentTarget);
            var $row = $input.closest('tr.mj-cart-item');

            // Skip if not on cart page table
            if (!$row.length) {
                return;
            }

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
                        MitziesJerk.saveCartState(response.data.cart);
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
            var itemId = $row.data('item-id');

            if (!confirm(mitzies_jerk_params.i18n.confirm_remove)) {
                return;
            }

            $row.addClass('loading');
            var self = this;

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
                            MitziesJerk.saveCartState(response.data.cart);

                            if (response.data.cart.items.length === 0) {
                                MitziesJerk.showEmptyCart();
                            }
                        });

                        // Update button state if on menu
                        if (itemId) {
                            self.switchToAddButton(itemId);
                            delete self.cartState[itemId];
                            localStorage.setItem('mj_cart_state', JSON.stringify(self.cartState));
                        }

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

            var self = this;

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
                        // Update button states after loading new items
                        self.updateAllButtonStates();
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

            var self = this;

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

                        // Update button states after loading new items
                        self.updateAllButtonStates();
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

            var self = this;

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: formData,
                timeout: 60000,
                success: function(response) {
                    if (response.success && response.data) {
                        // Clear local cart state on successful checkout
                        self.cartState = {};
                        localStorage.removeItem('mj_cart_state');

                        // Show success message
                        MitziesJerk.showNotice('success', response.data.message || mitzies_jerk_params.i18n.order_success || 'Order placed successfully!');

                        // Redirect to appropriate page
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 500);
                        } else if (response.data.payment_url) {
                            setTimeout(function() {
                                window.location.href = response.data.payment_url;
                            }, 500);
                        } else {
                            // Fallback: redirect to home after delay
                            $btn.removeClass('loading').prop('disabled', false);
                            $btn.html(mitzies_jerk_params.i18n.order_success || 'Order Placed!');
                            setTimeout(function() {
                                window.location.href = window.location.origin;
                            }, 2000);
                        }
                    } else {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.html(mitzies_jerk_params.i18n.place_order);
                        // Handle both string and object error responses.
                        var errorMsg = mitzies_jerk_params.i18n.error;
                        if (response.data) {
                            if (typeof response.data === 'string') {
                                errorMsg = response.data;
                            } else if (response.data.message) {
                                errorMsg = response.data.message;
                            }
                        }
                        MitziesJerk.showNotice('error', errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.removeClass('loading').prop('disabled', false);
                    $btn.html(mitzies_jerk_params.i18n.place_order);
                    if (status === 'timeout') {
                        MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.timeout || 'Request timed out. Please check your order status before trying again.');
                    } else {
                        MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error);
                    }
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
            var orderNumber = $form.find('.mj-tracking-input, input[name="order_number"]').val().trim();
            var email = $form.find('.mj-tracking-email, input[name="email"]').val().trim();

            if (!orderNumber) {
                MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.enter_order_number || 'Please enter your order number');
                return;
            }

            $btn.addClass('loading').prop('disabled', true);
            $btn.html('<span class="mj-spinner"></span> ' + (mitzies_jerk_params.i18n.tracking || 'Tracking...'));

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_track_order',
                    nonce: mitzies_jerk_params.nonce,
                    order_number: orderNumber,
                    email: email
                },
                success: function(response) {
                    $btn.removeClass('loading').prop('disabled', false);
                    $btn.html(mitzies_jerk_params.i18n.track_order || 'Track Order');

                    if (response.success) {
                        var $result = $('#mj-tracking-result, .mj-tracking-result');
                        if (response.data.html) {
                            $result.html(response.data.html).slideDown(300);
                        } else {
                            // Fallback if html is not provided
                            var html = '<div class="mj-tracking-result-content">';
                            html += '<h3>Order #' + response.data.order_number + '</h3>';
                            html += '<p><strong>Status:</strong> ' + response.data.status_label + '</p>';
                            html += '<p><strong>Total:</strong> ' + response.data.total + '</p>';
                            html += '</div>';
                            $result.html(html).slideDown(300);
                        }
                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $result.offset().top - 50
                        }, 500);
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : (mitzies_jerk_params.i18n.error || 'An error occurred');
                        MitziesJerk.showNotice('error', errorMsg);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    $btn.html(mitzies_jerk_params.i18n.track_order || 'Track Order');
                    MitziesJerk.showNotice('error', mitzies_jerk_params.i18n.error || 'An error occurred');
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
            var self = this;

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
                        MitziesJerk.saveCartState(response.data.cart);
                        self.updateAllButtonStates();
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

            // Update count with animation
            var $count = $miniCart.find('.mj-mini-cart-count');
            var oldCount = parseInt($count.text()) || 0;
            var newCount = cart.item_count || 0;

            if (oldCount !== newCount) {
                $count.addClass('mj-count-updated');
                setTimeout(function() {
                    $count.removeClass('mj-count-updated');
                }, 300);
            }
            $count.text(newCount);

            // Update items
            var itemsHtml = '';
            if (cart.items && cart.items.length > 0) {
                cart.items.forEach(function(item) {
                    itemsHtml += '<div class="mj-mini-cart-item" data-key="' + item.key + '">';
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

            // Show/hide checkout button
            if (cart.items && cart.items.length > 0) {
                $miniCart.find('.mj-mini-cart-actions').show();
            } else {
                $miniCart.find('.mj-mini-cart-actions').hide();
            }
        },

        /**
         * Update Cart Table
         */
        updateCartTable: function(cart) {
            // Update subtotals
            cart.items.forEach(function(item) {
                var $row = $('tr[data-item-key="' + item.key + '"]');
                $row.find('.mj-cart-item-subtotal').text(item.subtotal_formatted);
                $row.find('.mj-qty-input').val(item.quantity);
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

            if (cart.delivery_fee > 0) {
                $('.mj-cart-delivery-row').show();
                $('.mj-cart-delivery-value').text(cart.delivery_fee_formatted);
            }

            if (cart.tax > 0) {
                $('.mj-cart-tax-row').show();
                $('.mj-cart-tax-value').text(cart.tax_formatted);
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
                var gatewayId = $method.data('gateway');

                $('.mj-payment-method').removeClass('selected');
                $method.addClass('selected');
                $radio.prop('checked', true);

                // Toggle extra info display (bank details, COD instructions)
                $('.mj-payment-extra-info').slideUp(200);
                if (gatewayId) {
                    $('.mj-payment-extra-info[data-gateway="' + gatewayId + '"]').slideDown(200);
                }
            });

            // Show initial extra info if first payment method has it
            var $firstSelected = $('.mj-payment-method.selected');
            if ($firstSelected.length) {
                var gatewayId = $firstSelected.data('gateway');
                if (gatewayId) {
                    $('.mj-payment-extra-info[data-gateway="' + gatewayId + '"]').show();
                }
            }
        },

        /**
         * Initialize Quantity Controls
         */
        initQuantityControls: function() {
            // Prevent non-numeric input
            $(document).on('input', '.mj-quantity-input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Initialize dynamic total calculation for single product page
            this.initDynamicTotal();
        },

        /**
         * Initialize Dynamic Total Calculation
         */
        initDynamicTotal: function() {
            var $container = $('.mj-single-food-item, .mj-single-food-details');
            if (!$container.length) {
                return;
            }

            var self = this;

            // Bind to addon/extra checkboxes
            $container.on('change', '.mj-addon-input, .mj-extra-input', function() {
                self.updateDynamicTotal($container);
            });

            // Bind to quantity changes on single product page
            $container.on('change', '.mj-quantity-input', function() {
                self.updateDynamicTotal($container);
            });

            // Handle extras group max selection
            $container.on('change', '.mj-extras-group input[type="checkbox"]', function() {
                var $group = $(this).closest('.mj-extras-group');
                var maxSelect = parseInt($group.data('max')) || 0;

                if (maxSelect > 0) {
                    var $checkboxes = $group.find('input[type="checkbox"]');
                    var checked = $checkboxes.filter(':checked').length;

                    if (checked >= maxSelect) {
                        $checkboxes.not(':checked').prop('disabled', true);
                    } else {
                        $checkboxes.prop('disabled', false);
                    }
                }
            });

            // Initial calculation
            this.updateDynamicTotal($container);
        },

        /**
         * Update Dynamic Total
         */
        updateDynamicTotal: function($container) {
            var $totalValue = $container.find('.mj-total-value');
            if (!$totalValue.length) {
                return;
            }

            var basePrice = parseFloat($totalValue.data('base-price')) || 0;
            var quantity = parseInt($container.find('.mj-quantity-input').val()) || 1;
            var addonsTotal = 0;

            // Calculate addons total
            $container.find('.mj-addon-input:checked, .mj-extra-input:checked').each(function() {
                var addonPrice = parseFloat($(this).data('price')) || 0;
                addonsTotal += addonPrice;
            });

            var total = (basePrice + addonsTotal) * quantity;

            // Format the price
            var formattedTotal = this.formatPrice(total);
            $totalValue.text(formattedTotal);
        },

        /**
         * Format Price
         */
        formatPrice: function(price) {
            var currencySymbol = mitzies_jerk_params.currency_symbol || '$';
            var currencyPosition = mitzies_jerk_params.currency_position || 'left';
            var decimals = mitzies_jerk_params.decimals || 2;
            var decimalSeparator = mitzies_jerk_params.decimal_separator || '.';
            var thousandSeparator = mitzies_jerk_params.thousand_separator || ',';

            // Format the number
            var formattedPrice = price.toFixed(decimals);
            var parts = formattedPrice.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
            formattedPrice = parts.join(decimalSeparator);

            if (currencyPosition === 'left') {
                return currencySymbol + formattedPrice;
            } else if (currencyPosition === 'left_space') {
                return currencySymbol + ' ' + formattedPrice;
            } else if (currencyPosition === 'right') {
                return formattedPrice + currencySymbol;
            } else {
                return formattedPrice + ' ' + currencySymbol;
            }
        },

        /**
         * Handle View button click with addon persistence
         * Appends selected addon IDs to the URL when navigating to single view
         */
        handleViewWithAddons: function(e) {
            var $btn = $(e.currentTarget);
            var $item = $btn.closest('.mj-food-item');
            var href = $btn.attr('href');

            // Collect selected addons
            var selectedAddons = [];
            $item.find('.mj-addon-input:checked').each(function() {
                var addonId = $(this).data('addon-id');
                if (addonId) {
                    selectedAddons.push(addonId);
                }
            });

            // If no addons selected, just navigate normally
            if (selectedAddons.length === 0) {
                return true; // Allow default navigation
            }

            // Append addons to URL
            e.preventDefault();
            var separator = href.indexOf('?') === -1 ? '?' : '&';
            var newUrl = href + separator + 'selected_addons=' + selectedAddons.join(',');
            window.location.href = newUrl;
        },

        /**
         * Pre-select addons from URL parameters on single view page
         */
        preSelectAddonsFromUrl: function() {
            // Only run on single food item pages
            if (!$('.mj-single-food-item, .mj-single-food-details').length) {
                return;
            }

            // Get URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            var selectedAddons = urlParams.get('selected_addons');

            if (!selectedAddons) {
                return;
            }

            // Split addon IDs
            var addonIds = selectedAddons.split(',');

            // Pre-check the corresponding addon checkboxes
            addonIds.forEach(function(addonId) {
                var $checkbox = $('.mj-addon-input[data-addon-id="' + addonId + '"]');
                if ($checkbox.length) {
                    $checkbox.prop('checked', true);
                }
            });

            // Trigger price update
            var $container = $('.mj-single-food-item, .mj-single-food-details');
            if ($container.length) {
                MitziesJerk.updateDynamicTotal($container);
            }
        },

        /**
         * Initialize Card Addon Price Update
         * Updates the displayed price on food cards when addons are selected
         */
        initCardAddonPriceUpdate: function() {
            var self = this;

            // Bind to addon checkbox changes on food item cards
            $(document).on('change', '.mj-food-item .mj-addon-input', function() {
                var $item = $(this).closest('.mj-food-item');
                self.updateCardPrice($item);
            });
        },

        /**
         * Update Card Price
         * Calculates and updates the displayed price on a food card based on selected addons
         */
        updateCardPrice: function($item) {
            var $priceEl = $item.find('.mj-food-price .mj-current-price, .mj-food-price');
            if (!$priceEl.length) return;

            // Get base price from data attribute or parse from displayed text
            var basePrice = parseFloat($item.data('base-price')) || 0;
            if (!basePrice) {
                // Try to get from the price element
                var priceText = $priceEl.first().text().replace(/[^0-9.]/g, '');
                basePrice = parseFloat(priceText) || 0;
                $item.data('base-price', basePrice);
            }

            // Calculate addon total
            var addonTotal = 0;
            $item.find('.mj-addon-input:checked').each(function() {
                var addonPrice = parseFloat($(this).data('price')) || 0;
                addonTotal += addonPrice;
            });

            var totalPrice = basePrice + addonTotal;
            var formattedPrice = this.formatPrice(totalPrice);

            // Update the displayed price
            $priceEl.first().text(formattedPrice);

            // Add visual feedback
            $priceEl.addClass('mj-price-updated');
            setTimeout(function() {
                $priceEl.removeClass('mj-price-updated');
            }, 300);
        },

        /**
         * Initialize Single View Button State
         * Shows "View Cart" button on single view page if item is already in cart
         */
        initSingleViewButtonState: function() {
            var self = this;
            var $singleBtn = $('.mj-single-food-item .mj-add-to-cart-btn, .mj-single-food-details .mj-add-to-cart-btn');

            if (!$singleBtn.length) return;

            var itemId = $singleBtn.first().data('item-id');
            if (!itemId) return;

            // Check if item is in cart
            var cartItem = this.cartState[itemId];
            if (cartItem && cartItem.quantity > 0) {
                // Change button to "View Cart" link
                this.switchToViewCartButton($singleBtn);
            }
        },

        /**
         * Switch Add to Cart button to View Cart button on single page
         */
        switchToViewCartButton: function($btn) {
            var cartUrl = mitzies_jerk_params.cart_url || '/cart/';
            var viewCartText = mitzies_jerk_params.i18n.view_cart || 'View Cart';

            $btn.each(function() {
                var $thisBtn = $(this);
                // Convert to link styled as button
                var $link = $('<a></a>')
                    .attr('href', cartUrl)
                    .addClass('mj-btn mj-btn-primary mj-view-cart-btn')
                    .html('<span class="dashicons dashicons-cart"></span> ' + viewCartText);

                $thisBtn.replaceWith($link);
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
        },

        /**
         * Initialize delivery methods on checkout page
         */
        initDeliveryMethods: function() {
            if (!$('.mj-checkout-form').length) return;

            var self = this;

            // Load delivery methods on page load.
            this.loadDeliveryMethods();

            // Recalculate when address changes.
            $(document).on('change blur', 'input[name="address_1"], input[name="city"], input[name="postcode"]', function() {
                clearTimeout(self._addressTimer);
                self._addressTimer = setTimeout(function() {
                    self.recalculateDeliveryFee();
                }, 500);
            });

            // Handle delivery method selection.
            $(document).on('change', 'input[name="delivery_method"]', function() {
                var $selected = $(this);
                var methodType = $selected.data('method-type');
                var fee = parseFloat($selected.data('fee')) || 0;

                // Show/hide pickup locations.
                if (methodType === 'pickup') {
                    $('.mj-pickup-locations-section').show();
                    $('.mj-delivery-address-section').hide();
                } else {
                    $('.mj-pickup-locations-section').hide();
                    $('.mj-delivery-address-section').show();
                }

                // Update totals.
                self.updateDeliveryFee(fee);
            });

            // Handle pickup location selection.
            $(document).on('change', 'select[name="pickup_location"]', function() {
                var locationName = $(this).find(':selected').text();
                if (locationName) {
                    $('.mj-selected-pickup-info').html('<strong>' + locationName + '</strong>').show();
                }
            });
        },

        /**
         * Load available delivery methods
         */
        loadDeliveryMethods: function() {
            var self = this;
            var $container = $('.mj-delivery-methods-container');

            if (!$container.length) {
                // Create container if not exists.
                var $deliverySection = $('.mj-delivery-section, .mj-checkout-delivery');
                if ($deliverySection.length) {
                    $deliverySection.prepend('<div class="mj-delivery-methods-container"><p class="mj-loading">' + (mitzies_jerk_params.i18n.loading || 'Loading...') + '</p></div>');
                    $container = $('.mj-delivery-methods-container');
                } else {
                    return;
                }
            }

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_get_delivery_methods',
                    nonce: mitzies_jerk_params.nonce
                },
                success: function(response) {
                    if (response.success && response.data.methods) {
                        self.renderDeliveryMethods(response.data.methods, response.data.locations);
                    }
                }
            });
        },

        /**
         * Render delivery method options
         */
        renderDeliveryMethods: function(methods, locations) {
            var $container = $('.mj-delivery-methods-container');
            if (!$container.length) return;

            var html = '<div class="mj-delivery-method-options">';
            html += '<h4>' + (mitzies_jerk_params.i18n.select_delivery || 'Select Delivery Method') + '</h4>';

            methods.forEach(function(method, index) {
                var checked = index === 0 ? 'checked' : '';
                var icon = method.type === 'pickup' ? '📦' : '🚚';
                var feeText = parseFloat(method.fee) === 0
                    ? (mitzies_jerk_params.i18n.free || 'Free')
                    : method.formatted_fee;

                html += '<label class="mj-delivery-method-option' + (checked ? ' selected' : '') + '">';
                html += '<input type="radio" name="delivery_method" value="' + method.id + '" ' + checked;
                html += ' data-fee="' + method.fee + '" data-method-type="' + method.type + '">';
                html += '<span class="mj-method-icon">' + icon + '</span>';
                html += '<span class="mj-method-details">';
                html += '<span class="mj-method-name">' + method.name + '</span>';
                if (method.estimated_time) {
                    html += ' <span class="mj-method-time">(' + method.estimated_time + ')</span>';
                }
                html += '</span>';
                html += '<span class="mj-method-price">' + feeText + '</span>';
                html += '</label>';
            });

            html += '</div>';

            // Add pickup locations section (hidden by default).
            if (locations && locations.length > 0) {
                html += '<div class="mj-pickup-locations-section" style="display:none;">';
                html += '<h4>' + (mitzies_jerk_params.i18n.select_pickup || 'Select Pickup Location') + '</h4>';
                html += '<select name="pickup_location" class="mj-pickup-location-select">';
                html += '<option value="">' + (mitzies_jerk_params.i18n.choose_location || 'Choose a location...') + '</option>';
                locations.forEach(function(loc) {
                    html += '<option value="' + loc.id + '">' + loc.location_name + ' - ' + loc.address;
                    if (loc.availability_hours) {
                        html += ' (' + loc.availability_hours + ')';
                    }
                    html += '</option>';
                });
                html += '</select>';
                html += '<div class="mj-selected-pickup-info" style="display:none;"></div>';
                html += '</div>';
            }

            $container.html(html);

            // Highlight selected method.
            $(document).on('change', 'input[name="delivery_method"]', function() {
                $('.mj-delivery-method-option').removeClass('selected');
                $(this).closest('.mj-delivery-method-option').addClass('selected');
            });

            // Set initial fee from first method.
            if (methods.length > 0) {
                this.updateDeliveryFee(methods[0].fee);
            }
        },

        /**
         * Recalculate delivery fee based on address
         */
        recalculateDeliveryFee: function() {
            var address = $('input[name="address_1"]').val();
            var city = $('input[name="city"]').val();
            var self = this;

            if (!address || !city) return;

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_calculate_delivery_fee',
                    nonce: mitzies_jerk_params.nonce,
                    address: address,
                    city: city,
                    delivery_method_id: $('input[name="delivery_method"]:checked').val() || 0
                },
                success: function(response) {
                    if (response.success && response.data.methods) {
                        // Update method prices.
                        response.data.methods.forEach(function(method) {
                            var $radio = $('input[name="delivery_method"][value="' + method.id + '"]');
                            if ($radio.length) {
                                $radio.data('fee', method.fee);
                                var $option = $radio.closest('.mj-delivery-method-option');
                                var feeText = parseFloat(method.fee) === 0
                                    ? (mitzies_jerk_params.i18n.free || 'Free')
                                    : method.formatted_fee;
                                $option.find('.mj-method-price').text(feeText);
                                if (method.estimated_time) {
                                    $option.find('.mj-method-time').text('(' + method.estimated_time + ')');
                                }
                            }
                        });

                        // Update selected method's fee.
                        var selectedFee = parseFloat($('input[name="delivery_method"]:checked').data('fee')) || 0;
                        self.updateDeliveryFee(selectedFee);

                        // Show distance info.
                        if (response.data.distance !== null) {
                            var distanceText = response.data.distance + ' ' + response.data.distance_unit;
                            $('.mj-distance-info').remove();
                            $('.mj-delivery-methods-container').append(
                                '<p class="mj-distance-info"><small>' +
                                (mitzies_jerk_params.i18n.distance || 'Distance') + ': ' + distanceText +
                                '</small></p>'
                            );
                        }
                    }
                }
            });
        },

        /**
         * Update delivery fee in checkout totals
         */
        updateDeliveryFee: function(fee) {
            fee = parseFloat(fee) || 0;
            var $feeDisplay = $('.mj-delivery-fee-amount, .mj-checkout-delivery-fee');
            if ($feeDisplay.length) {
                var feeText = fee === 0
                    ? (mitzies_jerk_params.i18n.free || 'Free')
                    : mitzies_jerk_params.currency_symbol + fee.toFixed(2);
                $feeDisplay.text(feeText);
            }

            // Recalculate total.
            var subtotal = parseFloat($('.mj-checkout-subtotal').data('amount') || $('.mj-checkout-subtotal').text().replace(/[^0-9.]/g, '')) || 0;
            var discount = parseFloat($('.mj-checkout-discount').data('amount') || 0) || 0;
            var tax = parseFloat($('.mj-checkout-tax').data('amount') || 0) || 0;
            var total = subtotal - discount + fee + tax;

            var $totalDisplay = $('.mj-checkout-total-amount, .mj-checkout-total');
            if ($totalDisplay.length) {
                $totalDisplay.text(mitzies_jerk_params.currency_symbol + total.toFixed(2));
            }
        },

        /**
         * Initialize real-time order tracking
         */
        initOrderTracking: function() {
            var $trackingResult = $('.mj-tracking-result');
            if (!$trackingResult.length) return;

            // Auto-refresh tracking every 30 seconds if on tracking page.
            var orderNumber = $trackingResult.data('order-number');
            if (orderNumber) {
                this._trackingInterval = setInterval(function() {
                    MitziesJerk.refreshTracking(orderNumber);
                }, 30000);
            }
        },

        /**
         * Refresh order tracking data
         */
        refreshTracking: function(orderNumber) {
            if (!orderNumber) return;

            $.ajax({
                url: mitzies_jerk_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mj_track_order',
                    order_number: orderNumber,
                    nonce: mitzies_jerk_params.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('.mj-tracking-result').html(response.data.html);
                    }
                }
            });
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        MitziesJerk.init();
    });

})(jQuery);
