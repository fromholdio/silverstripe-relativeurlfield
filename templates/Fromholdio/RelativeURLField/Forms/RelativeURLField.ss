<div class="preview-holder">
    <a class="URL-link" href="$URL" target="_blank">
        $URL
    </a>
    <% if not $IsReadonly %>
        <button role="button" type="button" class="btn btn-outline-secondary btn-sm edit font-icon-edit">
            <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Edit 'Edit' %>
        </button>
    <% end_if %>
</div>

<div class="edit-holder">
    <div class="edit-holder-controls">
        <a class="BaseURL-link" href="$BaseURL" target="_blank">
            $BaseURL
        </a>
        <div class="input-group">
            <input $AttributesHTML />
            <div class="input-group-append">
                <button role="button" type="button" class="btn btn-primary font-icon-tick update">
                    <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.OK 'OK' %>
                </button>
            </div>
            <div class="input-group-append">
                <button role="button" type="button" class="btn btn-outline-secondary btn-sm font-icon-cancel input-group-append cancel">
                    <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Cancel 'Cancel' %>
                </button>
            </div>
        </div>
    </div>
    <% if $HelpText %><p class="form__field-description">$HelpText</p><% end_if %>
</div>
