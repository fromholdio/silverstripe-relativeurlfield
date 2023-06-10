(function($) {
$.entwine("ss", function($) {

    /**
     * Class: .field.relativeurl
     *
     * Provides enhanced functionality (read-only/edit switch) and
     * input validation on the RelativeURLField
     */
    $('.field.relativeurl:not(.readonly)').entwine({

        onmatch : function() {
            // Only initialize the field if it contains an editable field.
            // This ensures we don't get bogus previews on readonly fields.
            if(this.find(':text').length) {
                this.toggleEdit(false);
            }
            this.redraw();
            this._super();
        },

        redraw: function() {
            var field = this.find(':text'),
                url = decodeURI(field.data('base-url') + field.val()),
                baseUrl = decodeURI(field.data('base-url'));

            // Transfer current value to holder
            this.find('.URL-link').attr('href', encodeURI(url)).text(url);
            this.find('.BaseURL-link').attr('href', encodeURI(baseUrl)).text(baseUrl);
        },

        toggleEdit: function(toggle) {
            var field = this.find(':text');
            this.find('.preview-holder')[toggle ? 'hide' : 'show']();
            this.find('.edit-holder')[toggle ? 'show' : 'hide']();
            if(toggle) {
                field.data("origval", field.val()); //retain current value for cancel
                field.focus();
            }
        },

        /**
         * Commits the change of the RelativeURL to the field
         * Optional: pass in (String) to update the RelativeURL
         */
        update: function() {
            var self = this,
                field = this.find(':text'),
                currentVal = field.data('origval'),
                passedVal = arguments[0],
                updateVal = (passedVal && passedVal !== "") ? passedVal : field.val();

            if (currentVal !== updateVal) {
                this.addClass('loading');
                this.suggest(updateVal, function(data) {
                    field.val(decodeURIComponent(data.value));
                    self.toggleEdit(false);
                    self.removeClass('loading');
                    self.redraw();
                });
            } else {
                this.toggleEdit(false);
                this.redraw();
            }
        },

        /**
         * Cancels any changes to the field
         */
        cancel: function() {
            var field = this.find(':text');
            field.val(field.data("origval"));
            this.toggleEdit(false);
        },

        /**
         * Run collision & character checks to return valid value
         */
        suggest: function(val, callback) {
            var self = this,
                field = self.find(':text'),
                urlParts = $.path.parseUrl(self.closest('form').attr('action')),
                url = urlParts.hrefNoSearch + '/field/' + field.attr('name') + '/suggest/?value=' + encodeURIComponent(val);
            if(urlParts.search) url += '&' + urlParts.search.replace(/^\?/, '');

            $.ajax({
                url: url,
                success: function(data) {
                    callback.apply(this, arguments);
                },
                error: function(xhr, status) {
                    xhr.statusText = xhr.responseText;
                },
                complete: function() {
                    self.removeClass('loading');
                }
            });
        },
    });

    $('.field.relativeurl .text').entwine({
        onkeydown: function(e) {
            // Prevent page-level form submission, update this field instead
            if (e.keyCode === 13) {
                e.preventDefault();
                this.closest('.field').update();
            }
        }
    });

    $('.field.relativeurl .edit').entwine({
        onclick: function(e) {
            e.preventDefault();
            console.log('clicked Edit');
            this.closest('.field').toggleEdit(true);
        }
    });

    $('.field.relativeurl .update').entwine({
        onclick: function(e) {
            console.log('clicked Update');
            e.preventDefault();
            this.closest('.field').update();
        }
    });

    $('.field.relativeurl .cancel').entwine({
        onclick: function(e) {
            e.preventDefault();
            console.log('clicked Cancel');
            console.log(this.closest('.field'));
            this.closest('.field').cancel();
        }
    });
});
})(jQuery);
