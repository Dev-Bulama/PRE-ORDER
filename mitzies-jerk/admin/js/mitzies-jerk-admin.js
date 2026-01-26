/**
 * Mitzies Jerk - Admin JavaScript
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/js
 */

(function($) {
    'use strict';

    /**
     * Mitzies Jerk Admin Object
     */
    var MitziesJerkAdmin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initColorPickers();
            this.initMediaUploader();
            this.initSortable();
            this.initSettingsTabs();
            this.initDashboard();
        },

        /**
         * Bind Events
         */
        bindEvents: function() {
            // Order status change
            $(document).on('change', '.mj-order-status-select', this.updateOrderStatus.bind(this));

            // Send test email
            $(document).on('click', '.mj-send-test-email', this.sendTestEmail.bind(this));

            // Export orders
            $(document).on('click', '.mj-export-orders', this.exportOrders.bind(this));

            // Add gallery image
            $(document).on('click', '.mj-add-gallery-image', this.addGalleryImage.bind(this));

            // Remove gallery image
            $(document).on('click', '.mj-gallery-item .remove-image', this.removeGalleryImage.bind(this));

            // Remove featured image
            $(document).on('click', '.mj-remove-featured-image', this.removeFeaturedImage.bind(this));

            // Business hours toggle
            $(document).on('change', '.mj-day-closed', this.toggleDayHours.bind(this));

            // Order actions
            $(document).on('click', '.mj-resend-email', this.resendEmail.bind(this));
            $(document).on('click', '.mj-print-order', this.printOrder.bind(this));
        },

        /**
         * Initialize Color Pickers
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.mj-color-picker').wpColorPicker();
            }
        },

        /**
         * Initialize Media Uploader
         */
        initMediaUploader: function() {
            var self = this;

            // Featured image upload
            $(document).on('click', '.mj-upload-image-btn', function(e) {
                e.preventDefault();

                var $btn = $(this);
                var $container = $btn.closest('.mj-image-upload');
                var $input = $container.find('.mj-image-id');
                var $preview = $container.find('.mj-image-preview');

                var frame = wp.media({
                    title: mj_admin_params.i18n.select_image,
                    button: {
                        text: mj_admin_params.i18n.use_image
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    $preview.html('<img src="' + attachment.url + '" alt="" />');
                    $preview.append('<button type="button" class="mj-remove-featured-image button">&times;</button>');
                });

                frame.open();
            });
        },

        /**
         * Add Gallery Image
         */
        addGalleryImage: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $container = $btn.closest('.mj-gallery-meta-box');
            var $gallery = $container.find('.mj-gallery-container');
            var $input = $container.find('.mj-gallery-ids');

            var frame = wp.media({
                title: mj_admin_params.i18n.select_images,
                button: {
                    text: mj_admin_params.i18n.add_to_gallery
                },
                multiple: true
            });

            frame.on('select', function() {
                var attachments = frame.state().get('selection').toJSON();
                var currentIds = $input.val() ? $input.val().split(',') : [];

                attachments.forEach(function(attachment) {
                    if (currentIds.indexOf(attachment.id.toString()) === -1) {
                        currentIds.push(attachment.id);

                        var $item = $('<div class="mj-gallery-item" data-id="' + attachment.id + '">');
                        $item.append('<img src="' + attachment.url + '" alt="" />');
                        $item.append('<button type="button" class="remove-image">&times;</button>');
                        $gallery.append($item);
                    }
                });

                $input.val(currentIds.join(','));
            });

            frame.open();
        },

        /**
         * Remove Gallery Image
         */
        removeGalleryImage: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $item = $btn.closest('.mj-gallery-item');
            var $container = $btn.closest('.mj-gallery-meta-box');
            var $input = $container.find('.mj-gallery-ids');

            var imageId = $item.data('id').toString();
            var currentIds = $input.val().split(',');
            var index = currentIds.indexOf(imageId);

            if (index > -1) {
                currentIds.splice(index, 1);
            }

            $input.val(currentIds.join(','));
            $item.fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Remove Featured Image
         */
        removeFeaturedImage: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $container = $btn.closest('.mj-image-upload');
            var $input = $container.find('.mj-image-id');
            var $preview = $container.find('.mj-image-preview');

            $input.val('');
            $preview.html('');
        },

        /**
         * Initialize Sortable
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('.mj-gallery-container').sortable({
                    items: '.mj-gallery-item',
                    cursor: 'move',
                    update: function(event, ui) {
                        var $container = $(this).closest('.mj-gallery-meta-box');
                        var $input = $container.find('.mj-gallery-ids');
                        var ids = [];

                        $(this).find('.mj-gallery-item').each(function() {
                            ids.push($(this).data('id'));
                        });

                        $input.val(ids.join(','));
                    }
                });
            }
        },

        /**
         * Initialize Settings Tabs
         */
        initSettingsTabs: function() {
            var $tabs = $('.mj-settings-tabs');
            var $panels = $('.mj-settings-panel');

            if (!$tabs.length) {
                return;
            }

            // Get active tab from URL hash or first tab
            var activeTab = window.location.hash.substring(1) || $tabs.find('.mj-settings-tab').first().data('tab');

            // Set initial active state
            $tabs.find('.mj-settings-tab').removeClass('active');
            $tabs.find('[data-tab="' + activeTab + '"]').addClass('active');
            $panels.removeClass('active');
            $('#' + activeTab).addClass('active');

            // Tab click handler
            $tabs.on('click', '.mj-settings-tab', function(e) {
                e.preventDefault();

                var tab = $(this).data('tab');

                $tabs.find('.mj-settings-tab').removeClass('active');
                $(this).addClass('active');

                $panels.removeClass('active');
                $('#' + tab).addClass('active');

                window.location.hash = tab;
            });
        },

        /**
         * Toggle Day Hours
         */
        toggleDayHours: function(e) {
            var $checkbox = $(e.currentTarget);
            var $row = $checkbox.closest('.mj-business-hours-row');
            var $timeInputs = $row.find('input[type="time"]');

            if ($checkbox.is(':checked')) {
                $timeInputs.prop('disabled', true);
            } else {
                $timeInputs.prop('disabled', false);
            }
        },

        /**
         * Update Order Status
         */
        updateOrderStatus: function(e) {
            var $select = $(e.currentTarget);
            var orderId = $select.data('order-id');
            var newStatus = $select.val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mj_update_order_status',
                    nonce: mj_admin_params.nonce,
                    order_id: orderId,
                    status: newStatus
                },
                beforeSend: function() {
                    $select.prop('disabled', true);
                },
                success: function(response) {
                    $select.prop('disabled', false);

                    if (response.success) {
                        MitziesJerkAdmin.showNotice('success', response.data.message);
                    } else {
                        MitziesJerkAdmin.showNotice('error', response.data.message);
                        // Revert to previous value
                        $select.val($select.data('previous-status'));
                    }
                },
                error: function() {
                    $select.prop('disabled', false);
                    MitziesJerkAdmin.showNotice('error', mj_admin_params.i18n.error);
                }
            });
        },

        /**
         * Send Test Email
         */
        sendTestEmail: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var emailType = $btn.data('email-type');
            var testEmail = $btn.siblings('.mj-test-email-input').val();

            if (!testEmail) {
                MitziesJerkAdmin.showNotice('error', mj_admin_params.i18n.enter_email);
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mj_send_test_email',
                    nonce: mj_admin_params.nonce,
                    email_type: emailType,
                    test_email: testEmail
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text(mj_admin_params.i18n.sending);
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(mj_admin_params.i18n.send_test);

                    if (response.success) {
                        MitziesJerkAdmin.showNotice('success', response.data.message);
                    } else {
                        MitziesJerkAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(mj_admin_params.i18n.send_test);
                    MitziesJerkAdmin.showNotice('error', mj_admin_params.i18n.error);
                }
            });
        },

        /**
         * Export Orders
         */
        exportOrders: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $form = $btn.closest('form');
            var format = $form.find('[name="export_format"]').val();
            var dateFrom = $form.find('[name="date_from"]').val();
            var dateTo = $form.find('[name="date_to"]').val();
            var status = $form.find('[name="order_status"]').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mj_export_orders',
                    nonce: mj_admin_params.nonce,
                    format: format,
                    date_from: dateFrom,
                    date_to: dateTo,
                    status: status
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text(mj_admin_params.i18n.exporting);
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(mj_admin_params.i18n.export);

                    if (response.success) {
                        // Download file
                        window.location.href = response.data.download_url;
                        MitziesJerkAdmin.showNotice('success', response.data.message);
                    } else {
                        MitziesJerkAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(mj_admin_params.i18n.export);
                    MitziesJerkAdmin.showNotice('error', mj_admin_params.i18n.error);
                }
            });
        },

        /**
         * Resend Email
         */
        resendEmail: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var orderId = $btn.data('order-id');
            var emailType = $btn.data('email-type');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mj_resend_email',
                    nonce: mj_admin_params.nonce,
                    order_id: orderId,
                    email_type: emailType
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        MitziesJerkAdmin.showNotice('success', response.data.message);
                    } else {
                        MitziesJerkAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    MitziesJerkAdmin.showNotice('error', mj_admin_params.i18n.error);
                }
            });
        },

        /**
         * Print Order
         */
        printOrder: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var orderId = $btn.data('order-id');

            var printWindow = window.open('', '_blank');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mj_get_print_order',
                    nonce: mj_admin_params.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        printWindow.document.write(response.data.html);
                        printWindow.document.close();
                        printWindow.print();
                    } else {
                        printWindow.close();
                        MitziesJerkAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    printWindow.close();
                    MitziesJerkAdmin.showNotice('error', mj_admin_params.i18n.error);
                }
            });
        },

        /**
         * Initialize Dashboard
         */
        initDashboard: function() {
            var $dashboard = $('.mj-dashboard');

            if (!$dashboard.length) {
                return;
            }

            // Refresh stats
            this.refreshDashboardStats();

            // Set up auto-refresh every 5 minutes
            setInterval(function() {
                MitziesJerkAdmin.refreshDashboardStats();
            }, 300000);
        },

        /**
         * Refresh Dashboard Stats
         */
        refreshDashboardStats: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mj_get_dashboard_stats',
                    nonce: mj_admin_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var stats = response.data;

                        // Update stat cards
                        $('.mj-stat-card.orders .mj-stat-value').text(stats.total_orders);
                        $('.mj-stat-card.revenue .mj-stat-value').text(stats.total_revenue);
                        $('.mj-stat-card.customers .mj-stat-value').text(stats.total_customers);
                        $('.mj-stat-card.items .mj-stat-value').text(stats.total_items);

                        // Update recent orders table
                        if (stats.recent_orders) {
                            var $tbody = $('.mj-orders-table tbody');
                            $tbody.empty();

                            stats.recent_orders.forEach(function(order) {
                                var $row = $('<tr>');
                                $row.append('<td><a href="' + order.edit_url + '" class="mj-order-number">#' + order.order_number + '</a></td>');
                                $row.append('<td>' + order.customer_name + '</td>');
                                $row.append('<td>' + order.pickup_time + '</td>');
                                $row.append('<td><span class="mj-order-status mj-status-' + order.status + '">' + order.status_label + '</span></td>');
                                $row.append('<td>' + order.total + '</td>');
                                $tbody.append($row);
                            });
                        }
                    }
                }
            });
        },

        /**
         * Show Notice
         */
        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            // Remove existing notices
            $('.mj-admin-wrap > .notice').remove();

            // Add notice
            $('.mj-admin-wrap').prepend($notice);

            // Make it dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Scroll to top to see notice
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        MitziesJerkAdmin.init();
    });

})(jQuery);
