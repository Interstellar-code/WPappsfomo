const SettingCard = ({
    name: 'SettingCard',
    props: {
        name: String,
        maxSnippet: {type: [Number, String], default: 150},
        description: {type: String, default: ''}
    },
    data() {
        return {
            showAll: false,
        }
    },
    computed: {
        snippet: {
            get() {
                return this.description.substring(0, this.maxSnippet)
            }
        },
        more: {
            get() {
                return this.description.substring(this.maxSnippet, this.description.length);
            }
        }
    },
    methods: {
        toggleMore: function() {
            this.showAll = !this.showAll
        }
    },
    template: `
        <div class="setting-card">
            <div class="setting-card-header">
                <slot name="name">
                    <span>{{name}}</span>
                </slot>
                <div style="margin-left: 20px;"><slot></slot></div>
            </div>
            <div class="setting-card-content">
                {{snippet}}<span v-if="showAll">{{more}}</span>

                <span v-if="description.length > parseInt(maxSnippet)" style="white-space: nowrap;">
                    <span v-if="!showAll">...</span>
                    &nbsp;<i class="fa clickable" :class="showAll? 'fa-circle-chevron-up' : 'fa-circle-chevron-down'" @click="toggleMore" style="font-size: 16px;"></i>
                </span>
            </div>      
        </div>
    `,
});