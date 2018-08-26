/**
 * Renders boxes for defined gallery
 *
 * @param gallery
 */
var galleryRender = function (gallery) {
        var containerEl = gallery.galleryEl,
            layoutGeometry = require('justified-layout')(
                gallery.images,
                {
                    containerPadding: {
                        top: 0,
                        right: 0,
                        bottom: 20,
                        left: 0
                    }, targetRowHeight: 240,
                    containerWidth: containerEl.width()
                }
            );
        containerEl.height(layoutGeometry.containerHeight);
        containerEl.data('width', containerEl.width());

        jQuery.each(gallery.images, function (index) {
            var image = gallery.images[index],
                wrapEl = image.wrapEl,
                imageDivEl = wrapEl.find('.gallery-image'),
                imageEl = wrapEl.find('img'),
                box = layoutGeometry.boxes[index];

            wrapEl.height(box.height);
            wrapEl.width(box.width);

            imageDivEl.height(box.height);
            imageDivEl.width(box.width);

            if (imageEl) {
                imageEl.height(box.height);
                imageEl.width(box.width);
            }

            var transformDefinition = 'translate(' + box.left + 'px, ' + box.top + 'px)';
            wrapEl.css({
                '-webkit-transform': transformDefinition,
                '-moz-transform': transformDefinition,
                '-ms-transform': transformDefinition,
                '-o-transform': transformDefinition,
                transform: transformDefinition
            });
        });
    },
    galleryRenderAll = function () {
        jQuery.each(galleryLoadGalleries(), function () {
            if (this.galleryEl.data('width') !== this.galleryEl.width()) {
                galleryRender(this);
            }
        });
    },
    /**
     * Loads galleries
     *
     * @returns {Array}
     */
    galleryLoadGalleries = function () {
        var galleries = [];

        jQuery('.gallery').each(function () {
            var galleryEl = jQuery(this),
                image,
                images = [];
            galleryEl.find('.gallery-image-wrap').each(function () {
                var el = jQuery(this);
                if (el.data('image-id')) {
                    image = {
                        srcThumbnail: el.data('src-thumbnail'),
                        srcPreview: el.data('src-preview'),
                        srcOriginal: el.data('src-original'),
                        height: el.data('height'),
                        width: el.data('width'),
                        wrapEl: el,
                        thumbnailRequested: false,
                        thumbnailLoad: false,
                        previewRequested: false,
                        previewLoad: false
                    };
                    images.push(image);
                }
            });

            galleries.push({
                galleryEl: galleryEl,
                images: images
            });
        });

        return galleries;
    }
;

