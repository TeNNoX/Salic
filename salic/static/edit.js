/**
 * this script holds the editing functions of this SALiC CMS
 * therefore, it should only be loaded when in edit mode
 **/

$(function () {
    var editor = ContentTools.EditorApp.get();
    editor.init('*[data-salic-name]', 'data-salic-name');

    //TODO: add translations to editor

    // HANDLE IMAGE UPLOADS
    function imageUploader(dialog) {
        var image, xhr, xhrComplete, xhrProgress;

        // for now, just show an alert 'not implemented yet'
        dialog.bind('imageUploader.fileReady', function () {
            alert("Sorry, Image upload is not implemented yet. :(");
            dialog.trigger('cancel');
            new ContentTools.FlashUI('no');
        });
    }

    ContentTools.IMAGE_UPLOADER = imageUploader;

    // SAVING
    editor.bind('save', function (regions) {
        if ($.isEmptyObject(regions)) {
            console.log("[SaLiC] Not saving empty changes");
            return; // we don't need to save empty changes
        }
        console.log("[SaLiC] Sending new contents to Server...");

        // Set the editor as busy while we save our changes
        this.busy(true);

        function onSaveFail(msg) {
            new ContentTools.FlashUI('no');
            console.log("[SaLiC] " + msg);
            alert("Error: " + msg);
            //TODO: Handle errors
            //TODO: save draft locally?
        }

        $.post("/edit/" + salic_page_info['language'] + "/" + salic_page_info['pagekey'] + "/save", {
            'regions': regions
        }).always(function () {
            editor.busy(false); // unbusy editor
        }).success(function (data) {
            try {
                var json = jQuery.parseJSON(data);

                if (!json['success']) {
                    onSaveFail(json['error'] || "APIError - (check api result manually)");
                } else {
                    new ContentTools.FlashUI('ok');
                    console.log("[SaLiC] Successfully saved!");
                }
            } catch (err) {
                onSaveFail("JSError - " + err);
            }
        }).fail(function (err) {
            onSaveFail("AJAXError - " + err + "\n\nResponse from server:\n" + err.responseText);
        });
    });
});