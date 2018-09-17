var galleryCacheApiLoader = {

    cacheName: 'g_v1',

    continousLoader: null,

    load: function () {
        var loader = this;

        //first load all which are already in cache
        caches.open(loader.cacheName).then(function (cache) {
            jQuery.each(galleryRenderer.getGalleries(), function () {
                jQuery.each(this.images, function () {
                    var image = this,
                        imgSrcRequest = new Request(image.srcPreview);

                    cache.match(imgSrcRequest).then(function (cachedResponse) {
                        if (cachedResponse) {
                            image.previewRequested = true;
                            image.previewLoad = new Date();
                            galleryRenderer.renderImage(image.wrapEl, cachedResponse.clone(), false);
                            cache.delete(new Request(image.srcThumbnail));
                        } else {
                            imgSrcRequest = new Request(image.srcThumbnail);
                            cache.match(imgSrcRequest).then(function (cachedResponse) {
                                if (cachedResponse) {
                                    image.thumbnailRequested = true;
                                    image.thumbnailLoad = new Date();
                                    galleryRenderer.renderImage(image.wrapEl, cachedResponse.clone(), true);
                                }
                            });
                        }
                    });
                });
            });
        });

        //wait until dealt with cache
        setTimeout(function () {
            //request all images for visible boxes
            jQuery.each(galleryRenderer.getGalleries(), function () {
                jQuery.each(this.images, function () {
                    if (this.wrapEl.visible(true)) {
                        loader.improveImage(this, false);
                    }
                });
            });
            loader.continousLoader = setInterval(function () {
                var totalRequested;

                if (galleryRenderer.getTotalPreviewNotRequested() === 0) {
                    clearTimeout(loader.continousLoader);
                    return;
                }
                //load images in general
                totalRequested = galleryRenderer.getTotalRequestedNotLoadCount();
                jQuery.each(galleryRenderer.getGalleries(), function () {
                    jQuery.each(this.images, function () {
                        if (totalRequested > 5) {
                            return false;
                        }
                        if (this.wrapEl.visible(true)) {
                            if (this.thumbnailRequested === false) {
                                totalRequested += loader.improveImage(this, false);
                            }
                        }
                    });
                });
                if (totalRequested > 5) {
                    return;
                }

                jQuery.each(galleryRenderer.getGalleries(), function () {
                    jQuery.each(this.images, function () {
                        if (totalRequested > 5) {
                            return false;
                        }
                        if (this.wrapEl.visible(true)) {
                            if (this.previewRequested === false) {
                                totalRequested += loader.improveImage(this, true);
                            }
                        }
                    });
                });

                if (totalRequested > 5) {
                    return;
                }

                jQuery.each(galleryRenderer.getGalleries(), function () {
                    jQuery.each(this.images, function () {
                        if (totalRequested > 5) {
                            return false;
                        }
                        if (this.previewRequested === false) {
                            totalRequested += loader.improveImage(this, false);
                        }
                    });
                });

                if (totalRequested > 5) {
                    return;
                }

                jQuery.each(galleryRenderer.getGalleries(), function () {
                    jQuery.each(this.images, function () {
                        if (totalRequested > 5) {
                            return false;
                        }
                        if (this.previewRequested === false) {
                            totalRequested += loader.improveImage(this, true);
                        }
                    });
                });
            }, 500);

            loader.updateSizeButton();
        }, 160);
    },

    improveImage: function (image, allowPreview) {
        var loader = this,
            imgSrcRequest,
            blur,
            requestThumbnail = false,
            requestPreview = false;
        if (image.thumbnailRequested && !image.previewRequested && allowPreview) {
            image.previewRequested = new Date();
            imgSrcRequest = new Request(image.srcPreview);
            requestPreview = true;
            blur = false;
        } else if (!image.thumbnailRequested && !image.previewRequested) {
            imgSrcRequest = new Request(image.srcThumbnail);
            image.thumbnailRequested = new Date();
            blur = true;
            requestThumbnail = true;
        } else {
            return false;
        }

        caches.open(this.cacheName).then(function (cache) {
            return cache.match(imgSrcRequest).then(function (cachedResponse) {
                if (cachedResponse) {
                    return cachedResponse.clone();
                } else {
                    return fetch(imgSrcRequest, {credentials: 'same-origin'}).then(function (response) {
                        if (requestPreview) {
                            image.previewLoad = new Date();
                            cache.delete(new Request(image.srcThumbnail));
                        }
                        if (requestThumbnail) {
                            image.thumbnailLoad = new Date();
                        }
                        if (response.ok) {
                            cache.put(imgSrcRequest, response.clone());
                            return response;
                        } else {
                            throw response.statusText;
                        }
                    });
                }
            });
        }).then(
            function (response) {
                loader.updateSizeButton();
                if (requestThumbnail && image.previewLoad) {
                    //do not render thumbnail if preview already load
                    return;
                }
                galleryRenderer.renderImage(image.wrapEl, response, blur);
            }, function (error) {
                galleryRenderer.removeFaultyImageAndReRender(image.wrapEl.find('img'));
                console.log(error);
            }
        );
        return true;
    },

    updateSizeButton: function () {
        var loader = this,
            size = 0,
            wrapEl = jQuery('#gallery-cache-clear-btn-wrap'),
            buttonEl;
        if (!wrapEl.length) {
            return;
        }
        if (!wrapEl.find('button').length) {
            wrapEl.prepend('<button type="button" class="btn btn-primary" data-size="0">Cache leeren</button>');
            wrapEl.on('click', function (e) {
                e.preventDefault();
                caches.open(loader.cacheName).then(function (cache) {
                    cache.keys().then(function (keys) {
                        keys.forEach(function (request, index, array) {
                            cache.delete(request);
                        });
                    });
                    buttonEl = wrapEl.find('button').text('Cache leeren');
                });
            });
        }
        buttonEl = wrapEl.find('button');

        caches.open(this.cacheName).then(function (cache) {
            cache.keys().then(function (keys) {
                keys.forEach(function (request, index, array) {
                    cache.match(request).then(function (response) {
                        if (!response) {
                            return;
                        }

                        if (response.headers && response.headers.has('content-length')) {
                            var contentLength = parseInt(response.headers.get('content-length'));
                            size = size + (isNaN(contentLength) ? 0 : contentLength);
                        } else {
                            var blobSize = response.clone().blob().size;
                            size = size + (isNaN(blobSize) ? 0 : blobSize);
                        }
                        buttonEl.text('Cache leeren (~' + Math.round((size / 1024 / 1024)) + ' MB)')
                    });
                });
            });
        });
    }
};