jQuery(document).ready(function () {
    /**
     * Renders one single image
     * @param wrapEl
     * @param galleryImagesrc
     * @param blur
     */
    var renderImage = function (wrapEl, galleryImagesrc, blur) {
            var aEl = wrapEl.find('a');
            aEl.empty();

            aEl.prepend('<img' +
                ' data-wrap-el-id="' + wrapEl.attr('id') + '"' +
                ' width="' + wrapEl.width() + '"' +
                ' height="' + wrapEl.height() + '"' +
                ' src="' + galleryImagesrc + '"' +
                ' style="' + (blur ? 'filter: blur(3px);' : '') + '" />');
        },
        removeFaultyImageAndReRender = function (imageEl) {
            var wrapEl = imageEl.parents('.gallery-image-wrap'),
                gallery = getGallery(imageEl),
                galleryImagesUpdated = [];

            jQuery.each(gallery.images, function () {
                if (this.wrapEl === wrapEl) {
                    wrapEl.remove();
                } else {
                    galleryImagesUpdated.push(this);
                }
            });
        },
        /**
         * Improve transmitted images quality, update to preview if allowed to
         *
         * @param image Image to update
         * @param allowPreview {boolean} If set to false and image has already thumbnail, nothing is done
         * @returns {boolean} If image is updated or not
         */
        improveImage = function (image, allowPreview) {
            if (image.thumbnailRequested && !image.previewRequested && allowPreview) {
                image.previewRequested = new Date();
                image.wrapEl.find('.gallery-image').css('background-image', 'url(\'' + image.srcThumbnail + '\')');
                renderImage(image.wrapEl, image.srcPreview, false);
                return true;
            } else if (!image.thumbnailRequested) {
                image.thumbnailRequested = new Date();
                renderImage(image.wrapEl, image.srcThumbnail, true);
                return true;
            }
            return false;
        },
        getGallery = function (imageEl) {
            var wrapElId = imageEl.data('wrap-el-id'), //for some reason image el may be gone after load, so trying to fetch via data id
                wrapEl = jQuery('#' + wrapElId),
                galleryEl = wrapEl.parents('.gallery'),
                gallery;

            jQuery.each(galleries, function () {
                if (this.galleryEl.is(galleryEl)) {
                    gallery = this;
                    return false;
                }
            });
            if (!gallery) {
                throw new Error('No related gallery found');
            }

            return gallery;
        },
        getGalleryNotFullyRequested = function (gallery) {
            var notRequestedCount = 0;
            jQuery.each(gallery.images, function () {
                if (!this.thumbnailRequested || !this.previewRequested) {
                    ++notRequestedCount;
                }
            });
            return notRequestedCount;
        },
        getGalleryRequestedNotLoadCount = function (gallery, considerThumbnail, considerPreview) {
            var requestedNotLoad = 0;
            jQuery.each(gallery.images, function () {
                if (considerThumbnail && this.thumbnailRequested) {
                    if (this.thumbnailLoad === false && (new Date() - this.thumbnailRequested) < 120000) {
                        ++requestedNotLoad;
                    }
                }
                if (considerPreview && this.previewLoad) {
                    if (this.previewLoad === false && (new Date() - this.previewRequested) < 120000) {
                        ++requestedNotLoad;
                    }
                }
            });
            return requestedNotLoad;
        },
        getImageOfGallery = function (gallery, imageEl) {
            var wrapElId = imageEl.data('wrap-el-id'), //for some reason image el may be gone after load, so trying to fetch via data id
                wrapEl = jQuery('#' + wrapElId),
                image;

            jQuery.each(gallery.images, function () {
                if (this.wrapEl.is(wrapEl)) {
                    image = this;
                    return false;
                }
            });
            if (!image) {
                throw new Error('No related image found');
            }
            return image;
        };

    //init
    var galleries = galleryLoadGalleries(); //load all galleries
    galleryRenderAll(); //render boxes for all galleries the first time

    //request all images for visible boxes
    jQuery.each(galleries, function () {
        jQuery.each(this.images, function () {
            if (this.wrapEl.visible(true)) {
                improveImage(this, false);
            }
        });
    });

    //listen
    jQuery(window).on("resize", function () {
        galleryRenderAll();
    });

    var listenOnload = function () {
        jQuery('.gallery').imagesLoaded().progress(function (instance, imageFile) {
            var imageEl = jQuery(imageFile.img);

            if (!imageFile.isLoaded) {
                removeFaultyImageAndReRender(imageEl);
            }
            var gallery = getGallery(imageEl),
                galleryImage = getImageOfGallery(gallery, imageEl);

            if (galleryImage.previewRequested) {
                galleryImage.previewLoad = new Date();
            }
            if (galleryImage.thumbnailRequested) {
                galleryImage.thumbnailLoad = new Date();
            }

            //load images in general
            var totalRequested = 0;
            jQuery.each(galleries, function () {
                totalRequested += getGalleryRequestedNotLoadCount(this, true, true);
            });

            jQuery.each(galleries, function () {
                jQuery.each(this.images, function () {
                    if (totalRequested > 5) {
                        return false;
                    }
                    if (this.wrapEl.visible(true)) {
                        if (this.thumbnailRequested === false) {
                            totalRequested += improveImage(this, false);
                        }
                    }
                });
            });
            if (totalRequested > 5) {
                return;
            }

            jQuery.each(galleries, function () {
                jQuery.each(this.images, function () {
                    if (totalRequested > 5) {
                        return false;
                    }
                    if (this.wrapEl.visible(true)) {
                        if (this.previewRequested === false) {
                            totalRequested += improveImage(this, true);
                        }
                    }
                });
            });

            if (totalRequested > 5) {
                return;
            }

            jQuery.each(galleries, function () {
                jQuery.each(this.images, function () {
                    if (totalRequested > 5) {
                        return false;
                    }
                    if (this.previewRequested === false) {
                        totalRequested += improveImage(this, false);
                    }
                });
            });

            if (totalRequested > 5) {
                return;
            }

            jQuery.each(galleries, function () {
                jQuery.each(this.images, function () {
                    if (totalRequested > 5) {
                        return false;
                    }
                    if (this.previewRequested === false) {
                        totalRequested += improveImage(this, true);
                    }
                });
            });

        });
    };
    listenOnload();

    var continousLoader = setInterval(function () {
        listenOnload();

        var totalNotFullyRequested = 0;
        jQuery.each(galleries, function () {
            totalNotFullyRequested += getGalleryNotFullyRequested(this);
        });

        if (totalNotFullyRequested === 0) {
            console.log('all_requested');
            clearTimeout(continousLoader);
        }

    }, 1000);
});