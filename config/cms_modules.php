<?php

return [
    'modules' => [
        'blog' => [
            'classes' => [
                'Post' => [
                    'features' => [
                        'index', 'show', 'store', 'update', 'delete', 'publishSchedule',
                        'categorize', 'tag', 'draft', 'feature', 'archive', 'restore', 'notifySubscribers'
                    ],
                    'fields' => ['title', 'content', 'author_id', 'status', 'published_at', 'featured_image', 'views', 'likes', 'comments_count', 'tags'],
                    'permissions' => ['createPost', 'editPost', 'deletePost', 'publishPost', 'featurePost'],
                    'notifications' => ['newComment', 'postPublished', 'postUpdated'],
                    'actors' => ['author', 'editor', 'admin'],
                ],
                'Category' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'merge'],
                    'fields' => ['name', 'slug', 'description', 'parent_id'],
                    'permissions' => ['createCategory', 'editCategory', 'deleteCategory'],
                ],
                'Comment' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'approve', 'spam', 'reply', 'report'],
                    'fields' => ['post_id', 'user_id', 'content', 'status', 'likes', 'reports_count'],
                    'permissions' => ['moderateComments', 'deleteComment', 'replyToComment'],
                    'notifications' => ['commentApproved', 'commentReported', 'replyAdded'],
                ],
                'Tag' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete'],
                    'fields' => ['name', 'slug', 'post_count'],
                    'permissions' => ['createTag', 'editTag', 'deleteTag'],
                ],
            ],
        ],
        'newsletter' => [
            'classes' => [
                'Subscriber' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'subscribe', 'unsubscribe', 'resubscribe'],
                    'fields' => ['email', 'name', 'status', 'subscribed_at', 'unsubscribed_at'],
                    'notifications' => ['newSubscription', 'unsubscribed', 'weeklyNewsletter'],
                ],
                'Campaign' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'send', 'schedule', 'track', 'cancel', 'duplicate'],
                    'fields' => ['title', 'content', 'status', 'sent_at', 'scheduled_at', 'open_rate', 'click_rate'],
                    'permissions' => ['createCampaign', 'editCampaign', 'sendCampaign'],
                ],
            ],
        ],
        'property' => [
            'classes' => [
                'Property' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'publish', 'unpublish', 'feature', 'archive'],
                    'fields' => ['title', 'description', 'price', 'location', 'bedrooms', 'bathrooms', 'amenities', 'status', 'published_at', 'view_count'],
                    'permissions' => ['createProperty', 'editProperty', 'publishProperty', 'deleteProperty'],
                ],
                'Booking' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'confirm', 'cancel', 'reschedule', 'notifyOwner', 'review'],
                    'fields' => ['property_id', 'user_id', 'start_date', 'end_date', 'status', 'total_price', 'special_requests', 'review'],
                    'permissions' => ['createBooking', 'editBooking', 'cancelBooking'],
                ],
            ],
        ],
        'ecommerce' => [
            'classes' => [
                'Product' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'updateInventory', 'setDiscount', 'addToCategory', 'removeFromCategory', 'archive', 'restore'],
                    'fields' => ['name', 'description', 'price', 'sku', 'inventory', 'category_id', 'discount', 'status', 'featured', 'created_at'],
                    'permissions' => ['createProduct', 'editProduct', 'deleteProduct', 'featureProduct'],
                    'notifications' => ['productBackInStock', 'newProductAdded', 'productDiscounted'],
                ],
                'Order' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'process', 'ship', 'cancel', 'refund', 'notifyCustomer', 'track'],
                    'fields' => ['user_id', 'status', 'total', 'shipping_address', 'billing_address', 'tracking_number', 'payment_status'],
                    'permissions' => ['createOrder', 'processOrder', 'shipOrder', 'cancelOrder'],
                    'notifications' => ['orderShipped', 'orderCancelled', 'orderRefunded'],
                ],
                'Cart' => [
                    'features' => ['show', 'addItem', 'removeItem', 'updateQuantity', 'clear', 'applyDiscount', 'checkout'],
                    'fields' => ['user_id', 'items', 'total', 'discount_code'],
                ],
                'Coupon' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'apply', 'revoke', 'trackUsage'],
                    'fields' => ['code', 'type', 'value', 'starts_at', 'expires_at', 'usage_limit', 'remaining_uses'],
                    'permissions' => ['createCoupon', 'editCoupon', 'deleteCoupon'],
                ],
            ],
        ],
        'customer' => [
            'classes' => [
                'Address' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'setDefault', 'verify'],
                    'fields' => ['user_id', 'type', 'address_line1', 'address_line2', 'city', 'state', 'country', 'postal_code', 'verified_at'],
                    'permissions' => ['createAddress', 'editAddress', 'deleteAddress'],
                ],
                'Dashboard' => [
                    'features' => ['getOverview', 'getOrderHistory', 'getBookingHistory', 'getWishlist', 'updateProfile'],
                    'fields' => ['user_id', 'orders', 'bookings', 'wishlist', 'profile_completion'],
                    'permissions' => ['viewDashboard', 'updateProfile'],
                ],
            ],
        ],
        'media' => [
            'classes' => [
                'File' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'download', 'generateThumbnail', 'optimizeImage', 'share'],
                    'fields' => ['name', 'path', 'size', 'mime_type', 'user_id', 'shared_with', 'downloads_count'],
                    'permissions' => ['uploadFile', 'deleteFile', 'shareFile'],
                    'notifications' => ['fileShared', 'fileDownloaded'],
                ],
                'Gallery' => [
                    'features' => ['index', 'show', 'store', 'update', 'delete', 'addFile', 'removeFile', 'share'],
                    'fields' => ['name', 'description', 'user_id', 'files_count'],
                ],
            ],
        ],
        'reporting' => [
            'classes' => [
                'SalesReport' => [
                    'features' => ['generate', 'export', 'scheduleDaily', 'scheduleWeekly', 'scheduleMonthly', 'filterByDateRange', 'filterByProduct'],
                ],
                'BookingReport' => [
                    'features' => ['generate', 'export', 'scheduleDaily', 'scheduleWeekly', 'scheduleMonthly', 'filterByProperty', 'filterByDateRange'],
                ],
                'UserReport' => [
                    'features' => ['generate', 'export', 'scheduleDaily', 'scheduleWeekly', 'scheduleMonthly', 'filterByActivityLevel'],
                ],
                'InventoryReport' => [
                    'features' => ['generate', 'export', 'scheduleDaily', 'scheduleWeekly', 'scheduleMonthly', 'filterByLowStock', 'filterByCategory'],
                ],
            ],
        ],
    ],
];
