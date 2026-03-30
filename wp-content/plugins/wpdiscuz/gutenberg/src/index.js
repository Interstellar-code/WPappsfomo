import { registerPlugin } from '@wordpress/plugins';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { registerFormatType, insert } from '@wordpress/rich-text';
import { Modal, TextControl, ToggleControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, Fragment } from '@wordpress/element';
import { createRoot } from '@wordpress/element';

const SHORTCODE_NAME = 'wpdiscuz-feedback';

function generateId() {
    return Math.random().toString(36).substring(2, 12);
}

/**
 * Modal renderer helper
 */
function openFeedbackModal({ selectedText, value, onChange }) {
    const container = document.createElement('div');
    document.body.appendChild(container);
    const root = createRoot(container);

    const ModalApp = () => {
        const [question, setQuestion] = useState(selectedText);
        const [opened, setOpened] = useState(false);

        const close = () => {
            root.unmount();
            document.body.removeChild(container);
        };

        const insertShortcode = () => {
            const shortcode =
                `[${SHORTCODE_NAME} id="${generateId()}" ` +
                `question="${question}" opened="${opened ? 1 : 0}"]` +
                `${selectedText}` +
                `[/${SHORTCODE_NAME}]`;

            onChange(
                insert(value, shortcode, value.start, value.end)
            );

            close();
        };

        return (
            <Modal
                title={__('Inline Feedback', 'shortcode-inserter')}
                onRequestClose={close}
            >
                <TextControl
                    label={__('Question', 'shortcode-inserter')}
                    value={question}
                    onChange={setQuestion}
                />

                <ToggleControl
                    label={__('Opened by default', 'shortcode-inserter')}
                    checked={opened}
                    onChange={setOpened}
                />

                <div style={{ marginTop: 16 }}>
                    <Button variant="primary" onClick={insertShortcode}>
                        {__('Insert', 'shortcode-inserter')}
                    </Button>
                    <Button
                        variant="secondary"
                        onClick={close}
                        style={{ marginLeft: 8 }}
                    >
                        {__('Cancel', 'shortcode-inserter')}
                    </Button>
                </div>
            </Modal>
        );
    };

    root.render(<ModalApp />);
}

/**
 * Register RichText format
 */
registerFormatType('wpdiscuz/feedback', {
    title: __('Inline Feedback', 'shortcode-inserter'),
    tagName: 'span',
    className: 'wpdiscuz-feedback-format',

    edit({ value, onChange }) {
        const selectedText = value.text.slice(value.start, value.end);
        if (!selectedText) return null;

        return (
            <RichTextToolbarButton
                icon="shortcode"
                title={__('Inline Feedback', 'shortcode-inserter')}
                onClick={() =>
                    openFeedbackModal({
                        selectedText,
                        value,
                        onChange,
                    })
                }
            />
        );
    },
});

registerPlugin('shortcode-inserter', {
    render: () => null,
});
