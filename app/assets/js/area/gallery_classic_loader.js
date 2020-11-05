var galleryClassicLoader = {

    continousLoader: null,

    load: function () {
        var loader = this;
        //request all images for visible boxes
        jQuery.each(galleryRenderer.getGalleries(), function () {
            jQuery.each(this.images, function () {
                if (this.wrapEl.visible(true)) {
                    galleryRenderer.improveImage(this, false);
                }
            });
        });

        loader.handleLoad();

        loader.continousLoader = setInterval(function () {
            if (galleryRenderer.getTotalPreviewNotRequested() === 0) {
                clearTimeout(loader.continousLoader);
            }
            loader.handleLoad();
        }, 1000);
    },

    handleLoad: function () {
        jQuery('.gallery').imagesLoaded().progress(function (instance, imageFile) {
            var imageEl = jQuery(imageFile.img);

            if (!imageFile.isLoaded) {
                galleryRenderer.removeFaultyImageAndReRender(imageEl);
            }
            var gallery = galleryRenderer.getGallery(imageEl),
                galleryImage = galleryRenderer.getImageOfGallery(gallery, imageEl);

            if (galleryImage.previewRequested) {
                galleryImage.previewLoad = new Date();
            }
            if (galleryImage.thumbnailRequested) {
                galleryImage.thumbnailLoad = new Date();
            }

            //load images in general
            var totalRequested = galleryRenderer.getTotalRequestedNotLoadCount();

            jQuery.each(galleryRenderer.getGalleries(), function () {
                jQuery.each(this.images, function () {
                    if (totalRequested > 5) {
                        return false;
                    }
                    if (this.wrapEl.visible(true)) {
                        if (this.thumbnailRequested === false) {
                            totalRequested += galleryRenderer.improveImage(this, false);
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
                            totalRequested += galleryRenderer.improveImage(this, true);
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
                        totalRequested += galleryRenderer.improveImage(this, false);
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
                        totalRequested += galleryRenderer.improveImage(this, true);
                    }
                });
            });

        });
    }
};