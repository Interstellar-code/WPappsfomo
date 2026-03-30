 (function(wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var el = wp.element.createElement;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var SelectControl = wp.components.SelectControl;
    var __ = wp.i18n.__;

    function SectionTypePanel() {
        var metaValue = useSelect(function(select) {
            return select('core/editor').getEditedPostAttribute('meta')._rh_section_type || '';
        }, []);

        var { editPost } = useDispatch('core/editor');

        function updateSectionType(value) {
            editPost({
                meta: {
                    _rh_section_type: value
                }
            });
        }

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'rh-section-type-panel',
                title: __('Section type', 'rehub-framework'),
                open: true
            },
            el(SelectControl, {
                value: metaValue,
                options: [
                    { label: __('Template', 'rehub-framework'), value: '' },
                    { label: __('Header', 'rehub-framework'), value: 'header' },
                    { label: __('Footer', 'rehub-framework'), value: 'footer' },
                    { label: __('Product Single page', 'rehub-framework'), value: 'woosingle' },
                    { label: __('Product Archive page', 'rehub-framework'), value: 'wooarchive' }
                ],
                onChange: updateSectionType
            })
        );
    }

    registerPlugin('rh-section-type', {
        render: SectionTypePanel,
        icon: 'layout'
    });
})(window.wp);