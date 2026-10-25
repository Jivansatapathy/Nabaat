/**
 * NABAAT Store - Luxury Home Page Interactions
 * Dynamic interactive features: Match finder, colorway switches, and micro-interactions
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Fit Finder: pick what you protect, then its type; the match card updates (data from [nabaat_fit_finder])
  var fit = document.querySelector('[data-nb-fit]');
  if (fit) {
    var fitData = JSON.parse(fit.getAttribute('data-nb-fit'));
    var thingBtns = fit.querySelectorAll('.nb-fit-thing');
    var typesWrap = fit.querySelector('.nb-fit-types-wrap');
    var typesBox = fit.querySelector('.nb-fit-types');
    var q = function (sel) { return fit.querySelector(sel); };
    var current = 0;

    var showMatch = function (t) {
      q('.nb-fit-img').src = t.image;
      q('.nb-fit-name').textContent = t.name;
      q('.nb-fit-variant').textContent = t.variant;
      q('.nb-fit-variant').hidden = !t.variant;
      q('.nb-fit-price strong').textContent = t.price;
      q('.nb-fit-mrp').hidden = !t.off;
      q('.nb-fit-mrp s').textContent = t.mrp;
      q('.nb-fit-off').textContent = t.off + '% off';
      q('.nb-fit-buy').href = t.link;
      q('.nb-fit-all').href = t.all;
      q('.nb-fit-all span').textContent = 'See all ' + t.count + (t.count === 1 ? ' option' : ' options');
      var card = q('.nb-fit-result');
      card.classList.remove('is-new');
      void card.offsetWidth; // restart the arrival animation
      card.classList.add('is-new');
    };

    var pickType = function (j) {
      typesBox.querySelectorAll('.nb-fit-type').forEach(function (b, n) { b.setAttribute('aria-checked', n === j ? 'true' : 'false'); });
      showMatch(fitData[current].types[j]);
    };

    var pickThing = function (i) {
      current = i;
      thingBtns.forEach(function (b, n) { b.setAttribute('aria-checked', n === i ? 'true' : 'false'); });
      var types = fitData[i].types;
      typesWrap.hidden = types.length < 2;
      typesBox.innerHTML = '';
      types.forEach(function (t, j) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'nb-fit-type';
        b.setAttribute('role', 'radio');
        b.textContent = t.label;
        b.addEventListener('click', function () { pickType(j); });
        typesBox.appendChild(b);
      });
      pickType(0);
    };

    thingBtns.forEach(function (b, n) { b.addEventListener('click', function () { pickThing(n); }); });
    typesBox.querySelectorAll('.nb-fit-type').forEach(function (b, j) { b.addEventListener('click', function () { pickType(j); }); });
  }

  // 2. Product Card Color Swatches Hover / Selection
  var swatches = document.querySelectorAll('.nb-swatch-dot');
  swatches.forEach(function (swatch) {
    swatch.addEventListener('click', function () {
      var parent = this.closest('.nb-swatch-row');
      if (parent) {
        parent.querySelectorAll('.nb-swatch-dot').forEach(function (s) {
          s.classList.remove('is-active');
        });
      }
      this.classList.add('is-active');
    });
  });

  // 3. Quick Toast on Add to Cart (for demo or live)
  var addBtns = document.querySelectorAll('.nb-btn-card-cart, .nb-btn-quick-cart');
  addBtns.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var self = this;
      var productId = self.getAttribute('data-product-id');
      var originalHtml = self.innerHTML;
      self.innerHTML = '<span>✓ Added to Cart</span>';
      self.style.background = '#10B981';

      // If in WordPress environment with AJAX
      if (window.ajaxurl && productId) {
        var formData = new FormData();
        formData.append('action', 'nabaat_home_quick_add');
        formData.append('product_id', productId);
        fetch(window.ajaxurl, {
          method: 'POST',
          body: formData
        }).catch(function(err){ console.log(err); });
      }

      setTimeout(function () {
        self.innerHTML = originalHtml;
        self.style.background = '';
      }, 1600);
    });
  });

  // 4. Everyday Edit Navigation (Prev / Next Buttons)
  var sliderTrack = document.getElementById('nb-bestsellers-track');
  var prevBtn = document.getElementById('nb-bestsellers-prev');
  var nextBtn = document.getElementById('nb-bestsellers-next');

  if (sliderTrack && prevBtn && nextBtn) {
    // One card (plus the track's gap) per click
    var cardStep = function () {
      var card = sliderTrack.querySelector('.nb-edit-card');
      return card ? card.offsetWidth + (parseFloat(getComputedStyle(sliderTrack).columnGap) || 20) : 300;
    };
    prevBtn.addEventListener('click', function () { sliderTrack.scrollBy({ left: -cardStep(), behavior: 'smooth' }); });
    nextBtn.addEventListener('click', function () { sliderTrack.scrollBy({ left: cardStep(), behavior: 'smooth' }); });
  }

  // 5. Hero Background Slider (auto-advance, dots, pause on hover / hidden tab)
  var heroSlider = document.querySelector('[data-nb-hero-slider]');
  if (heroSlider) {
    var heroSlides = heroSlider.querySelectorAll('.nb-hero-slide');
    var heroDots = heroSlider.querySelectorAll('.nb-hero-dot');
    var heroIndex = 0;
    var heroTimer = null;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function showHeroSlide(i) {
      heroIndex = (i + heroSlides.length) % heroSlides.length;
      heroSlides.forEach(function (s, n) { s.classList.toggle('is-active', n === heroIndex); });
      heroDots.forEach(function (d, n) {
        d.classList.toggle('is-active', n === heroIndex);
        if (n === heroIndex) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
      });
    }

    function startHero() {
      if (reduceMotion || heroSlides.length < 2) return;
      clearInterval(heroTimer);
      heroTimer = setInterval(function () { showHeroSlide(heroIndex + 1); }, 4500); // brief: every 4 to 5 seconds
    }

    function stopHero() { clearInterval(heroTimer); }

    heroDots.forEach(function (dot, n) {
      dot.addEventListener('click', function () { showHeroSlide(n); startHero(); });
    });
    // Desktop arrows
    var heroPrev = heroSlider.querySelector('.nb-hero-arrow--prev');
    var heroNext = heroSlider.querySelector('.nb-hero-arrow--next');
    if (heroPrev) heroPrev.addEventListener('click', function () { showHeroSlide(heroIndex - 1); startHero(); });
    if (heroNext) heroNext.addEventListener('click', function () { showHeroSlide(heroIndex + 1); startHero(); });

    // Swipe on phones and tablets (a horizontal drag of 40px or more changes the banner)
    var touchX = null;
    var heroSwiped = false;
    heroSlider.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    heroSlider.addEventListener('touchend', function (e) {
      if (touchX === null) return;
      var dx = e.changedTouches[0].clientX - touchX;
      touchX = null;
      if (Math.abs(dx) < 40) return;
      heroSwiped = true;
      showHeroSlide(heroIndex + (dx < 0 ? 1 : -1));
      startHero();
    });
    // A swipe is not a tap: don't open the banner's link when the finger slid across it
    heroSlider.addEventListener('click', function (e) {
      if (heroSwiped) { e.preventDefault(); heroSwiped = false; }
    }, true);

    heroSlider.addEventListener('mouseenter', stopHero);
    heroSlider.addEventListener('mouseleave', startHero);
    document.addEventListener('visibilitychange', function () { if (document.hidden) stopHero(); else startHero(); });
    startHero();
  }

  // 5b. Range Index: hover / focus / tap a range to swap its shop window
  var utility = document.querySelector('[data-nb-utility]');
  if (utility) {
    var utilityItems = utility.querySelectorAll('.nb-utility-item');
    var utilityImgs = utility.querySelectorAll('.nb-utility-panel');

    function showUtility(i) {
      utilityItems.forEach(function (el, n) { el.classList.toggle('is-active', n === i); });
      utilityImgs.forEach(function (el, n) { el.classList.toggle('is-active', n === i); });
    }

    utilityItems.forEach(function (item, n) {
      item.addEventListener('mouseenter', function () { showUtility(n); });
      item.addEventListener('focusin', function () { showUtility(n); });
      item.addEventListener('click', function () { showUtility(n); });
    });
  }

  // 5c. Shop by category: pointing at or focusing a category shows its product collage
  var hoverGrid = document.querySelector('[data-nb-hover]');
  if (hoverGrid) {
    var hoverRows = hoverGrid.querySelectorAll('.nb-hover-row');
    var hoverPrevs = hoverGrid.querySelectorAll('.nb-hover-prev');
    hoverRows.forEach(function (row) {
      var show = function () {
        hoverRows.forEach(function (r) { r.classList.toggle('is-on', r === row); });
        hoverPrevs.forEach(function (p) { p.classList.toggle('is-on', p.getAttribute('data-i') === row.getAttribute('data-i')); });
      };
      row.addEventListener('mouseenter', show);
      row.addEventListener('focus', show);
    });
  }

  // 6. Wishlist Heart Button Interactive Toggle
  var wishlistBtns = document.querySelectorAll('.nb-card-wishlist-btn');
  wishlistBtns.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      this.classList.toggle('is-active');
      var heart = this.querySelector('svg');
      if (this.classList.contains('is-active')) {
        heart.setAttribute('fill', '#E11D48');
        heart.setAttribute('stroke', '#E11D48');
      } else {
        heart.setAttribute('fill', 'none');
        heart.setAttribute('stroke', '#334155');
      }
    });
  });
});
