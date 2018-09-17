jQuery(document).ready(function () {
    if ('caches' in self) {
        galleryCacheApiLoader.load();
    } else {
        galleryClassicLoader.load();
    }
});