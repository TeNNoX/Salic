/** the javascript stuff for the backend **/

$(function () {
    var Salic = { // namespace - I don't really need it here, but it's fun!

        getSidebarState: function () {
            return $("#sidebar").hasClass('toggled');
        },

        setSidebarState: function (flag) {
            var sidebar = $("#sidebar");

            if (flag) {
                sidebar.addClass('toggled');
                sidebar.attr('style', 'margin-left: 0px');
            } else {
                sidebar.removeClass('toggled');
                sidebar.removeAttr('style');
            }
        }
    };

    $("#nav-hamburger").click(function () { // toggle sidebar
        Salic.setSidebarState(!Salic.getSidebarState());
    });
});