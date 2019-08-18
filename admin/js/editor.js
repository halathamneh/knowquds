var sipModule = window.sipModule || {};

(function (appModule, $) {
    function Event() {

    }

    Event.prototype = $.extend({}, {
        trigger: function (id) {
            if (this.topics && this.topics[id])
                this.topics[id].fireWith(this, Array.prototype.slice.call(arguments, 1));
            return this;
        },

        bind: function (id) {
            this.topics = this.topics || {};
            this.topics[id] = this.topics[id] || $.Callbacks();
            this.topics[id].add.apply(this.topics[id], Array.prototype.slice.call(arguments, 1));
            return this;
        },

        unbind: function (id) {
            if (this.topics && this.topics[id])
                this.topics[id].remove.apply(this.topics[id], Array.prototype.slice.call(arguments, 1));
            return this;
        }
    });

    appModule.Event = Event;
})(sipModule, jQuery);

(function (appModule, $) {
    function Preview(app, event) {
        var preview = this;

        preview.app = app;
        preview.event = event;

        $(function () {
            preview.init();
        })
    }

    Preview.prototype.init = function () {
        var preview = this;

        preview.$editor = $('.js-sip-editor');
        preview.$image = $('.js-sip-image');

        preview.event.bind('init', function () {
            preview.event.trigger('updateImage', preview.app.image);

            preview.app.points.forEach(function (point) {
                preview.renderPoint(point);
            });
        });

        preview.event.bind('updateImage', function (url) {
            preview.$image.attr('src', url);
        });

        preview.event.bind('updateCurrentPoint', function (point) {
            preview.setCurrentPoint(point);
        });

        preview.event.bind('currentPointFromList', function (point) {
            preview.setCurrentPoint(point);
        });

        preview.$editor.on('click', function (evt) {
            evt.preventDefault();

            if ($(evt.target).is('.js-sip-image')) {
                var id = preview.app.generatePointId();

                var point = {
                    id: id,
                    popup_type: 'popup',
                    icon_text: id,
                    left: evt.offsetX / preview.$image.width() * 100,
                    top: evt.offsetY / preview.$image.height() * 100,
                };

                preview.event.trigger('addPoint', point);
                preview.setCurrentPoint(point);
                preview.event.trigger('currentPointFromPreview', point);
            }
        });

        preview.$editor.on('click', '.js-sip-point', function (evt) {
            var $point = $(evt.target).closest('.js-sip-point');

            var point = preview.app.getPointById($point.data('id'));

            if (point) {
                preview.setCurrentPoint(point);
                preview.event.trigger('currentPointFromPreview', point);
            }
        });

        preview.event.bind('removePoint', function (point) {
            preview.$editor.find('.js-sip-point[data-id="' + point.id + '"]').remove();
        });

        $('.js-sip-preview-control').appendTo('#sip-preview .hndle span');

        $('.js-sip-preview-control').on('click', function (evt) {
            evt.stopPropagation();
        });

        // Open media box
        var file_frame, image_data;
        $('#sip-preview .js-sip-change-image').click(function () {

            if (undefined !== file_frame) {

                file_frame.open();
                return;

            }

            file_frame = wp.media.frames.file_frame = wp.media({
                title: sip_params.choose_image,
                button: {
                    text: sip_params.choose_image
                },
                multiple: false
            });

            file_frame.on('select', function () {

                var json = file_frame.state().get('selection').first().toJSON();

                // First, make sure that we have the URL of an image to display
                if (0 > $.trim(json.url.length)) {
                    return;
                }

                preview.event.trigger('updateImage', json.url);
            });

            // Now display the actual file_frame
            file_frame.open();
        });
    };

    Preview.prototype.setCurrentPoint = function (point) {
        var preview = this;

        if (preview.$currentPoint) {
            preview.$currentPoint.removeClass('current');
        }

        preview.$currentPoint = $('.js-sip-point[data-id="' + point.id + '"]');

        if (!preview.$currentPoint.length) {
            preview.$currentPoint = preview.renderPoint(point);
        }

        preview.$currentPoint.addClass('current');

        preview.updatePoint(preview.$currentPoint, point);
    };

    Preview.prototype.renderPoint = function (point) {
        var preview = this;

        var $pointEl = $('<span class="sip-point js-sip-point" data-id="' + point.id + '"></span>').appendTo(preview.$editor);
        $pointEl.draggable({
            containment: 'parent',
            drag: function (evt, ui) {
                var width = preview.$image.width();
                var height = preview.$image.height();
                var iconWidth = $(evt.target).width();
                var iconHeight = $(evt.target).height();

                point.left = (ui.position.left + iconWidth / 2) / width * 100;
                point.top = (ui.position.top + iconHeight / 2) / height * 100;
            }
        });

        preview.updatePoint($pointEl, point);

        return $pointEl;
    };

    Preview.prototype.updatePoint = function ($pointEl, point) {
        var iconHtml = '';

        if (point.icon_image) {
            //iconHtml = '<img src="'+ point.icon_image +'" />';

            $pointEl.removeClass('sip-point-icon-text');
        } else {
            //iconHtml = '<span class="sip-point-text">' + point.icon_text + '</span>';

            $pointEl.addClass('sip-point-icon-text');

            if (typeof point.icon_background !== 'undefined') {
                $pointEl.css({
                    backgroundColor: point.icon_background
                });
            }

            if (typeof point.icon_color !== 'undefined') {
                $pointEl.css({
                    "border-color": point.icon_color
                });
            }
        }

        $pointEl.html(iconHtml);

        $pointEl.css({
            left: 'calc(' + point.left + '% - ' + $pointEl.width() / 2 + 'px)',
            top: 'calc(' + point.top + '% - ' + $pointEl.height() / 2 + 'px)'
        });
    };

    appModule.Preview = Preview;
})(sipModule, jQuery);

