(function ($, Drupal) {
  'use strict';

  var pswpElement;

  /**
   * Behavior
   */
  Drupal.behaviors.photoswipe = {
    attach: function(context, settings) {
      if(!$('#pswp-element').length) {
        $('body').append('<div id="pswp-element"></div>');
        $("#pswp-element").load( "/pswp-element", function() {
          pswpElement = document.querySelectorAll('.pswp')[0];
          $('.photoswipe-gallery').each(function() {
            initGallery($(this));
            if($(this).hasClass('photoswipe-gallery-isotope')) {
              initIsotope($(this));
            }
          });
        });
      }
    }
  };

  var initGallery = function($ps_gallery) {
    var items = [];
    var options = {
      index: 0
    };
    if(!$ps_gallery.hasClass('photoswipe-initialized')) {
      $ps_gallery.addClass('photoswipe-initialized');
      // Get items
      items = parseGalleryItems($ps_gallery);
      if(!items.length) {
        console.log('No images in gallery');
      } else {
        // Parse URL and open gallery if it contains #&pid=3&gid=1
        var hashData = photoswipeParseHash();
        if(hashData.pid && hashData.gid) {
          // openGallery( hashData.pid ,  galleryElements[ hashData.gid - 1 ], true, true );
          openGallery($ps_gallery, hashData.pid, pswpElement, items, options);
        }
        // Click on thumbnail
        $('.photoswipe-item a', $ps_gallery).click(function(e) {
          e.preventDefault();
          var $ps_gallery = $(this).closest('.photoswipe-gallery');
          var index = $(this).closest('figure').index();
          openGallery($ps_gallery, index, pswpElement, items, options);
        });
      }
    }
  };

  var parseGalleryItems = function($ps_gallery) {
    var ps_items = [];
    $('figure', $ps_gallery).each(function() {
      var item = [];
      // src
      item.src = $('a', $(this)).attr('href');
      // size if available
      if($('a', $(this)).attr('data-size')) {
        var size = $('a', $(this)).attr('data-size').split('x');
        if(size.length) {
          item.w = parseInt(size[0], 10);
          item.h = parseInt(size[1], 10);
        } else {
          item.w = 0;
          item.h = 0;
        }
      } else {
        item.w = 0;
        item.h = 0;
      }
      // caption
      if($('figcaption', $(this)).length && $('figcaption', $(this)).innerHTML) {
        item.title = $('figcaption', $(this)).innerHTML;
      }
      // add item
      ps_items.push(item);
    });
    return ps_items;
  };

  var getImageWidth = function(index, item) {
    var gallery = $(this)[0];
    if (item.w < 1 || item.h < 1) { // unknown size
      var img = new Image();
      img.onload = function() {
        item.w = this.width;
        item.h = this.height;
        gallery.invalidateCurrItems();
        gallery.updateSize(true);
      };
      img.src = item.src;
    }
  };

  var openGallery = function($ps_gallery, index, pswpElement, items, options) {
    options.index = index;
    var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
    gallery.listen('gettingData', getImageWidth);
    gallery.init();
  };

  var photoswipeParseHash = function() {
    var hash = window.location.hash.substring(1),
      params = {};
    if(hash.length < 5) {
      return params;
    }
    var vars = hash.split('&');
    for (var i = 0; i < vars.length; i++) {
      if(!vars[i]) {
        continue;
      }
      var pair = vars[i].split('=');
      if(pair.length < 2) {
        continue;
      }
      params[pair[0]] = pair[1];
    }
    if(params.gid) {
      params.gid = parseInt(params.gid, 10);
    }
    return params;
  };

  var initIsotope = function($ps_gallery) {
    console.log('init');
    $ps_gallery.isotope({
      // options
      itemSelector: '.photoswipe-item',
      layoutMode: 'masonry',
      transitionDuration: 0
    });
  }

})(jQuery, Drupal);