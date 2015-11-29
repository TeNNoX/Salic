/**
 * this script holds the managing functions of this SALiC CMS
 * it should only be loaded when in manage mode
 **/

var Salic = (function () {

    var currentModal = null;

    return {};
})();


$(function () {
    var editor;

    editor = ContentTools.EditorApp.get();
    editor.init('*[data-salic-name]', 'data-salic-name');


    editor.bind('save', function (regions) {
        if ($.isEmptyObject(regions)) {
            return;
        }
        console.log("Sending new contents to Server...");
        console.log(regions);

        // Set the editor as busy while we save our changes
        this.busy(true);

        $.post('save.php', {
            'pagekey': salic_page_info['pagekey'],
            'regions': regions
        }).always(function () {
            editor.busy(false);
        }).success(function (data) {
            if(data == "success") { //TODO: use .endswith() - maybe php warnings or so... ?
                new ContentTools.FlashUI('ok');
                console.log("Successfully saved!");
            } else {
                new ContentTools.FlashUI('no');
                alert("Response from server:\n" + data);
                console.log(data); //TODO: do something when error occurs (go back to edit mode ?)
            }
        }).fail(function (err) {
            new ContentTools.FlashUI('no');
            alert("Error: "+err+"\n\nResponse from server:\n" + err.responseText);
            console.log(err);
        });


        /*// Collect the contents of each region into a FormData instance
         payload = new FormData();
         for (name in regions) {
         if (regions.hasOwnProperty(name)) {
         payload.append(name, regions[name]);
         }
         }

         // Send the update content to the server to be saved
         function onStateChange(ev) {
         // Check if the request is finished
         if (ev.target.readyState == 4) {
         editor.busy(false);
         if (ev.target.status == '200') {
         // Save was successful, notify the user with a flash
         new ContentTools.FlashUI('ok');
         } else {
         // Save failed, notify the user with a flash
         new ContentTools.FlashUI('no');
         }
         }
         };

         xhr = new XMLHttpRequest();
         xhr.addEventListener('readystatechange', onStateChange);
         xhr.open('POST', '/save-my-page');
         xhr.send(payload); */
    });
});