(function (appModule, $) {
    function PointList(app, event) {
        var pointList = this;

        pointList.app = app;
        pointList.event = event;

        pointList.$currentItem = null;

        $(function () {
            pointList.init();
        });
    }

    PointList.prototype.init = function () {
        var pointList = this;

        pointList.currentPoint = null;
        pointList.$currentItem = null;
        pointList.pointRenderer = wp.template('sip-point');

        pointList.$list = $('.js-sip-point-list');

        pointList.event.bind('init', function () {
            pointList.app.points.forEach(function (point) {
                pointList.renderPoint(point);
            });
        });

        pointList.event.bind('submitImagePoint', function (data) {
            if ($.isArray(data.points)) {
                data.points.forEach(function (point) {
                    var productId = pointList.$list.find('.js-sip-point-item[data-id="' + point.id + '"] [data-prop="product"]').val();

                    if (productId) {
                        point.product = productId;
                    }
                });
            }
        });

        pointList.event.bind('currentPointFromPreview', function (point) {
            pointList.setCurrentPoint(point);
        });

        pointList.event.bind('removePoint', function (point) {
            pointList.$list.find('.js-sip-point-item[data-id="' + point.id + '"]').remove();
        });

        pointList.$list.on('keyup change', '.js-point-input', function (evt) {
            var $input = $(evt.target);

            if (pointList.currentPoint) {
                pointList.currentPoint[$input.data('prop')] = $input.val();
                pointList.event.trigger('updateCurrentPoint', pointList.currentPoint);
            }
        });

        pointList.$list.on('click', '.js-sip-point-item .js-sip-point-item-handle', function (evt) {
            if ($(evt.target).closest('.js-remove-point').length) {
                return true;
            }

            var $item = $(evt.target).closest('.js-sip-point-item');

            if ($item.hasClass('open')) {
                $item.removeClass('open');

                return true;
            }

            var point = pointList.app.getPointById($item.data('id'));

            if (point) {
                pointList.setCurrentPoint(point);
                pointList.event.trigger('currentPointFromList', point);
            }
        });

        pointList.$list.on('click', '.js-remove-point', function (evt) {
            var $item = $(evt.target).closest('.js-sip-point-item');

            if (confirm(sip_params.delete_point_confirm)) {
                pointList.event.trigger('removePoint', pointList.app.getPointById($item.data('id')));
            }
        });

        // Render existing points

        var file_frame2;
        pointList.$list.on("click", '.js-sip-add-point-images', function (e) {
            e.preventDefault();
            var $this = $(this);
            var $imagesContainer = $this.siblings(".sip-point-images-container");
            if (undefined !== file_frame2) {

                file_frame2.open();
                return;

            }

            file_frame2 = wp.media.frames.file_frame = wp.media({
                title: sip_params.choose_image,
                button: {
                    text: sip_params.choose_image
                },
                multiple: true
            });

            file_frame2.on('select', function () {

                var attachments = file_frame2.state().get('selection').map(
                    function (attachment) {

                        attachment.toJSON();
                        return attachment;

                    });

                //loop through the array and do things with each attachment

                var i;
                var ids = [];
                for (i = 0; i < attachments.length; ++i) {

                    var thumb = attachments[i].attributes.sizes.thumbnail.url;

                    $imagesContainer.append('<div data-index="' + i + '" data-id="' + attachments[i].id + '" class="point-image-item"><a href="#" class="js-sip-point-image-remove"><i class="dashicons dashicons-trash"></i></a><img src="' + thumb + '" alt=""></div>');
                    if (pointList.currentPoint['description_images'] && pointList.currentPoint['description_images'].length)
                        pointList.currentPoint['description_images'].push({
                            id: attachments[i].id,
                            url: attachments[i].attributes.url,
                            thumb: attachments[i].attributes.sizes.thumbnail.url
                        });
                    else
                        pointList.currentPoint['description_images'] = [
                            {
                                id: attachments[i].id,
                                url: attachments[i].attributes.url,
                                thumb: attachments[i].attributes.sizes.thumbnail.url
                            }
                        ]
                }
                pointList.event.trigger('updateCurrentPoint', pointList.currentPoint);


            });

            // Now display the actual file_frame
            file_frame2.open();
        });

        pointList.$list.on('click', '.js-sip-point-image-remove', function (e) {
            e.preventDefault();
            var $item = $(e.target).closest('.point-image-item');
            var index = $item.data('index');
            pointList.currentPoint['description_images'].splice(index, 1);
            $item.remove();
        });
    };

    PointList.prototype.setCurrentPoint = function (point) {
        var pointList = this;

        var oldId = pointList.currentPoint ? pointList.currentPoint.id : '';

        // Close detail of previous current point
        if (pointList.$currentItem) {
            pointList.$currentItem.removeClass('open');
        }

        pointList.currentPoint = point;
        pointList.$currentItem = $('.js-sip-point-item[data-id="' + point.id + '"]');

        if (!pointList.$currentItem.length) {
            pointList.$currentItem = pointList.renderPoint(point);
        }

        pointList.$currentItem.addClass('open');

        $(document.body).animate({
            scrollTop: pointList.$currentItem.offset().top - 32
        }, 300);
    };

    PointList.prototype.renderPoint = function (point) {
        var pointList = this;

        var $item = $(pointList.pointRenderer(point));

        pointList.$list.find('.js-point-warning').addClass('hidden');

        pointList.$list.append($item);

        var id = 'popupContent_' + point.id;
        $item.find('[data-prop="popup_content"]').attr('id', id);
        //wp.editor.initialize(id, {
            tinymce.init({
                selector: "#" + id,
                menubar: false,
                plugins: [
                    'autolink lists link print preview anchor',
                    'directionality paste code help'
                ],
                directionality: "rtl",
                toolbar: 'undo redo | removeformat | bold italic | rtl | alignleft aligncenter alignright alignjustify | bullist numlist | code',
                init_instance_callback: function (editor) {
                    editor.on('Change', function (e) {
                        editor.save();
                        if (pointList.currentPoint) {
                            pointList.currentPoint[editor.getElement().dataset.prop] = editor.getContent();
                            pointList.event.trigger('updateCurrentPoint', pointList.currentPoint);
                        }

                    });
                }
            //}
        });

        $item.find('.js-sip-color').wpColorPicker({
            change: function (event, ui) {
                var element = event.target;
                var color = ui.color.toString();

                $(element).val(color).trigger('change');
            },

            clear: function (event) {
                var element = jQuery(event.target).siblings('.wp-color-picker')[0];
                var color = '';

                if (element) {
                    $(element).val(color).trigger('change');
                }
            }
        });

        // Init product autocomplete
        $(document.body).trigger('wc-enhanced-select-init');

        $item.find('.js-inp-popup-type').on('change', function (evt) {
            var $input = $(evt.target);

            switch ($input.val()) {
                case 'popup':
                    $('.js-popup-title, .js-popup-content, .js-popup-position', $item).removeClass('hidden');
                    $('.js-popup-link, .js-popup-product', $item).addClass('hidden');
                    break;
                case 'link':
                    $('.js-popup-link, .js-popup-title, .js-popup-position', $item).removeClass('hidden');
                    $('.js-popup-content, .js-popup-product', $item).addClass('hidden');
                    break;
                case 'product':
                    $('.js-popup-product, .js-popup-position', $item).removeClass('hidden');
                    $('.js-popup-title, .js-popup-content, .js-popup-link', $item).addClass('hidden');
                    break;
            }
        });

        $item.find('.js-sip-remove-icon-image').on('click', function (evt) {
            evt.preventDefault();

            $item.find('.js-sip-inp-icon-image').val('').trigger('change');
        });

        var file_frame, image_data;
        $item.find('.js-sip-change-icon-image').on('click', function (evt) {
            evt.preventDefault();

            if (undefined !== file_frame) {

                file_frame.open();
                return;

            }

            file_frame = wp.media.frames.file_frame = wp.media({
                title: sip_params.choose_image,
                button: {
                    text: sip_params.choose_image
                },
                multiple: false
            });

            file_frame.on('select', function () {

                var json = file_frame.state().get('selection').first().toJSON();

                // First, make sure that we have the URL of an image to display
                if (0 > $.trim(json.url.length)) {
                    return;
                }

                $item.find('.js-sip-inp-icon-image').val(json.url).trigger('change');
            });

            // Now display the actual file_frame
            file_frame.open();
        });

        return $item;
    };

    appModule.PointList = PointList;
})(sipModule, jQuery);

