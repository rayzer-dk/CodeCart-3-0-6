(function ($) {
    'use strict';

    var gallery = document.querySelector('#product-product [data-gallery-engine]');
    if (!gallery) {
        return;
    }

    var photoSelector = 'a.ccp-main-product-image, a.ccp-product-gallery-thumb';
    var photoLabel = gallery.dataset.photoLabel;

    function readPhoto(anchor, original) {
        var image = anchor.querySelector('img');
        var data = anchor.dataset;
        return {
            src: (original && data.defaultHref) || anchor.getAttribute('href'),
            width: Number(original ? data.defaultWidth : data.pswpWidth) || 1,
            height: Number(original ? data.defaultHeight : data.pswpHeight) || 1,
            preview: (original && image.dataset.defaultSrc) || image.getAttribute('src'),
            alt: image.alt
        };
    }

    function buildPreviews(strip, photos, select) {
        var buttons = photos.map(function (photo, index) {
            var button = document.createElement('button');
            var image = document.createElement('img');
            button.type = 'button';
            button.setAttribute('aria-label', photoLabel.replace('%s', index + 1));
            image.src = photo.preview;
            image.alt = '';
            image.loading = 'lazy';
            image.decoding = 'async';
            button.appendChild(image);
            button.addEventListener('click', function () {
                select(index);
            });
            strip.appendChild(button);
            return button;
        });
        strip.addEventListener('click', function (event) {
            event.stopPropagation();
        });
        strip.addEventListener('wheel', function (event) {
            event.stopPropagation();
        }, {passive: true});

        return function (index) {
            buttons.forEach(function (button, position) {
                if (position === index) {
                    button.setAttribute('aria-current', 'true');
                    button.scrollIntoView({block: 'nearest', inline: 'nearest'});
                } else {
                    button.removeAttribute('aria-current');
                }
            });
        };
    }

    function openPhotoSwipe(photos, index) {
        var viewer = new window.PhotoSwipe({
            dataSource: photos,
            index: index,
            bgOpacity: 0.92,
            wheelToZoom: true,
            preload: [1, 2],
            showHideAnimationType: 'none',
            showAnimationDuration: 0,
            hideAnimationDuration: 0,
            imageClickAction: 'zoom-or-close',
            tapAction: 'toggle-controls',
            doubleTapAction: 'zoom',
            padding: {top: 0, right: 0, bottom: photos.length > 1 ? 80 : 0, left: 0}
        });
        if (photos.length > 1) {
            viewer.on('uiRegister', function () {
                viewer.ui.registerElement({
                    name: 'product-previews',
                    className: 'ccp-product-photo-previews',
                    appendTo: 'root',
                    onInit: function (strip) {
                        var update = buildPreviews(strip, photos, function (position) {
                            viewer.goTo(position);
                        });
                        viewer.on('change', function () {
                            update(viewer.currIndex);
                        });
                    }
                });
            });
        }
        viewer.init();
    }

    function openMagnific(photos, index) {
        var strip;
        var update;
        $.magnificPopup.open({
            type: 'image',
            items: photos,
            mainClass: photos.length > 1 ? 'ccp-product-magnific' : '',
            gallery: {enabled: true, navigateByImgClick: true, preload: [0, 1]},
            image: {verticalFit: true},
            callbacks: {
                buildControls: function () {
                    if (photos.length > 1) {
                        var viewer = this;
                        strip = document.createElement('div');
                        strip.className = 'ccp-product-photo-previews';
                        this.wrap.append(strip);
                        update = buildPreviews(strip, photos, function (position) {
                            viewer.goTo(position);
                        });
                    }
                },
                open: function () {
                    if (update) {
                        update(this.index);
                    }
                },
                change: function () {
                    if (update) {
                        update(this.index);
                    }
                },
                close: function () {
                    if (strip) {
                        strip.remove();
                    }
                }
            }
        }, index);
    }

    gallery.addEventListener('click', function (event) {
        var anchor = event.target.closest(photoSelector);
        if (!anchor || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        var usePhotoSwipe = gallery.dataset.galleryEngine === 'photoswipe';
        if (usePhotoSwipe ? typeof window.PhotoSwipe !== 'function' : !$.magnificPopup) {
            return;
        }

        var main = gallery.querySelector('#ccp-main-product-image');
        var anchors = Array.from(gallery.querySelectorAll(photoSelector)).filter(function (item) {
            return item.hasAttribute('href') && item.querySelector('img');
        });
        var index = anchors.indexOf(anchor);
        if (index === -1) {
            return;
        }
        var photos = anchors.map(function (item) {
            return readPhoto(item, item === main);
        });
        if (anchor === main) {
            var selected = readPhoto(main, false);
            index = photos.findIndex(function (photo) {
                return photo.src === selected.src;
            });
            if (index === -1) {
                // An option can supply its own photo outside the product gallery.
                photos.unshift(selected);
                index = 0;
            }
        }

        event.preventDefault();
        if (usePhotoSwipe) {
            openPhotoSwipe(photos, index);
        } else {
            openMagnific(photos, index);
        }
    });
})(jQuery);
