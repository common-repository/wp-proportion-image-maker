            jQuery(document).ready(function($){

                var smartsetting = (function(){

                    var product_settings = {

                        width: 100,
                        height: 100,
                        top: 100,
                        left: 100,
                        scale_width: 100,
                        scale_height: 100,
                        scale_top: 100,
                        scale_left: 200,
                        image_height: 100,
                        height_status: 0
                    };

                    function init() {

                        setPosition();
                        changeImageHeight();
                        changeImageShow();
                        changeOneOfImages();
                        changeBackground();
                    }

                    function changeBackground() {

                        $("#input-image-background").on('change', function(){
                            var target = $(this), image, path;
                            image = target.val();
                            path = $('option:selected', this).attr('data-imagepath');

                            //Set new image for background
                            $('#upload-image-field-background').val(path);
                            $('#show-image-background-main').attr("src", image);
                            $('#show-image-background').attr("src", image);
                        });
                    }

                    function changeOneOfImages() {
                        $('[id^=button-image-]').click(function(e) {
                            e.preventDefault();
                            var self = this;
                            var image = wp.media({
                                title: 'Upload Image',
                                // mutiple: true if you want to upload multiple files at once
                                multiple: false
                            }).open()
                                .on('select', function(e){
                                    // This will return the selected image from the Media Uploader, the result is an object
                                    var uploaded_image = image.state().get('selection').first();
                                    // We convert uploaded_image to a JSON object to make accessing it easier
                                    // Output to the console uploaded_image

                                    var image_url = uploaded_image.toJSON().url,
                                        path_temp = image_url.split('/'),
                                        path = system_image_path,
                                        n = path_temp.length,
                                        domain = new RegExp('www|'+ system_domain, "gi"),
                                        prime = 0;

                                    if ((path_temp[0] == 'http:') || (path_temp[0] == 'https:')) {
                                        for (var i = 0; i < n; i++) {
                                            if (!path_temp[i].match(/http:|https:|wp-content|uploads?/gi) && !path_temp[i].match(domain)) {
                                                if (!prime){
                                                    path += path_temp[i];
                                                    prime = 1;
                                                } else {
                                                    path += '/' + path_temp[i];
                                                }
                                            }
                                        }
                                    }

                                    // Let's assign the url value to the input field
                                    var target_id = self.id.substr(13);

                                    if (target_id == 'background') {
                                        $('#upload-image-field-background').val(path);
                                        $('#show-image-background-main').attr("src", image_url);
                                        $('#show-image-background').attr("src", image_url);
                                    } else if(target_id == 'product') {
                                        $('#upload-image-field-product').val(path);
                                        $('#show-image-product').attr("src", image_url);
                                        $('#show-image-product-main').attr("src", image_url);
                                    }

                                });
                        });

                    }


                    function addScale(height) {

                        var html  =  '<div class="scale-for-image">';
                        html +=  '   <div class ="horizontal-line-top"></div>';
                        html +=  '  <div class="line-first"></div>';
                        html +=  '   <div class="wordwrapper">';
                        html +=  '      <div class="word">'+ height +'</div>';
                        html +=  '    </div>';
                        html +=  '  <div class="line-second"></div>';
                        html +=  '   <div class ="horizontal-line-bottom"></div>';
                        html +=  '</div>';

                        $(".container-for-product-image").before(html);

                        if (product_settings['height_status'] == 1) {
                            $(".scale-for-image").show();
                        } else {
                            $(".scale-for-image").hide();
                        }

                        resizeScale();
                    }

                    function changeImageHeight() {

                        $("#input-height-image").bind('input', function() {

                            $(".word").html($("#input-height-image").val());
                        });
                    }

                    function changeImageShow() {

                        $("#input-show-scale").on('change', function() {

                            product_settings['height_status'] = $("#input-show-scale").val();

                            if (product_settings['height_status'] == 1) {
                                $(".scale-for-image").show();
                            } else {
                                $(".scale-for-image").hide();
                            }
                        });
                    }

                    function resizeScale() {

                        interact('.scale-for-image')
                            .draggable({
                                // enable inertial throwing
                                inertia: true,
                                // keep the element within the area of it's parent
                                restrict: {
                                    restriction: "parent",
                                    endOnly: true,
                                    elementRect: { top: 0, left: 0, bottom: 0, right: 0 }
                                },
                                // enable autoScroll
                                autoScroll: true,

                                // call this function on every dragmove event
                                onmove: scaleMoveListener,
                                // call this function on every dragend event
                                onend: function (event) {
                                    var mover = $(".scale-for-image");
                                    var top = mover.position().top;
                                    var left = mover.position().left;
                                    var height = mover.height();

                                    $("#input-product-scale-position").val(Math.round(500 - top - height) + 'x' + Math.round(left));

                                }
                            })
                            .resizable({
                                preserveAspectRatio: true,
                                edges: { left: true, right: true, bottom: true, top: true }
                            })
                            .on('resizemove', function (event) {
                                var target = event.target,
                                    x = (parseFloat(target.getAttribute('data-x')) || 0),
                                    y = (parseFloat(target.getAttribute('data-y')) || 0);

                                // update the element's style
                                target.style.width  = event.rect.width + 'px';
                                target.style.height = event.rect.height + 'px';

                                // translate when resizing from top or left edges
                                x += event.deltaRect.left;
                                y += event.deltaRect.top;

                                target.style.webkitTransform = target.style.transform =
                                    'translate(' + x + 'px,' + y + 'px)';

                                target.setAttribute('data-x', x);
                                target.setAttribute('data-y', y);

                                var mover = $(".scale-for-image");
                                var top = mover.position().top;
                                var left = mover.position().left;
                                var height = mover.height();

                                $("#input-product-scale-size").val(Math.round(event.rect.width) + 'x' + Math.round(event.rect.height));

                            });


                    }

                    function setPosition() {

                        $(".container-for-product-image").css({
                            top: 500 - product_settings['height'] - product_settings['top'],
                            left: product_settings['left'],
                            width: product_settings['width'],
                            height: product_settings['height']
                        });

                        addScale(product_settings['image_height']);

                        $(".scale-for-image").css({
                            height: product_settings['scale_height'],
                            top:  500 - product_settings['scale_height'] - product_settings['scale_top'],
                            left: product_settings['scale_left'],
                            width: product_settings['scale_width']
                        });

                        //Filling all position and size fields by start value
                        $("#input-product-image-position").val(Math.round(product_settings['top']) + 'x' + Math.round(product_settings['left']));
                        $("#input-product-image-size").val(Math.round(product_settings['width']) + 'x' + Math.round(product_settings['height']));
                        $("#input-product-scale-position").val(Math.round(product_settings['scale_top']) + 'x' + Math.round(product_settings['scale_left']));
                        $("#input-product-scale-size").val(Math.round(product_settings['scale_width']) + 'x' + Math.round(product_settings['scale_height']));
                    }

                    function resizing() {

                        interact('.container-for-product-image')
                            .draggable({
                                // enable inertial throwing
                                inertia: true,
                                // keep the element within the area of it's parent
                                restrict: {
                                    restriction: "parent",
                                    endOnly: true,
                                    elementRect: { top: 0, left: 0, bottom: 0, right: 0 }
                                },
                                // enable autoScroll
                                autoScroll: true,

                                // call this function on every dragmove event
                                onmove: dragMoveListener,
                                // call this function on every dragend event
                                onend: function (event) {
                                    var mover = $(".container-for-product-image");
                                    var top = mover.position().top;
                                    var left = mover.position().left;
                                    var height = mover.height();


                                    $("#input-product-image-position").val(Math.round(500 - top - height) + 'x' + Math.round(left));

                                }
                            })
                            .resizable({
                                preserveAspectRatio: true,
                                edges: { left: true, right: true, bottom: true, top: true }
                            })
                            .on('resizemove', function (event) {
                                var target = event.target,
                                    x = (parseFloat(target.getAttribute('data-x')) || 0),
                                    y = (parseFloat(target.getAttribute('data-y')) || 0);

                                // update the element's style
                                target.style.width  = event.rect.width + 'px';
                                target.style.height = event.rect.height + 'px';

                                // translate when resizing from top or left edges
                                x += event.deltaRect.left;
                                y += event.deltaRect.top;

                                target.style.webkitTransform = target.style.transform =
                                    'translate(' + x + 'px,' + y + 'px)';

                                target.setAttribute('data-x', x);
                                target.setAttribute('data-y', y);

                                $("#input-product-image-size").val(Math.round(event.rect.width) + 'x' + Math.round(event.rect.height));


                                var mover = $(".container-for-product-image");
                                var top = mover.position().top;
                                var left = mover.position().left;
                                var height = mover.height();
                            });
                    }

                    function dragMoveListener (event) {

                        var target = event.target,
                        // keep the dragged position in the data-x/data-y attributes
                            x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx,
                            y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                        // translate the element
                        target.style.webkitTransform =
                            target.style.transform =
                                'translate(' + x + 'px, ' + y + 'px)';

                        // update the posiion attributes
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);

                    }

                    function scaleMoveListener (event) {

                        var target = event.target,
                        // keep the dragged position in the data-x/data-y attributes
                            x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx,
                            y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                        // translate the element
                        target.style.webkitTransform =
                            target.style.transform =
                                'translate(' + x + 'px, ' + y + 'px)';

                        // update the posiion attributes
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);

                    }

                    return {
                        init: init,
                        resize: resizing
                    }
                })();

                smartsetting.init();
                smartsetting.resize();
            });


