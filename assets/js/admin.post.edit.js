/* global moment, _nails, CKEDITOR */
var NAILS_Admin_Blog_Create_Edit;
NAILS_Admin_Blog_Create_Edit = function(mode, initialPublishState)
{
    this.blog_id        = null;
    this.upload_token   = null;
    this._api           = null;
    this.sMode = mode;
    this.sInitialPublishState = initialPublishState;

    // --------------------------------------------------------------------------

    this.init = function(blog_id, upload_token)
    {
        //  Set vars
        this.blog_id      = blog_id;
        this.upload_token = upload_token;

        // --------------------------------------------------------------------------

        //  Set up the API interface
        this._api = new window.NAILS_API();

        // --------------------------------------------------------------------------

        //  Init everything!
        this.initTabs();
        this._init_publish_date();
        this._init_comments();
        this._init_type();
        this._init_select2();
        this._init_gallery();
        this._init_submit_btn_txt();
        this._init_preview();
    };

    // --------------------------------------------------------------------------

    this.initTabs = function()
    {
        $('ul.tabs a').on('click', function()
        {
            var tab = $(this).data('tab');
            $('#activeTab').val(tab);
        });
    };

    // --------------------------------------------------------------------------

    this._init_publish_date = function()
    {
        var _this = this;

        $('#is-published').on('change', function()
        {
            _this._publish_change();
        });

        $('#is-published').closest('div.field').on('toggle', function()
        {
            _this._publish_change();
        });

        this._publish_change();
    };

    // --------------------------------------------------------------------------

    this._publish_change = function()
    {
        var _published = $('#is-published').is(':checked');

        if (_published)
        {
            $('#field-publish-date').show();

            //  If it's empty set it to now
            var _publish_date = $.trim($('#field-publish-date input.datetime').val());

            if (_publish_date.length <= 0)
            {
                var _date   = new Date();
                var _year   = _date.getFullYear();
                var _month  = _date.getMonth()+1;
                var _day    = _date.getDate();
                var _hour   = _date.getHours();
                var _minute = _date.getMinutes();

                //  Pad strings
                if (_month.toString().length === 1)
                {
                    _month = '0' + _month;
                }

                if (_day.toString().length === 1)
                {
                    _day = '0' + _day;
                }

                if (_hour.toString().length === 1)
                {
                    _hour = '0' + _hour;
                }

                if (_minute.toString().length === 1)
                {
                    _minute = '0' + _minute;
                }

                var _compiled = _year + '-' + _month + '-' + _day + ' ' + _hour + ':' + _minute;

                $('#field-publish-date input.datetime').val(_compiled);
            }
        }
        else
        {
            $('#field-publish-date').hide();
        }

        _nails.addStripes();
    };

    // --------------------------------------------------------------------------

    this._init_comments = function() {

        var _this = this;

        $('#comments-enabled').on('change', function()
        {
            _this.commentsChange();
        });

        $('#comments-enabled').closest('div.field').on('toggle', function()
        {
            _this.commentsChange();
        });

        this.commentsChange();
    };

    // --------------------------------------------------------------------------

    this.commentsChange = function() {

        var commentsEnabled = $('#comments-enabled').is(':checked');

        if (commentsEnabled)
        {
            $('#field-comments-expire').show();
        }
        else
        {
            $('#field-comments-expire').hide();
        }

        _nails.addStripes();
    };

    // --------------------------------------------------------------------------

    this._init_type = function() {

        var _this = this;

        //  Type changing
        $('#post-type').on('change', function()
        {
            _this.typeChange();
        });

        this.typeChange();

        // --------------------------------------------------------------------------

        //  Type JS
        $('#post-type-fields-photo').on('click', 'img,a', function() {

            var cdnManagerUrl = $(this).closest('.type-fields').data('manager-url');

            if (cdnManagerUrl.length) {

                cdnManagerUrl += cdnManagerUrl.indexOf('?') >= 0 ? '&isModal=1' : '?isModal=1';

                $.fancybox.open({
                    href: cdnManagerUrl,
                    type: 'iframe',
                    iframe: {
                        preload: false // fixes issue with iframe and IE
                    }
                });
            }

            return false;
        });
    };

    // --------------------------------------------------------------------------

    this.typePhotoCdnManagerCallback = function(bucket, filename, id)
    {
        var urlScheme = $('#post-type-fields-photo').data('url-scheme');

        urlScheme = urlScheme.replace('{{width}}', 500);
        urlScheme = urlScheme.replace('{{height}}', 500);
        urlScheme = urlScheme.replace('{{bucket}}', bucket);
        urlScheme = urlScheme.replace('{{filename}}{{extension}}', filename);

        $('#post-type-fields-photo img').attr('src', urlScheme);
        $('#post-type-fields-photo input').val(id);
        return true;
    };

    // --------------------------------------------------------------------------

    this.typeChange = function() {

        var postType = $('#post-type').val();
        $('.type-fields').hide();
        $('#post-type-fields-' + postType.toLowerCase()).show();
    };

    // --------------------------------------------------------------------------

    this._init_select2 = function()
    {
        var _this = this;

        // --------------------------------------------------------------------------

        //  Define targets and URLs
        var _target = {};
        var _url = {};

        _target.categories  = '#tab-categories select.categories';
        _url.categories     = window.SITE_URL + 'admin/blog/category/index/' + this.blog_id + '?isModal=1';

        _target.tags    = '#tab-tags select.tags';
        _url.tags       = window.SITE_URL + 'admin/blog/tag/index/' + this.blog_id + '?isModal=1';

        // --------------------------------------------------------------------------

        //  Bind fancybox to select2s
        $(document).on('click', 'a.manage-categories', function()
        {
            $.fancybox.open(_url.categories, {
                type: 'iframe',
                width: '95%',
                height: '95%',
                beforeClose: function() {
                    _this._rebuild_select2(_target.categories);
                }
            });
            return false;
        });

        $(document).on('click', 'a.manage-tags', function() {
            $.fancybox.open(_url.tags, {
                type: 'iframe',
                width: '95%',
                height: '95%',
                beforeClose: function() {
                    _this._rebuild_select2(_target.tags);
                }
            });
            return false;
        });

        // --------------------------------------------------------------------------

        //  Ensure all select2s are updated when their tab is shown
        $('ul.tabs a:not(.disabled)').on('click', function()
        {
            setTimeout(function()
            {
                $(_target.categories).trigger('change');
                $(_target.tags).trigger('change');
            }, 1);
        });
    };

    // --------------------------------------------------------------------------

    this._rebuild_select2 = function(target)
    {
        var _DATA = $('.fancybox-iframe').get(0).contentWindow._DATA;

        if (typeof(_DATA) === 'undefined')
        {
            //  Do nothing, nothing to work with
            return false;
        }

        //  Fetch the target(s)
        var _targets = $(target);

        if (!_targets.length)
        {
            //  Target doesn't exist, ignore
            return false;
        }

        //  Rebuild the target(s)
        _targets.each(function()
        {
            //  Save a referene to the target
            var _target = this;

            //  Get the currently selected items in this select
            //  and store as an array of ID's

            var _selected = [];
            $('option:selected', this).each(function()
            {
                _selected.push(parseInt($(this).val(), 10));
            });

            //  Rebuild, marking as selected where appropriate
            $(this).empty();
            $.each(_DATA, function()
            {
                var _option = $('<option>');

                _option.val(this.id);
                _option.html(this.label);

                if ($.inArray(this.id, _selected) > -1)
                {
                    _option.prop('selected', true);
                }

                $(_target).append(_option);
            });

            //  Trigger the select2
            $(this).trigger('change');
        });
    };

    // --------------------------------------------------------------------------

    this._init_gallery = function()
    {
        var _this = this;

        //  Init uploadify
        //  TODO: replace uploadify, it's lack of session support is killing me.
        //  Additionally, if CSRF is enabled, this won't work.

        $('#file_upload').uploadify({
            'debug': false,
            'auto': true,
            'swf': window.NAILS.URL + 'packages/uploadify/uploadify.swf',
            'queueID': 'gallery-items',
            'uploader': window.SITE_URL + 'api/cdn/object/create',
            'fileSizeLimit': 2048,
            'fileObjName': 'upload',
            'fileTypeExts': '*.gif; *.jpg; *.jpeg; *.png',
            'formData': {
                'token': this.upload_token,
                'bucket': 'blog-post-gallery',
                'return': 'URL|THUMB|100x100,34x34'
            },
            'itemTemplate': $('#template-uploadify').html(),
            'onSelect': function() {
                if ($('#gallery-items li.gallery-item').length) {
                    $('#gallery-items li.empty').fadeOut(250, function() {
                        $(this).parent().removeClass('empty');
                    });
                }
            },
            'onUploadStart': function() {

                //  Prevent submits
                $('#post-form').off('submit');
                $('#post-form').on('submit', function() {
                    return false;
                });

                //  Destroy CKEditor instance
                if (typeof(CKEDITOR.instances.post_body) !== 'undefined'){

                    CKEDITOR.instances.post_body.destroy();
                }
                if (typeof(CKEDITOR.instances.post_excerpt) !== 'undefined'){

                    CKEDITOR.instances.post_excerpt.destroy();
                }

                var _uploading_string = 'Uploading...';
                var _button_val = $('#post-form input[type=submit]').val();
                window.onbeforeunload = function() {
                    return 'Uploads are in progress. Leaving this page will cause them to stop.';
                };

                //  Disable tabs - SWFUpload aborts uploads if it is hidden.
                $('ul.tabs li a').addClass('disabled');
                $('#upload-message').show();

                if (_button_val !== _uploading_string) {
                    $('#post-form input[type=submit]').attr({
                        'data-old_val': _button_val,
                        'disabled': 'disabled'
                    }).val('Uploading...');
                }
            },
            'onQueueComplete': function() {

                $('#post-form').off('submit');
                $('#post-form input[type=submit]').removeAttr('disabled').val($('#post-form input[type=submit]').data('old_val'));
                window.onbeforeunload = null;

                //  Enable tabs - SWFUpload aborts uploads if it is hidden.
                $('ul.tabs li a').removeClass('disabled');
                $('#upload-message').hide();

                //  Reinit description wysiwyg's
                if (typeof(CKEDITOR.instances.post_body) === 'undefined'){

                    $('#post_body').ckeditor(
                    {
                        customConfig: window.NAILS.URL + 'js/ckeditor.config.default.min.js'
                    });
                }
                if (typeof(CKEDITOR.instances.post_excerpt) === 'undefined'){

                    $('#post_excerpt').ckeditor(
                    {
                        customConfig: window.NAILS.URL + 'js/ckeditor.config.basic.min.js'
                    });
                }
            },
            'onUploadProgress': function(file, bytesUploaded, bytesTotal) {
                var _percent = bytesUploaded / bytesTotal * 100;

                $('#' + file.id + ' .progress').css('height', _percent + '%');
            },
            'onUploadSuccess': function(file, data) {
                var _data;
                try
                {
                    _data = JSON.parse(data);
                }
                catch(err)
                {
                    _data = {};
                }

                // --------------------------------------------------------------------------

                var _html = $.trim($('#template-gallery-item').html());
                var _item = $($.parseHTML(_html));

                _item.attr('id', file.id + '-complete');
                $('#' + file.id).replaceWith(_item);

                // --------------------------------------------------------------------------

                var _target = $('#' + file.id + '-complete');

                if (!_target.length) {
                    _html = $.trim($('#template-gallery-item').html());
                    _item = $($.parseHTML(_html));

                    _item.attr('id', file.id + '-complete');
                    $('#' + file.id).replaceWith(_item);

                    _target = $('#' + file.id + '-complete');
                }

                // --------------------------------------------------------------------------

                //  Switch the response code
                if (_data.status === 200) {
                    //  Insert the image
                    var _img = $('<img>').attr('src', _data.object_url[0]).on('load', function() {
                        _target.removeClass('crunching');
                    });
                    var _del = $('<a>').attr({
                        'href': '#',
                        'class': 'delete',
                        'data-object_id': _data.object_id
                    });

                    _target.append(_img).append(_del).find('input').val(_data.object_id);

                    // --------------------------------------------------------------------------

                    //  Update any variations

                    //  Create a new checkbox item
                    _img = $('<img>').attr({
                        'src': _data.object_url[1]
                    });
                    var _in = $('<input>').attr({
                        'type': 'checkbox',
                        'value': _data.object_id
                    });
                    var _li = $('<li>').addClass('image object-id-' + _data.object_id).append(_in).append(_img);

                    //  Find variations and for each of them append this item
                    $('#product-variations .variation').each(function() {
                        //  Set the name of the checkbox based on this variants counter ID
                        _in.attr('name', 'variation[' + $(this).data('counter') + '][gallery][]');

                        $('ul.gallery-associations', this).removeClass('empty');
                        _li.clone().insertBefore($('ul.gallery-associations li.actions', this));
                    });

                    //  Now update the template
                    var _template = $('<div>').html($.parseHTML($.trim($('#template-variation').html(), null, true)));

                    _in.attr('name', 'variation[{{counter}}][gallery][]');
                    $('ul.gallery-associations', _template).removeClass('empty');
                    _li.clone().insertBefore($('ul.gallery-associations li.actions', _template));

                    //  Replace the template
                    $('#template-variation').html($(_template).html());
                } else {
                    //  An error occurred
                    var _filename = $('<p>').addClass('filename').text(file.name);
                    var _message = $('<p>').addClass('message').text(_data.error);

                    _target.addClass('error').append(_filename).append(_message).removeClass('crunching');
                }
            },
            'onUploadError': function(file, errorCode, errorMsg, errorString) {
                var _target = $('#' + file.id + '-complete');

                if (!_target.length) {
                    var _html = $.trim($('#template-gallery-item').html());
                    var _item = $($.parseHTML(_html));

                    _item.attr('id', file.id + '-complete');
                    $('#' + file.id).replaceWith(_item);

                    _target = $('#' + file.id + '-complete');
                }

                var _filename = $('<p>').addClass('filename').text(file.name);
                var _message = $('<p>').addClass('message').text(errorString);

                _target.addClass('error').append(_filename).append(_message).removeClass('crunching');
            }
        });

        // --------------------------------------------------------------------------

        //  Init sorting
        $('#gallery-items').disableSelection().sortable({
            placeholder: 'gallery-item placeholder',
            items: "li.gallery-item"
        });

        // --------------------------------------------------------------------------

        //  Removes/Cancels an upload
        $(document).on('click', '#gallery-items .gallery-item .remove', function()
        {
            var _instance_id = $(this).data('instance_id');
            var _file_id = $(this).data('file_id');

            $('#' + _instance_id).uploadify('cancel', _file_id);
            $('#' + _file_id + ' .data-cancel').text('Cancelled').show();
            $('#' + _file_id).addClass('cancelled');

            if ($('#gallery-items li.gallery-item:not(.cancelled)').length === 0)
            {
                $('#gallery-items').addClass('empty');
                $('#gallery-items li.empty').css('opacity', 0).delay(1000).animate({
                    opacity: 1
                }, 250);
            }

            return false;
        });

        // --------------------------------------------------------------------------

        //  Deletes an uploaded image
        $(document).on('click', '#gallery-items .gallery-item .delete', function()
        {
            var _object = this;

            $('#dialog-confirm-delete').dialog(
            {
                resizable: false,
                draggable: false,
                modal: true,
                dialogClass: "no-close",
                buttons:
                {
                    "Delete Image": function()
                    {
                        var _object_id = $(_object).data('object_id');

                        //  Send off the delete request
                        var _call = {
                            'controller'    : 'cdn/object',
                            'method'        : 'delete',
                            'action'        : 'POST',
                            'data'          :
                            {
                                'object_id': _object_id
                            }
                        };
                        _this._api.call(_call);

                        // --------------------------------------------------------------------------

                        $(_object).closest('li.gallery-item').addClass('deleted').fadeOut('slow', function()
                        {
                            $(_object).closest('li.gallery-item').remove();
                        });

                        //  Remove the image from any variations
                        $('.image.object-id-' + _object_id).remove();

                        //  Update the template
                        var _template = $('<div>').html($.parseHTML($.trim($('#template-variation').html(), null, true)));

                        $('.image.object-id-' + _object_id, _template).remove();

                        // --------------------------------------------------------------------------

                        //  Show the empty screens
                        if ($('#gallery-items li.gallery-item:not(.deleted)').length === 0)
                        {
                            $('#gallery-items').addClass('empty');
                            $('#gallery-items li.empty').css('opacity', 0).delay(1000).animate({
                                opacity: 1
                            }, 250);

                            //  Variations
                            $('ul.gallery-associations').addClass('empty');

                            //  Template
                            $('ul.gallery-associations', _template).addClass('empty');
                        }

                        // --------------------------------------------------------------------------

                        //  Replace the template
                        $('#template-variation').html($(_template).html());

                        // --------------------------------------------------------------------------

                        //  Close dialog
                        $(this).dialog("close");
                    },
                    Cancel: function()
                    {
                        $(this).dialog("close");
                    }
                }
            });

            return false;
        });
    };

    // --------------------------------------------------------------------------

    this._init_submit_btn_txt = function() {

        var _this = this;
        $('#field-is-published').on('toggle', function() {
            setTimeout(function() {
                _this.calcSubmitBtnTxt();
            }, 100);
        });
        $('#field-publish-date input').on('change', function() {
            setTimeout(function() {
                _this.calcSubmitBtnTxt();
            }, 100);
        });

        //  And run now too, so button is correct at outset
        _this.calcSubmitBtnTxt();
    };

    // --------------------------------------------------------------------------

    this.calcSubmitBtnTxt = function() {

        /**
         * CREATING A POST
         * - If publish is to "on" and publish date is in the future then the button should read "Schedule Post".
         * - If publish is to "on" then the button should read "Publish Now".
         * - If publish is set to "off" then button should read "Save Draft"
         *
         * EDITING A POST
         * - If post is previously unpublished and publish is to "on" and publish date is in the future then the button should read "Schedule Post".
         * - If post is previously unpublished and publish is to "on" then the button should read "Publish Now".
         * - If post is previously unpublished and publish is set to "off" then button should read "Update Draft"
         *
         * - If post is previously published and publish is to "on" and publish date is in the future then the button should read "Unpublish & Schedule Post".
         * - If post is previously published and publish is to "on" then the button should read "Update Post".
         * - If post is previously published and publish is set to "off" then button should read "Unpublish Post & Save as Draft"
         *
         * - If post is previously scheduled and publish is to "on" and publish date is in the future then the button should read "Schedule Post".
         * - If post is previously scheduled and publish is to "on" then the button should read "Publish Post".
         * - If post is previously scheduled and publish is set to "off" then button should read "Unschedule Post & Save as Draft"
         */

        var now = moment();
        var publishDateVal = $('#field-publish-date input').val();
        var publishDate;
        var isSetToPublish = $('#field-is-published .toggle').data('toggles').active;

        if (publishDateVal.length) {

            publishDateVal += ':00';
            publishDate = moment(publishDateVal, 'YYYY-MM-DD HH:mm:ss');

        } else {

            publishDate = moment();
        }

        if (this.sMode === 'CREATE') {

            if (isSetToPublish) {

                if (publishDate.isAfter(now)) {
                    this.setSubmitBtnTxt('Schedule Post', 'warning');
                } else {
                    this.setSubmitBtnTxt('Publish Now', 'success');
                }

            } else {

                this.setSubmitBtnTxt('Save Draft');
            }

        } else if (this.sMode === 'EDIT') {

            if (this.sInitialPublishState === 'PUBLISHED') {

                if (isSetToPublish) {

                    if (publishDate.isAfter(now)) {
                        this.setSubmitBtnTxt('Unpublish & Schedule Post', 'warning');
                    } else {
                        this.setSubmitBtnTxt('Update Post', 'success');
                    }

                } else {

                    this.setSubmitBtnTxt('Unpublish & Save Draft');
                }

            } else if (this.sInitialPublishState === 'SCHEDULED') {

                if (isSetToPublish) {

                    if (publishDate.isAfter(now)) {
                        this.setSubmitBtnTxt('Schedule Post', 'warning');
                    } else {
                        this.setSubmitBtnTxt('Publish Now', 'success');
                    }

                } else {

                    this.setSubmitBtnTxt('Unschedule Post & Save as Draft');
                }

            } else if (this.sInitialPublishState === 'DRAFT') {

                if (isSetToPublish) {

                    if (publishDate.isAfter(now)) {
                        this.setSubmitBtnTxt('Schedule Post', 'warning');
                    } else {
                        this.setSubmitBtnTxt('Publish Now', 'success');
                    }

                } else {

                    this.setSubmitBtnTxt('Update Draft');
                }
            }
        }
    };

    // --------------------------------------------------------------------------

    this.setSubmitBtnTxt = function(txt, context)
    {
        context = context || 'default';
        $('#btnSubmit')
            .text(txt)
            .attr('class', 'btn btn-' + context);
    };

    // --------------------------------------------------------------------------

    this._init_preview = function()
    {
        var _this = this;
        $('#btnPreview').on('click', function() {

            _this._submit_preview();
            return false;
        });
    };

    // --------------------------------------------------------------------------

    this._submit_preview = function()
    {
        //  Alter the post form and submit it
        var oldTarget = $('#post-form').attr('target') || '';
        $('#post-form').attr('target', '_blogPreview');
        $('#isPreview').val(1);

        //  Do submit
        $('#btnSubmit').click();

        //   Reset
        $('#isPreview').val(0);
        $('#post-form').attr('target', oldTarget);
    };
};