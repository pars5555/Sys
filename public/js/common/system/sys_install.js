docReady(function () {
    var clearTemplatesCacheBtn = document.getElementById('clear_templates_cache');
    clearTemplatesCacheBtn.onclick = function () {
        Sys.action('install.actions.ClearTemplateCache', {},
                function () {
                },
                function (mgs) {
                    alert(mgs);
                });
    };
});