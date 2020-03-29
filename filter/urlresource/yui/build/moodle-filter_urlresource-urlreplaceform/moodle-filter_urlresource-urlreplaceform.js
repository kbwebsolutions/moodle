YUI.add('moodle-filter_urlresource-urlreplaceform', function (Y, NAME) {

M.filter_urlresource = M.filter_urlresource || {};

M.filter_urlresource.urlreplaceforminit = function (data) {
    "use strict";

    var loading = false;

    function doSubmit(params, spinnernode, callbacksuccess) {

        if (loading) {
            return false;
        }

        loading = true;

        var url = M.cfg.wwwroot + '/filter/urlresource/ajax.php';
        var spinner = M.util.add_spinner(Y, spinnernode);

        Y.io(url, {

            data : params,

            on: {
                start : function() {

                    spinner.show();
                },

                success: function (id, resp) {
                    try {

                        var responsetext = Y.JSON.parse(resp.responseText);

                        // ...set Posts
                        callbacksuccess(responsetext);
                        loading = false;
                        spinner.hide();

                    } catch (e) {

                    //contentnode.setHTML("parsefailed");
                    }
                },
                failure: function() {
                    loading = false;
                    spinner.hide();
                }
            }
        });
    }

    var previewimage; // node to preview image.
    var imgurl;       // (fromfield-) node to hold current selected image.
    var status = 0;   // state of loading.
    var prevnext = 0;   // how image was loaded -  0 = previous, 1 = next.

    /** after loading the nex Image onImageloaded will be called to check size of the image.*/
    function loadNextImage() {

        if (data.urlimgs.length == 0) {
            return false;
        }

        prevnext = 1;

        // ... naxt image will be tested by data.offsett + 1
        if (data.offset + 1 >=  data.urlimgs.length) {
            data.offset = -1;
        }

        // ... fetch next image.
        previewimage.set('src', data.urlimgs[data.offset + 1]);
        return true;

    }

    function loadPrevImage() {

        if (data.urlimgs.length == 0) {
            return false;
        }

        prevnext = 0;

        // ... naxt image will be tested by data.offsett + 1
        if (data.offset - 1 >=  data.urlimgs.length) {
            data.offset = -1;
        }

        // ... fetch next image.
        previewimage.set('src', data.urlimgs[data.offset - 1]);
        return true;

    }

    function onImageLoadError() {
        data.urlimgs.splice(data.offset + 1, 1);
        loadNextImage();
    }

    /** check width after loading the image and nload next image if width is to small.*/
    function onImageLoaded() {

        var width = 0;

        if (previewimage.get('width')) {

            // Sizecheck.
            width = previewimage.get('width');

            if (width < 20) {
                // ...remove this image from list.
                data.urlimgs.splice(data.offset + 1, 1);
                return loadNextImage();
            }
        }

        if (prevnext == 1) {
            data.offset = data.offset + 1;
            Y.one('#imgsrc').setHTML(data.urlimgs[data.offset + 1]);
            imgurl.set('value', data.urlimgs[data.offset + 1]);
        } else {
            data.offset = data.offset - 1;
            Y.one('#imgsrc').setHTML(data.urlimgs[data.offset - 1]);
            imgurl.set('value', data.urlimgs[data.offset - 1]);
        }
        previewimage.show();
        return true;
    }

    function loadNewImages() {
        var param = {};
        param.action = 'loadnewimages';
        param.courseid = data.courseid;
        param.url = Y.one('#id_externalurl').get('value');

        doSubmit(param, previewimage.ancestor(), function (r) {
            onNewImagesLoaded(r);
        });
    }

    function onNewImagesLoaded(response) {

        if (response.error == 0) {

            var title = unescapeHTML(response.title);
            data.urlimgs = response.imgurls;
            Y.one('#id_title').set('value', title);
            loadNextImage();
            status = 1;

        } else {
            alert('check url');
        }
    }

    function onClickNextImage() {

        if (status > 0) {

            loadNextImage();

        } else {

            loadNewImages();
        }
    }

    function onClickPrevImage() {

        if (status > 0) {

            loadPrevImage();

        } else {

            loadNewImages();
        }
    }

    function resetData() {
        // reset all Data.
        status = 0;
        previewimage.set('src', '');
        previewimage.hide();
        Y.one('#imgsrc').setHTML('');
        imgurl.set('value', '');
        data.urlimgs = [];
        data.offset = -1;
    }


    function onChangeExternalUrl() {

        if (status > 0) {
            resetData();
        }
    }

    function unescapeHTML(p_string) {
        if ((typeof p_string === "string") && (new RegExp(/&amp;|&lt;|&gt;|&quot;|&#39;/).test(p_string)))
        {
            return p_string.replace(/&amp;/g, "&").replace(/&lt/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "\"").replace(/&#39;/g, "'");
        }

        return p_string;
    }

    /** initialize all necessary objects */
    function initialize() {

        Y.one('#id_externalurl').on('change', function (e) {
            onChangeExternalUrl();
            loadNewImages();
        });

        Y.one('#refreshimages').on('click', function(e) {
            e.preventDefault();
            loadNewImages();
        });

        Y.one('#nextimage').on('click', function(e) {
            e.preventDefault();
            onClickNextImage();
        });

        Y.one('#previmage').on('click', function(e) {
            e.preventDefault();
            onClickPrevImage();
        });

        previewimage = Y.one('#imgpreview');
        previewimage.on('load', function() {
            onImageLoaded();
        });

        previewimage.on('error', function() {
            onImageLoadError();
        });

        imgurl = Y.one('#imgurl');

        if ((data.urlimgs.length) > 0) {
            status = 1; // imgs loaded, valid externalurl.
        } else {
            resetData();
        }
    }

    initialize();
};

}, '@VERSION@', {"requires": ["base", "node"]});