(function (appModule, $) {
    function App() {
        var app = this;

        app.event = new appModule.Event();
        app.preview = new appModule.Preview(app, app.event);
        app.pointList = new appModule.PointList(app, app.event);

        app.image = '';
        app.points = [];
        app.pointIndex = 0;

        app.event.bind('addPoint', function (point) {
            app.points.push(point);
        });

        app.event.bind('removePoint', function (removePoint) {
            app.points.splice(app.points.indexOf(removePoint), 1);
        });

        app.event.bind('updateImage', function (url) {
            app.image = url;
        });

        $(function () {
            app.init();
        });
    }

    App.prototype.init = function () {
        var app = this;
        var $form = $('#post');

        $form.on('submit', function (evt) {
            if (!$form.find('[name="sip_image_point"]').length) {
                app.event.trigger('submitImagePoint', {
                    'points': app.points
                });

                var imagePointText = JSON.stringify({image: app.image});
                var pointText = JSON.stringify(app.points);

                $form.prepend('<input type="hidden" name="sip_image_point" value="' + app.escapeAttr(imagePointText) + '" />');
                $form.prepend('<input type="hidden" name="sip_points" value="' + app.escapeAttr(pointText) + '" />');

                evt.preventDefault();

                $form.trigger('submit');
            }
        });

        // Init from existing data
        if (sip_params.image_point_data.image) {
            app.image = sip_params.image_point_data.image;
        }

        if ($.isArray(sip_params.points_data)) {
            app.points = sip_params.points_data;

            app.points.forEach(function (point) {
                point.id = app.generatePointId();
            });
        }

        app.event.trigger('init');
    };

    App.prototype.generatePointId = function () {
        var app = this;

        return ++app.pointIndex;
    };

    App.prototype.getPointById = function (id) {
        var app = this;
        var result = null;

        app.points.forEach(function (point) {
            if (point.id === id) {
                result = point;
            }
        });

        return result;
    };

    App.prototype.escapeAttr = function (s, preserveCR) {
        preserveCR = preserveCR ? '&#13;' : '\n';
        return ('' + s)
            .replace(/&/g, '&amp;')
            .replace(/'/g, '&apos;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\r\n/g, preserveCR)
            .replace(/[\r\n]/g, preserveCR);
    };

    appModule.app = new App();
})(sipModule, jQuery);