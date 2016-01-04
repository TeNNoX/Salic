/**
 * this script holds the editing functions of this SALiC CMS
 * therefore, it should only be loaded when in edit mode
 **/

$(function () {
    var editor = ContentTools.EditorApp.get();
    editor.init('*[data-salic-name]', 'data-salic-name');

    // SAVING
    editor.bind('save', function (regions) {
        if ($.isEmptyObject(regions)) {
            return; // we don't need to save empty changes
        }
        console.log("[SaLiC] Sending new contents to Server...");

        // Set the editor as busy while we save our changes
        this.busy(true);

        function onSaveFail(msg) {
            new ContentTools.FlashUI('no');
            console.log("[SaLiC] " + msg);
            alert(msg);
            //TODO: do something when error occurs (go back to edit mode ?)
        }

        $.post("/edit/" + salic_page_info['language'] + "/" + salic_page_info['pagekey'] + "/save", {
            'regions': regions
        }).always(function () {
            editor.busy(false);
        }).success(function (data) {
            if (data.endsWith("success")) { // ignore stuff like Notices before, what counts is the success at the end :P
                new ContentTools.FlashUI('ok');
                console.log("[SaLiC] Successfully saved!");
            } else {
                onSaveFail("Response from server:\n" + data);
            }
        }).fail(function (err) {
            onSaveFail("Error: " + err + "\n\nResponse from server:\n" + err.responseText);
        });
    });
});