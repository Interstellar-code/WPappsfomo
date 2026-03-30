const LinksyCopy = ({
    name: 'LinksyCopy',
    props: {
        value: {
            type: String,
            default: ''
        },
        style: [String, Object]
    },
    watch: {
        clicked: function (val) {
            const _this = this;
            if (val) {
                setTimeout(function() {
                    _this.clicked = false
                }, 800)
            }
        }
    },
    data() {
        return {
            clicked: false,
            styles: {
                container: {
                    border: 'none',
                    padding: '8px 10px',
                    background: 'transparent',
                    position: 'relative',
                    animationDuration: '.5s'
                },
            }
        }
    },
    methods: {
        onClicked() {
            if (this.value && this.value.length > 0) {
                this.clicked = true
                copyToClipboard(this.value);
            }
        }
    },
    template: `
        <button class="linksy-copy" :style="[styles.container, style]" @click="onClicked">
            <slot>
                <i class="fa fa-copy"></i>
            </slot>
            <span class="linksy-animation" :class="{'fade-out-up': clicked}" :style="[styles.container, style]" style="position:absolute; left: 0; top: 0; opacity: 0; color: #3582c4">
                <slot>
                    <i class="fa fa-copy"></i>
                </slot>
            </span>
        </button>
    `,
})