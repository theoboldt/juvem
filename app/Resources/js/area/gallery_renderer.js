var galleryRenderer = {
        render: function (gallery) {
            const containerEl = gallery.galleryEl,
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
        renderImage: function (wrapEl, imageData, blur) {
            var renderer = this;
            if (typeof imageData === 'string' || imageData instanceof String) {
                renderer._renderImageData(wrapEl, imageData, blur);
            } else {
                imageData.blob().then(function (blob) {
                    renderer._renderImageData(wrapEl, blob, blur);
                });
            }
        },
        _renderImageData: function (wrapEl, imageData, blur) {
            var aEl = wrapEl.find('a'),
                imgEl;
            aEl.empty();

            aEl.prepend('<img' +
                ' data-wrap-el-id="' + wrapEl.attr('id') + '"' +
                ' width="' + wrapEl.width() + '"' +
                ' height="' + wrapEl.height() + '"' +
                ' style="' + (blur ? 'filter: blur(3px);' : '') + '" />');

            imgEl = aEl.find('img');
            if (typeof imageData === 'string' || imageData instanceof String) {
                imgEl.attr('src', imageData);
            } else {
                imgEl.attr('src', window.URL.createObjectURL(imageData))
                    .on('load', function () {
                        window.URL.revokeObjectURL(this.src);
                    });
            }

        },
        galleries: null,
        /**
         * Loads galleries
         *
         * @returns {Array}
         */
        getGalleries: function () {
            if (this.galleries === null) {
                var renderer = this;
                this.galleries = [];
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

                    renderer.galleries.push({
                        galleryEl: galleryEl,
                        images: images
                    });
                });
            }

            return this.galleries;
        },
        galleryRenderAll: function () {
            var renderer = this;
            jQuery.each(this.getGalleries(), function () {
                if (this.galleryEl.data('width') !== this.galleryEl.width()) {
                    renderer.render(this);
                }
            });
        },
        removeFaultyImageAndReRender: function (imageEl) {
            var wrapEl = imageEl.parents('.gallery-image-wrap'),
                gallery = this.getGallery(imageEl),
                galleryImagesUpdated = [];

            jQuery.each(gallery.images, function () {
                if (this.wrapEl === wrapEl) {
                    wrapEl.remove();
                } else {
                    galleryImagesUpdated.push(this);
                }
            });
        },
        getGallery: function (imageEl) {
            var wrapElId = imageEl.data('wrap-el-id'), //for some reason image el may be gone after load, so trying to fetch via data id
                wrapEl = jQuery('#' + wrapElId),
                galleryEl = wrapEl.parents('.gallery'),
                gallery;

            jQuery.each(this.getGalleries(), function () {
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

        /**
         * Improve transmitted images quality, update to preview if allowed to
         *
         * @param image Image to update
         * @param allowPreview {boolean} If set to false and image has already thumbnail, nothing is done
         * @returns {boolean} If image is updated or not
         */
        improveImage: function (image, allowPreview) {
            if (image.thumbnailRequested && !image.previewRequested && allowPreview) {
                image.previewRequested = new Date();
                image.wrapEl.find('.gallery-image').css('background-image', 'url(\'' + image.srcThumbnail + '\')');
                this.renderImage(image.wrapEl, image.srcPreview, false);
                return true;
            } else if (!image.thumbnailRequested) {
                image.thumbnailRequested = new Date();
                this.renderImage(image.wrapEl, image.srcThumbnail, true);
                return true;
            }
            return false;
        },
        getTotalPreviewNotRequested: function () {
            var renderer = this,
                totalPreviewNotRequested = 0;
            jQuery.each(renderer.getGalleries(), function () {
                jQuery.each(this.images, function () {
                    if (!this.previewRequested) {
                        ++totalPreviewNotRequested;
                    }
                });
            });
            return totalPreviewNotRequested;
        },

        getGalleryNotFullyRequested: function (gallery) {
            var notRequestedCount = 0;
            jQuery.each(gallery.images, function () {
                if (!this.thumbnailRequested || !this.previewRequested) {
                    ++notRequestedCount;
                }
            });
            return notRequestedCount;
        },
        getTotalNotFullyRequestedCount: function () {
            var renderer = this,
                totalNotFullyRequested = 0;
            jQuery.each(renderer.getGalleries(), function () {
                totalNotFullyRequested += renderer.getGalleryNotFullyRequested(this);
            });
        },
        getTotalRequestedNotLoadCount: function () {
            var renderer = this,
                totalRequested = 0;
            jQuery.each(renderer.getGalleries(), function () {
                totalRequested += renderer.getGalleryRequestedNotLoadCount(this, true, true);
            });
            return totalRequested;
        },
        getGalleryRequestedNotLoadCount: function (gallery, considerThumbnail, considerPreview) {
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
        getImageOfGallery: function (gallery, imageEl) {
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
        }
    }
;

jQuery(document).ready(function () {

    galleryRenderer.galleryRenderAll(); ////render boxes for all galleries the first time

    //listen
    jQuery(window).on("resize", function () {
        galleryRenderer.galleryRenderAll();
    });

});