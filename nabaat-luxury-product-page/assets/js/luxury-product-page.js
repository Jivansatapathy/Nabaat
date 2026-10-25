/**
 * NABAAT Store - Luxury D2C Product Page UX
 * Interactive controller for thumbnail gallery, image zoom, colorways,
 * sizing cards, PIN estimator, AJAX Add to Cart, and 1-Click Buy Now.
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    var $wrapper = $('.nabaat-luxury-wrapper');
    if (!$wrapper.length) {
      return;
    }

    var basePrice = parseFloat($wrapper.data('base-price')) || 0;
    var activePrice = basePrice;
    var productId = parseInt($wrapper.data('product-id'), 10);
    var activeSizeName = '';

    // =========================================================================
    // 1. THUMBNAIL GALLERY SWITCHER
    // =========================================================================
    $('.nabaat-thumb-item').on('click', function () {
      var $this = $(this);
      if ($this.hasClass('is-active')) return;

      $('.nabaat-thumb-item').removeClass('is-active');
      $this.addClass('is-active');

      var fullUrl = $this.data('full');
      var altTxt = $this.data('alt');

      var $mainImg = $('#nabaat-active-main-image');
      $mainImg.css('opacity', 0.4);

      setTimeout(function () {
        $mainImg.attr('src', fullUrl).attr('alt', altTxt).css('opacity', 1);
      }, 150);
    });

    // =========================================================================
    // 2. INTERACTIVE IMAGE HOVER ZOOM & PAN
    // =========================================================================
    var $zoomContainer = $('#nabaat-zoom-container');
    var $mainImg = $('#nabaat-active-main-image');

    $zoomContainer.on('mousemove', function (e) {
      var rect = this.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;

      $mainImg.css({
        'transform-origin': x + '% ' + y + '%',
        'transform': 'scale(1.5)'
      });
    });

    $zoomContainer.on('mouseleave', function () {
      $mainImg.css({
        'transform-origin': 'center center',
        'transform': 'scale(1)'
      });
    });

    // 360° Studio View auto-rotation preview
    var is360Running = false;
    $('#nabaat-btn-360').on('click', function () {
      if (is360Running) return;
      is360Running = true;
      var $thumbs = $('.nabaat-thumb-item');
      if ($thumbs.length <= 1) return;

      var step = 0;
      var interval = setInterval(function () {
        $thumbs.eq(step % $thumbs.length).trigger('click');
        step++;
        if (step >= $thumbs.length * 2) {
          clearInterval(interval);
          is360Running = false;
        }
      }, 400);
    });

    // =========================================================================
    // 3. COLORWAY SELECTOR
    // =========================================================================
    $('.nabaat-color-swatch').on('click', function () {
      var $this = $(this);
      $('.nabaat-color-swatch').removeClass('is-selected');
      $this.addClass('is-selected');

      var colorName = $this.data('color-name');
      $('#nabaat-color-label').text(colorName);
    });

    // =========================================================================
    // 4. SIZING MODE & CARDS
    // =========================================================================
    $('.nabaat-mode-tab').on('click', function () {
      var mode = $(this).data('mode');
      $('.nabaat-mode-tab').removeClass('is-active');
      $(this).addClass('is-active');

      if (mode === 'custom') {
        // Trigger WhatsApp consultation for bespoke sizing
        var waUrl = $('.nabaat-whatsapp-bar').attr('href');
        if (waUrl) {
          window.open(waUrl, '_blank');
        }
      }
    });

    $('.nabaat-size-card').on('click', function () {
      var $this = $(this);
      $('.nabaat-size-card').removeClass('is-selected');
      $this.addClass('is-selected');

      var price = parseFloat($this.data('price')) || basePrice;
      var mrp = parseFloat($this.data('mrp'));
      var sku = $this.data('sku');
      var discount = $this.data('discount');
      var statement = $this.data('statement');
      activeSizeName = $this.find('strong').text();
      activePrice = price;

      // Update Active Master SKU
      if (sku) {
        $('#nabaat-active-sku').text(sku);
      }

      // Update MRP and Discount Tag if present
      if (mrp && mrp > price) {
        $('.nabaat-price-mrp').text('₹' + new Intl.NumberFormat('en-IN').format(mrp)).show();
        if (discount) {
          $('.nabaat-discount-tag').text(discount + '% OFF').show();
        }
      }

      updatePriceDisplays();

      if (statement) {
        $('#nabaat-active-size-statement').text(statement);
      }
    });

    // =========================================================================
    // 5. QUANTITY STEPPER & PRICE CALCULATOR
    // =========================================================================
    function getQty() {
      return parseInt($('#nabaat-qty-input').val(), 10) || 1;
    }

    function setQty(val) {
      val = Math.max(1, Math.min(99, val));
      $('#nabaat-qty-input').val(val);
      updatePriceDisplays();
    }

    function updatePriceDisplays() {
      var qty = getQty();
      var total = activePrice * qty;
      var formatted = new Intl.NumberFormat('en-IN').format(total);
      var singleFormatted = new Intl.NumberFormat('en-IN').format(activePrice);

      $('#nabaat-active-price').text(singleFormatted);
      $('.nabaat-btn-price-val').text(formatted);
      $('.nabaat-sticky-price-num').text(singleFormatted);
    }

    $('.nabaat-qty-minus').on('click', function () {
      setQty(getQty() - 1);
    });

    $('.nabaat-qty-plus').on('click', function () {
      setQty(getQty() + 1);
    });

    $('#nabaat-qty-input').on('change input', function () {
      updatePriceDisplays();
    });

    // =========================================================================
    // 6. PINCODE DELIVERY ESTIMATOR
    // =========================================================================
    var cityMap = {
      '560': 'Bengaluru',
      '110': 'New Delhi',
      '400': 'Mumbai',
      '700': 'Kolkata',
      '600': 'Chennai',
      '500': 'Hyderabad',
      '411': 'Pune',
      '302': 'Jaipur',
      '380': 'Ahmedabad',
      '122': 'Gurugram'
    };

    function verifyPincode() {
      var pin = $.trim($('#nabaat-pincode-input').val());
      var $result = $('#nabaat-pincode-result');
      var $msg = $('#nabaat-pin-msg');

      if (!/^\d{6}$/.test(pin)) {
        $result.css('color', '#b91c1c').fadeIn();
        $msg.text('Please enter a valid 6-digit Indian PIN code.');
        return;
      }

      var prefix = pin.substring(0, 3);
      var cityName = cityMap[prefix] || 'Your Area';

      // Calculate estimated delivery date (+3 business days)
      var d = new Date();
      d.setDate(d.getDate() + 3);
      var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      var estString = dayNames[d.getDay()] + ', ' + d.getDate() + ' ' + monthNames[d.getMonth()];

      $result.css('color', '#065f46').fadeIn();
      $msg.html('Express courier available: Delivery by <strong>' + estString + '</strong> to ' + cityName + ' (' + pin + '). <strong>COD Eligible</strong>.');
    }

    $('#nabaat-btn-verify-pin').on('click', verifyPincode);
    $('#nabaat-pincode-input').on('keypress', function (e) {
      if (e.which === 13) {
        verifyPincode();
      }
    });

    // =========================================================================
    // 7. AJAX ADD TO CART & BUY NOW
    // =========================================================================
    function executeAddToCart(isBuyNow) {
      var $btn = isBuyNow ? $('#nabaat-btn-buy-now') : $('#nabaat-btn-add-to-cart');
      var origHtml = $btn.html();

      $btn.prop('disabled', true).text(isBuyNow ? 'Proceeding to Checkout...' : 'Adding to Cart...');

      $.ajax({
        url: window.nabaat_luxury_vars.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'nabaat_luxury_ajax_add_to_cart',
          nonce: window.nabaat_luxury_vars.nonce,
          product_id: productId,
          quantity: getQty(),
          custom_size: activeSizeName
        },
        success: function (res) {
          if (res.success) {
            if (isBuyNow) {
              window.location.href = window.nabaat_luxury_vars.checkout_url;
            } else {
              $btn.html('✔ Added to Cart!');
              $(document.body).trigger('added_to_cart', [res.data.cart_count, res.data.cart_hash]);

              setTimeout(function () {
                $btn.prop('disabled', false).html(origHtml);
              }, 2000);
            }
          } else {
            alert(res.data && res.data.message ? res.data.message : 'Error adding to cart.');
            $btn.prop('disabled', false).html(origHtml);
          }
        },
        error: function () {
          // Fallback to standard WooCommerce form submit if AJAX is intercepted
          $btn.prop('disabled', false).html(origHtml);
          window.location.href = window.nabaat_luxury_vars.checkout_url;
        }
      });
    }

    $('#nabaat-btn-add-to-cart, #nabaat-sticky-add-btn').on('click', function () {
      executeAddToCart(false);
    });

    $('#nabaat-btn-buy-now').on('click', function () {
      executeAddToCart(true);
    });

    // =========================================================================
    // 8. LUXURY ACCORDION TOGGLE
    // =========================================================================
    $('.nabaat-acc-trigger').on('click', function () {
      var $item = $(this).closest('.nabaat-acc-item');
      $item.toggleClass('is-open');
    });

    // =========================================================================
    // 9. MOBILE STICKY PURCHASE BAR TRIGGER
    // =========================================================================
    var $mainCta = $('#nabaat-btn-add-to-cart');
    var $stickyBar = $('#nabaat-sticky-bar');

    if ($mainCta.length && $stickyBar.length) {
      $(window).on('scroll', function () {
        var ctaBottom = $mainCta.offset().top + $mainCta.outerHeight();
        var scrollPos = $(window).scrollTop();

        if (scrollPos > ctaBottom && $(window).width() <= 768) {
          $stickyBar.fadeIn(200).css('display', 'flex');
        } else {
          $stickyBar.fadeOut(150);
        }
      });
    }

  });

})(jQuery);
