
const LinksyPhraseCard = ({
    name: 'LinksyPhraseCard',
    components: {
        LinksyPhraseItem: {
            name: 'LinksyPhraseItem',
            components: {
                LinksyDropdown
            },
            props: {
                item: Object,
                used: Boolean,
                selected: Boolean
            },
            computed: {
                score: {
                    get() {
                        return Math.round((this.item.score + Number.EPSILON) * 100);
                    }
                },
                color: {
                    get() {
                        return scoreToColor(this.item.score);
                    }
                },
            },
            methods: {
                onSelect() {
                    if (!this.selected)
                        this.$emit('onSelect', this.item)
                },

                onMenuSelected(type) {
                    switch(type) {
                        case 'View':
                            window.open(this.item.link, '_blank');
                            break;

                        case 'Edit':
                            window.open(LINKSY.post_url+'?post='+this.item.post_id+'&action=edit', '_blank');
                            break;

                        case 'Stop Suggesting':
                            this.$emit('onIgnore', this.item)
                            break;
                    }
                }
            },
            template: `
                <div class="linksy-phrase-card-item" :class="{used: used, selected: selected}">
                    <h5 @click="onSelect">{{item.post_title}}</h5>
                    <small>
                        <i class="fa fa-check"></i>
                        Added by another anchor
                    </small>
                    <div style="display: flex; align-items: center;">
                        <div class="bar">
                            <div class="bar-brogress" :style="{width: 'calc('+score+'% - 8px)', background: color}"></div>
                            <div class="bar-label" :style="{left: 'calc('+score+'% - 10px)', background: color}">{{score}}</div>
                        </div>
                        <div style="padding-left: 8px;">
                            <linksy-dropdown
                                hide-icon
                                :items="['View', 'Edit', 'Stop Suggesting']"
                                @selected="onMenuSelected"
                            >
                                <i class="fa fa-ellipsis"></i>
                            </linksy-dropdown>
                        </div>
                    </div>
                </div>
            `,
        },
    },
    props: {
        item: Object,
        expanded: Boolean,
        selectedItems: {
            type: Array,
            default: []
        }
    },
    data() {
        return {
            showSource: false,
            meta: {
                customLinkAction: '',
                showCustomLinkAction: false,
            },
        }
    },
    computed: {
        id: {
            get() {
                return 'linksy-phrase-card-'+this.item.phrase.toLowerCase()
                    .replace(new RegExp(escapeRegExp(' '), 'g'), '-')
                    .replace(new RegExp(escapeRegExp("'"), 'g'), '');
            }
        },
        summary: {
            get() {
                return this.item.documents[0].post_title;
            }
        },
        color: {
            get() {
                return scoreToColor(this.item.documents[0].score);
            }
        },
        selected: {
            get() {
                return this.selectedItems.findIndex(e => e.phrase === this.item.phrase) !== -1;
            }
        }
    },
    methods: {
        documentIsUsed(document) {
            const selectedCnt = this.selectedItems.filter(e => e.document.post_id === document.post_id).length;

            if (this.documentIsSelected(document)) {
                return selectedCnt > 1;
            }

            return selectedCnt > 0;
        },
        documentIsSelected(document) {
            const selectedDocument = this.selectedItems.find(e => e.phrase === this.item.phrase);
            return document.post_id === selectedDocument?.document?.post_id;
        },
        onItemAdd() {
            const isInUse = this.item.documents.some(e => e.link == this.meta.customLinkAction);
            if (isInUse) {
                this.$toast.error('already in use');
                return;
            }
            this.$emit('onItemAdd', this.item, this.meta.customLinkAction);
            this.meta.customLinkAction = '';
        },
        onSelect() {
            this.$emit('onSelect', this.item);
        },
        onIgnore() {
            this.$emit('onIgnore', this.item);
        },
        handleItemSelect(item) {
            this.$emit('onItemSelect', item, this.item);
        },
        handleItemIgnore(item) {
            this.$emit('onItemIgnore', item, this.item);
        }
    },
    template: `
        <div class="linksy-phrase linksy-phrase-card" :id="id" :class="{selected, active: expanded}">
            <h4 @click="onSelect" class="d-flex justify-content-between align-items-center">
                <div><span class="dot" :style="{background: color}" ></span> {{item.phrase}}</div>
                <i v-if="expanded" class="fa" :class="[showSource? 'fa-circle-chevron-up' : 'fa-circle-chevron-down']" @click="showSource = !showSource"></i>
            </h4>
            <div class="linksy-phrase-card-body">
                <div v-if="showSource">
                    <p class="source" v-html="item.source.replace( item.phrase, '<mark>'+item.phrase+'</mark>' )"></p>
                    <hr style="margin-bottom: 0" />
                </div>

                <div class="options">
                    <linksy-phrase-item
                        v-for="document in item.documents.filter(e => !e.ignored)"
                        :item="document"
                        :used="documentIsUsed(document)"
                        :selected="documentIsSelected(document)"
                        @onSelect="handleItemSelect"
                        @onIgnore="handleItemIgnore"
                    >
                    </linksy-phrase-item>
                </div>
                <div class="meta">
                    <button type="button" class="btn-clear ignore" @click="onIgnore">
                        <i class="fa fa-times-square"></i>
                        Ignore Keyword
                    </button>
                    <button type="button" class="btn-clear custom" @click="meta.showCustomLinkAction = true">
                        <i class="fa fa-plus-square"></i>
                        Custom URL
                    </button>
                </div>
                <div class="meta-actions" style="padding-right: 1px;">
                    <div v-if="meta.showCustomLinkAction">
                        <input type="text" v-model="meta.customLinkAction" style="width: 100%;" />
                        <div class="d-flex justify-content-end" style="margin-top: 5px;">
                            <button type="button" @click="meta.showCustomLinkAction = false" class="btn-default btn-small">Cancel</button>
                            <button type="button" @click="onItemAdd" class="btn-app btn-small" style="margin-left: 5px;">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="linksy-phrase-card-summary" @click="onSelect">{{summary}}</div>
            <div class="toggler">
                <button type="button" class="btn-clear" @click="onSelect">
                    <i class="fa fa-angles-down"></i>
                </button>
            </div>
        </div>
    `,
    mounted() {
    }
});