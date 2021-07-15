(function() {
    function setUpSelect(jQueryObject, url, valueAccessor, textAccessor) {
        jQueryObject
        .selectpicker({
            liveSearch: true
        })
        .ajaxSelectPicker({
            ajax: {
                url: url,
                method: "GET"
            },
            locale: {
                emptyTitle: 'Type to search...'
            },
            clearOnEmpty: false,
            emptyRequest: true,
            preprocessData: function(data){
                return data.map(
                    (current) => {
                        return {
                            'value': current[valueAccessor],
                            'text': current[textAccessor],
                            'disabled': false
                        }
                    }
                );
            },
            preserveSelected: false
        });
    }

    function showLoading() {
        $(".loadingHeader").removeClass("hidden");
    }

    function hideLoading() {
        $(".loadingHeader").addClass("hidden");
    }

    $.ajaxSetup({
        beforeSend: showLoading,
        complete: hideLoading
    });

    window["showLoading"] = showLoading;
    window["hideLoading"] = hideLoading;
    window["setUpSelect"] = setUpSelect;
})();
