$(function () {
    $(document).ready(function () {
        $('.markdown-editable').each(function () {
            var el = $(this);
            new EasyMDE({
                autoDownloadFontAwesome: false,
                forceSync: true,
                uploadImage: false,
                element: el[0],
                spellChecker: false,
                status: false,
                promptURLs: true,
                promptTexts: {
                    link: "URL/Webadresse der zu verlinkenden Website:",
                },
                toolbar: [{
                    name: "bold",
                    action: EasyMDE.toggleBold,
                    className: "glyphicon glyphicon-bold",
                    title: "Fett",
                }, {
                    name: "italic",
                    action: EasyMDE.toggleItalic,
                    className: "glyphicon glyphicon-italic",
                    title: "Kursiv",
                }, '|', {
                    name: "heading-1",
                    action: EasyMDE.toggleHeading1,
                    className: "glyphicon glyphicon-header",
                    title: "Überschrift 1",
                }, {
                    name: "heading-2",
                    action: EasyMDE.toggleHeading2,
                    className: "glyphicon glyphicon-header",
                    title: "Überschrift 2",
                }, {
                    name: "heading-3",
                    action: EasyMDE.toggleHeading3,
                    className: "glyphicon glyphicon-header",
                    title: "Überschrift 3",
                }, '|', {
                    name: "quote",
                    action: EasyMDE.toggleBlockquote,
                    className: "glyphicon glyphicon-comment",
                    title: "Zitat",
                }, {
                    name: "link",
                    action: EasyMDE.drawLink,
                    className: "glyphicon glyphicon-link",
                    title: "Link",
                }, '|', {
                    name: "unordered-list",
                    action: EasyMDE.toggleUnorderedList,
                    className: "glyphicon glyphicon-align-justify",
                    title: "Liste",
                }, {
                    name: "ordered-list",
                    action: EasyMDE.toggleOrderedList,
                    className: "glyphicon glyphicon-list",
                    title: "Aufzählung",
                }, '|', {
                    name: "side-by-side",
                    action: EasyMDE.toggleSideBySide,
                    className: "glyphicon glyphicon-sound-dolby",
                    title: "Editor und Vorschauansicht",
                }, {
                    name: "fullscreen",
                    action: EasyMDE.toggleFullScreen,
                    className: "glyphicon glyphicon-fullscreen",
                    title: "Vollbild (Esc zum Verlassen)",
                }, {
                    name: "guide",
                    action: "https://www.markdownguide.org/basic-syntax/",
                    className: "glyphicon glyphicon-question-sign",
                    title: "Aufzählung",
                }]
            });
        });
    });
});
