<script>
  (function () {
    var loader = document.getElementById('sf-page-loader');

    function hideLoader() {
      if (!loader) return;
      loader.classList.add('sf-hidden');
      setTimeout(function () {
        if (loader.parentNode) loader.parentNode.removeChild(loader);
      }, 400);
    }

    if (document.readyState === 'complete') {
      hideLoader();
    } else {
      window.addEventListener('load', hideLoader);
      setTimeout(hideLoader, 8000);
    }

    document.addEventListener('DOMContentLoaded', function () {
      var lazyImages = [].slice.call(document.querySelectorAll('.lazy'));

      if ('IntersectionObserver' in window && lazyImages.length) {
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var img = entry.target;
            if (img.dataset.src) img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
          });
        }, { rootMargin: '120px' });

        lazyImages.forEach(function (img) { observer.observe(img); });
      }

      var navToggle = document.getElementById('sf-account-nav-toggle');
      var nav = document.getElementById('sf-account-nav');

      if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
          nav.classList.toggle('is-open');
        });
      }
    });
  })();
</script>
